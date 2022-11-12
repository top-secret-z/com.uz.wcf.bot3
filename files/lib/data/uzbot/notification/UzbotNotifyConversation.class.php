<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace wcf\data\uzbot\notification;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationEditor;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\Uzbot;
use wcf\system\exception\SystemException;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\LanguageFactory;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\MessageUtil;
use wcf\util\StringUtil;

/**
 * Creates conversations for Bot
 */
class UzbotNotifyConversation
{
    /**
     * limit for participants in group conversation
     */
    const NOTIFY_CONVERSATION_MULTI = 90;

    public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null)
    {
        // prepare text and data
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        $content = MessageUtil::stripCrap($content);
        $subject = MessageUtil::stripCrap(StringUtil::stripHTML($subject));
        if (\mb_strlen($subject) > 255) {
            $subject = \mb_substr($subject, 0, 250) . '...';
        }

        // test mode
        if ($bot->testMode) {
            $teaser = '';
            if (\mb_strlen($content) > 63500) {
                $content = \mb_substr($content, 0, 63500) . ' ...';
            }
            $result = \serialize([$subject, $teaser, $content]);

            UzbotLogEditor::create([
                'bot' => $bot,
                'count' => 1,
                'testMode' => 1,
                'additionalData' => $result,
            ]);

            return;
        }

        // check sender to avoid potential problems later
        $checkUser = new User($bot->senderID);
        if (!$checkUser->userID) {
            if ($bot->enableLog) {
                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 2,
                    'additionalData' => 'wcf.acp.uzbot.error.noSender',
                ]);
            }

            return false;
        }

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($content, 'com.woltlab.wcf.conversation.message', 0);

        $data = [
            'subject' => $subject,
            'time' => TIME_NOW,
            'userID' => $bot->senderID,
            'username' => $bot->sendername,
            'isDraft' => 0,
            'isUzbot' => 1,
            'participantCanInvite' => $bot->conversationAllowAdd,
        ];

        // invisible participants
        $userIDs = [];
        if (!empty($bot->conversationInvisible)) {
            $usernames = ArrayUtil::trim(\explode(',', $bot->conversationInvisible));
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.username IN (?)', [$usernames]);
            $userList->readObjectIDs();
            $userIDs = $userList->getObjectIDs();
            if ($bot->enableLog && \count($usernames) != \count($userIDs)) {
                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 1,
                    'count' => 1,
                    'additionalData' => $defaultLanguage->get('wcf.acp.uzbot.log.notify.error.invisible'),
                ]);
            }
        }
        $conversationData = [
            'data' => $data,
            'attachmentHandler' => null,
            'htmlInputProcessor' => $htmlInputProcessor,
            'messageData' => [],
            'participants' => [$bot->senderID, $receiver->userID],
            'invisibleParticipants' => \count($userIDs) ? $userIDs : [],
        ];

        try {
            $action = new ConversationAction([], 'create', $conversationData);
            $result = $action->executeAction();
            $conversation = $result['returnValues'];

            MessageQuoteManager::getInstance()->saved();

            // leave / close
            if ($bot->conversationClose || $bot->conversationLeave) {
                $conversationEditor = new ConversationEditor($conversation);

                // change user for log / action
                $oldUser = WCF::getUser();
                WCF::getSession()->changeUser(new User($bot->senderID), true);

                if ($bot->conversationClose) {
                    $conversationEditor->update(['isClosed' => 1]);

                    ConversationModificationLogHandler::getInstance()->add($conversation, 'close');
                }

                if ($bot->conversationLeave == 1) {
                    $conversationEditor->removeParticipant($bot->senderID);
                    $conversationEditor->updateParticipantSummary();
                    UserStorageHandler::getInstance()->reset([$bot->senderID], 'unreadConversationCount');

                    ConversationModificationLogHandler::getInstance()->add($conversation, 'removeParticipant', [
                        'userID' => $bot->senderID,
                        'username' => $bot->sendername,
                    ]);
                }

                if ($bot->conversationLeave == 2) {
                    $sql = "UPDATE    wcf" . WCF_N . "_conversation_to_user
                            SET    hideConversation = ?
                            WHERE    conversationID = ? AND participantID = ?";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute([Conversation::STATE_LEFT, $conversation->conversationID, $bot->senderID]);

                    UserStorageHandler::getInstance()->reset([$bot->senderID], 'conversationCount');
                    UserStorageHandler::getInstance()->reset([$bot->senderID], 'unreadConversationCount');

                    ConversationModificationLogHandler::getInstance()->leave($conversation);

                    ConversationEditor::updateParticipantCounts([$conversation->conversationID]);
                    ConversationEditor::updateParticipantSummaries([$conversation->conversationID]);
                    // no need to delete conversation, since there must be a participant at this time
                }

                // Reset to old user
                WCF::getSession()->changeUser($oldUser, true);
            }
        } catch (SystemException $e) {
            // users may get lost; check sender again to abort
            if (!$bot->checkSender(true, true)) {
                return false;
            }

            // report any other error und continue
            if ($bot->enableLog) {
                $error = $defaultLanguage->get('wcf.acp.uzbot.log.notify.error') . ' ' . $e->getMessage();

                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 1,
                    'count' => 1,
                    'additionalData' => $error,
                ]);
            }
        }
    }

    /**
     * group conversation
     * $tags misused for receiverIDs
     */
    public function sendMulti(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags)
    {
        $receiverIDs = \explode(',', $tags);

        // max 90 participants
        $chunks = \array_chunk($receiverIDs, self::NOTIFY_CONVERSATION_MULTI);

        // prepare text and data
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        $content = MessageUtil::stripCrap($content);
        $subject = MessageUtil::stripCrap(StringUtil::stripHTML($subject));
        if (\mb_strlen($subject) > 255) {
            $subject = \mb_substr($subject, 0, 250) . '...';
        }

        // test mode
        if ($bot->testMode) {
            $teaser = '';
            if (\mb_strlen($content) > 63500) {
                $content = \mb_substr($content, 0, 63500) . ' ...';
            }
            $result = \serialize([$subject, $teaser, $content]);

            UzbotLogEditor::create([
                'bot' => $bot,
                'count' => 1,
                'testMode' => 1,
                'additionalData' => $result,
            ]);

            return;
        }

        // check sender to avoid potential problems later
        $checkUser = new User($bot->senderID);
        if (!$checkUser->userID) {
            if ($bot->enableLog) {
                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 2,
                    'additionalData' => 'wcf.acp.uzbot.error.noSender',
                ]);
            }

            return false;
        }

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($content, 'com.woltlab.wcf.conversation.message', 0);

        // basic data
        $data = [
            'subject' => $subject,
            'time' => TIME_NOW,
            'userID' => $bot->senderID,
            'username' => $bot->sendername,
            'isDraft' => 0,
            'isUzbot' => 1,
            'participantCanInvite' => $bot->conversationAllowAdd,
        ];

        // invisible participants
        $userIDs = [];
        if (!empty($bot->conversationInvisible)) {
            $usernames = ArrayUtil::trim(\explode(',', $bot->conversationInvisible));
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.username IN (?)', [$usernames]);
            $userList->readObjectIDs();
            $userIDs = $userList->getObjectIDs();
            if ($bot->enableLog && \count($usernames) != \count($userIDs)) {
                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 1,
                    'count' => 1,
                    'additionalData' => $defaultLanguage->get('wcf.acp.uzbot.log.notify.error.invisible'),
                ]);
            }
        }

        foreach ($chunks as $chunk) {
            $participants = \array_merge([$bot->senderID], $chunk);

            $conversationData = [
                'data' => $data,
                'attachmentHandler' => null,
                'htmlInputProcessor' => $htmlInputProcessor,
                'messageData' => [],
                'participants' => $participants,
                'invisibleParticipants' => \count($userIDs) ? $userIDs : [],
            ];

            try {
                $action = new ConversationAction([], 'create', $conversationData);
                $result = $action->executeAction();
                $conversation = $result['returnValues'];

                MessageQuoteManager::getInstance()->saved();

                // leave / close
                if ($bot->conversationClose || $bot->conversationLeave) {
                    $conversationEditor = new ConversationEditor($conversation);

                    // change user for log / action
                    $oldUser = WCF::getUser();
                    WCF::getSession()->changeUser(new User($bot->senderID), true);

                    if ($bot->conversationClose) {
                        $conversationEditor->update(['isClosed' => 1]);
                        ConversationModificationLogHandler::getInstance()->add($conversation, 'close');
                    }

                    if ($bot->conversationLeave == 1) {
                        $conversationEditor->removeParticipant($bot->senderID);
                        $conversationEditor->updateParticipantSummary();
                        UserStorageHandler::getInstance()->reset([$bot->senderID], 'unreadConversationCount');

                        ConversationModificationLogHandler::getInstance()->add($conversation, 'removeParticipant', [
                            'userID' => $bot->senderID,
                            'username' => $bot->sendername,
                        ]);
                    }

                    if ($bot->conversationLeave == 2) {
                        $sql = "UPDATE    wcf" . WCF_N . "_conversation_to_user
                                SET        hideConversation = ?
                                WHERE    conversationID = ? AND participantID = ?";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute([Conversation::STATE_LEFT, $conversation->conversationID, $bot->senderID]);

                        UserStorageHandler::getInstance()->reset([$bot->senderID], 'conversationCount');
                        UserStorageHandler::getInstance()->reset([$bot->senderID], 'unreadConversationCount');

                        ConversationModificationLogHandler::getInstance()->leave($conversation);

                        ConversationEditor::updateParticipantCounts([$conversation->conversationID]);
                        ConversationEditor::updateParticipantSummaries([$conversation->conversationID]);
                        // no need to delete conversation, since there must be an participant at this time
                    }

                    // Reset to old user
                    WCF::getSession()->changeUser($oldUser, true);
                }
            } catch (SystemException $e) {
                // users may get lost; check sender again to abort
                if (!$bot->checkSender(true, true)) {
                    return false;
                }

                // report any other error und continue
                if ($bot->enableLog) {
                    $error = $defaultLanguage->get('wcf.acp.uzbot.log.notify.error') . ' ' . $e->getMessage();

                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'status' => 1,
                        'count' => 1,
                        'additionalData' => $error,
                    ]);
                }
            }
        }
    }
}
