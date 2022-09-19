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
namespace wcf\system\cronjob;

use PDO;
use wcf\data\conversation\ConversationAction;
use wcf\data\conversation\ConversationList;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\UserList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Conversation Cleanup for Bot
 */
class UzbotConversationCronjob extends AbstractCronjob
{
    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        if (!MODULE_UZBOT) {
            return;
        }

        // Read all active, valid activity bots, abort if none
        $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_conversation']);
        if (empty($bots)) {
            return;
        }

        // get language for log
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        // Step through all bots and get matching conversations / users
        foreach ($bots as $bot) {
            // preset data
            $conversationIDs = $affectedUserIDs = $countToUserID = [];
            $count = 0;

            // get users iaw conditions
            $userList = new UserList();
            $userList->getConditionBuilder()->add("user_table.userID IN (SELECT DISTINCT userID FROM wcf" . WCF_N . "_conversation)");

            $conditions = $bot->getUserConditions();
            foreach ($conditions as $condition) {
                $condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
            }

            $botConditions = $bot->getUserBotConditions();
            foreach ($botConditions as $condition) {
                $condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
            }

            $userList->readObjects();
            $temp = $userList->getObjects();
            $userIDs = [];
            if (\count($temp)) {
                foreach ($temp as $user) {
                    $userIDs[] = $user->userID;
                }
            }

            // find matching conversations
            if (\count($userIDs)) {
                $conversationList = new ConversationList();
                if ($bot->conversationNoAnswers) {
                    $conversationList->getConditionBuilder()->add('conversation.replies = ?', [0]);
                }
                if ($bot->conversationDaysAfter == 'reply') {
                    $conversationList->getConditionBuilder()->add('conversation.lastPostTime < ?', [TIME_NOW - $bot->conversationDays * 86400]);
                } else {
                    $conversationList->getConditionBuilder()->add('conversation.time < ?', [TIME_NOW - $bot->conversationDays * 86400]);
                }
                // skip unless required
                if (!empty($conditions) || !empty($botConditions)) {
                    $conversationList->getConditionBuilder()->add('conversation.userID IN (?)', [$userIDs]);
                }

                // without labels
                if ($bot->conversationNoLabels) {
                    $sql = "SELECT DISTINCT    conversationID
                            FROM            wcf" . WCF_N . "_conversation_label_to_object";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($ids)) {
                        $conversationList->getConditionBuilder()->add('conversation.conversationID NOT IN (?)', [$ids]);
                    }
                }

                // limit
                if (!$bot->testMode) {
                    $conversationList->sqlLimit = UZBOT_DATA_LIMIT_COMMENT;
                } else {
                    $conversationList->sqlLimit = 1000;
                }

                $conversationList->readObjects();
                $conversations = $conversationList->getObjects();

                $count = \count($conversations);
                if ($count) {
                    foreach ($conversations as $conversation) {
                        $conversationIDs[] = $conversation->conversationID;
                        $partIDs = $conversation->getParticipantIDs();

                        foreach ($partIDs as $partID) {
                            $affectedUserIDs[] = $partID;
                            if (isset($countToUserID[$partID])) {
                                $countToUserID[$partID]++;
                            } else {
                                $countToUserID[$partID] = 1;
                            }
                        }
                    }

                    $affectedUserIDs = \array_unique($affectedUserIDs);

                    // delete conversations and update counts, unless test mode
                    if (!$bot->testMode) {
                        $action = new ConversationAction($conversationIDs, 'delete');
                        $action->executeAction();

                        UserStorageHandler::getInstance()->reset($affectedUserIDs, 'conversationCount');
                        UserStorageHandler::getInstance()->reset($affectedUserIDs, 'unreadConversationCount');
                    }
                }
            }

            // log action
            if ($bot->enableLog) {
                if (!$bot->testMode) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $count,
                        'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.conversation', [
                            'conversations' => $count,
                            'users' => \count($affectedUserIDs),
                            'userIDs' => \implode(', ', $affectedUserIDs),
                        ]),
                    ]);
                } else {
                    $result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
                        'objects' => $count,
                        'users' => \count($affectedUserIDs),
                        'userIDs' => \implode(', ', $affectedUserIDs),
                    ]);
                    if (\mb_strlen($result) > 64000) {
                        $result = \mb_substr($result, 0, 64000) . ' ...';
                    }
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $count,
                        'testMode' => 1,
                        'additionalData' => \serialize(['', '', $result]),
                    ]);
                }
            }

            // check for and prepare notification, must have deleted conversations
            if (!$bot->notifyID || !$count) {
                continue;
            }
            $notify = $bot->checkNotify(true, true);
            if ($notify === null) {
                continue;
            }

            $placeholders = [];
            $placeholders['count'] = $count;

            // test mode
            $testUserIDs = $testToUserIDs = [];
            if (\count($affectedUserIDs)) {
                if ($bot->condenseEnable) {
                    $testUserIDs = $affectedUserIDs;
                    $testToUserIDs = $countToUserID;
                } else {
                    $userID = \reset($affectedUserIDs);
                    $testUserIDs[] = $userID;
                    $testToUserIDs[$userID] = $countToUserID[$userID];
                }
            }

            // send to scheduler
            $data = [
                'bot' => $bot,
                'placeholders' => $placeholders,
                'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
                'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs,
            ];

            $job = new NotifyScheduleBackgroundJob($data);
            BackgroundQueueHandler::getInstance()->performJob($job);
        }
    }
}
