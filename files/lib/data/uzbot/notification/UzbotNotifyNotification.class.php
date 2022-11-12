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

use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\Uzbot;
use wcf\system\user\notification\object\UzbotNotifyUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\util\MessageUtil;

/**
 * Creates system notification for Bot
 */
class UzbotNotifyNotification
{
    public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null)
    {
        // prepare text
        $content = MessageUtil::stripCrap($content);

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

            return;
        }

        UserNotificationHandler::getInstance()->fireEvent(
            'notify',
            'com.uz.wcf.bot3',
            new UzbotNotifyUserNotificationObject(new Uzbot($bot->botID)),
            [$receiver->userID],
            [
                'message' => $content,
                'receiverID' => $receiver->userID,
                'botID' => $bot->botID,
            ]
        );
    }
}
