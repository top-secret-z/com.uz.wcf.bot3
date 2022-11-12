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

use wcf\data\comment\Comment;
use wcf\data\comment\CommentEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\Uzbot;
use wcf\system\comment\manager\UserProfileCommentManager;
use wcf\system\exception\SystemException;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\LanguageFactory;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\user\notification\object\CommentUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;

/**
 * Creates wall comments for Bot
 */
class UzbotNotifyComment
{
    public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null)
    {
        // preset some data
        $objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName('com.woltlab.wcf.comment.commentableContent', 'com.woltlab.wcf.user.profileComment');
        $objectType = ObjectTypeCache::getInstance()->getObjectType($objectTypeID);
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        // test mode
        if ($bot->testMode) {
            $subject = $teaser = '';
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

            return true;
        }

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($content, 'com.woltlab.wcf.comment', 0);

        $commentData = [
            'objectTypeID' => $objectTypeID,
            'objectID' => $receiver->userID,
            'time' => TIME_NOW,
            'userID' => $bot->senderID,
            'username' => $bot->sendername,
            'message' => $htmlInputProcessor->getHtml(),
            'isDisabled' => 0,
            'enableHtml' => 1,
            'responses' => 0,
            'responseIDs' => \serialize([]),
            'isUzbot' => 1,
        ];

        try {
            $createdComment = CommentEditor::create($commentData);

            UserProfileCommentManager::getInstance()->updateCounter($receiver->userID, 1);

            // fire activity event
            if ($bot->commentActivity) {
                $objectType = ObjectTypeCache::getInstance()->getObjectType($objectTypeID);
                if (UserActivityEventHandler::getInstance()->getObjectTypeID($objectType->objectType . '.recentActivityEvent')) {
                    UserActivityEventHandler::getInstance()->fireEvent($objectType->objectType . '.recentActivityEvent', $createdComment->commentID, null, $bot->senderID);
                }
            }

            // fire notification event
            if (UserNotificationHandler::getInstance()->getObjectTypeID($objectType->objectType . '.notification')) {
                $notificationObject = new CommentUserNotificationObject($createdComment);
                $notificationObjectType = UserNotificationHandler::getInstance()->getObjectTypeProcessor($objectType->objectType . '.notification');

                $userID = $notificationObjectType->getOwnerID($createdComment->commentID);
                if ($userID != $bot->senderID) {
                    UserNotificationHandler::getInstance()->fireEvent('comment', $objectType->objectType . '.notification', $notificationObject, [$userID], ['objectUserID' => $userID]);
                }
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

        return true;
    }

    /**
     * sends comment via background queue
     */
    public function deliver(array $data)
    {
        $commentData = $data['commentData'];

        $createdComment = CommentEditor::create($commentData);

        UserProfileCommentManager::getInstance()->updateCounter($commentData['objectID'], 1);

        // Fire activity event
        if ($data['activity']) {
            $objectType = ObjectTypeCache::getInstance()->getObjectType($commentData['objectTypeID']);
            if (UserActivityEventHandler::getInstance()->getObjectTypeID($objectType->objectType . '.recentActivityEvent')) {
                UserActivityEventHandler::getInstance()->fireEvent($objectType->objectType . '.recentActivityEvent', $createdComment->commentID, null, $commentData['userID']);
            }
        }

        // fire notification event
        if (UserNotificationHandler::getInstance()->getObjectTypeID($objectType->objectType . '.notification')) {
            $notificationObject = new CommentUserNotificationObject($createdComment);
            $notificationObjectType = UserNotificationHandler::getInstance()->getObjectTypeProcessor($objectType->objectType . '.notification');

            $userID = $notificationObjectType->getOwnerID($createdComment->commentID);
            if ($userID != $commentData['userID']) {
                UserNotificationHandler::getInstance()->fireEvent('comment', $objectType->objectType . '.notification', $notificationObject, [$userID], ['objectUserID' => $userID]);
            }
        }
    }
}
