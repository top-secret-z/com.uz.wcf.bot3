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
namespace wcf\system\event\listener;

use wcf\data\user\infraction\warning\UserInfractionWarning;
use wcf\data\user\User;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Listen to bans of users for Bot
 */
class UzbotUserBanListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        // need module
        if (!MODULE_UZBOT) {
            return;
        }

        if ($className == 'wcf\system\infraction\suspension\BanSuspensionAction') {
            // suspend
            if ($eventName == 'suspend') {
                // need valid bots
                $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_ban']);
                if (!\count($bots)) {
                    return;
                }

                // set data
                $affectedUserIDs = $placeholders = [];
                $suspension = $parameters['suspension'];
                $userSuspension = $parameters['userSuspension'];
                $warning = new UserInfractionWarning($userSuspension->warningID);

                $affectedUserIDs[] = $userSuspension->userID;
                $banner = WCF::getUser();

                $placeholders['count'] = \count($affectedUserIDs);
                $placeholders['ban-reason'] = $warning->reason;
                $placeholders['ban-expire'] = $suspension->expires ? \date('Y-m-d', TIME_NOW + $suspension->expires) : 'wcf.uzbot.system.never';
                $placeholders['translate'][] = 'ban-expire';

                if ($banner->userID) {
                    $placeholders['ban-userid'] = $banner->userID;
                    $placeholders['ban-username'] = $banner->username;
                    $placeholders['ban-userlink'] = $banner->getLink();
                    $placeholders['ban-userlink2'] = StringUtil::getAnchorTag($banner->getLink(), $banner->username);
                } else {
                    $placeholders['ban-userid'] = 0;
                    $placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
                    $placeholders['translate'][] = 'ban-username';
                    $placeholders['translate'][] = 'ban-userlink';
                }

                // step through bots
                foreach ($bots as $bot) {
                    // send to scheduler
                    $data = [
                        'bot' => $bot,
                        'placeholders' => $placeholders,
                        'affectedUserIDs' => $affectedUserIDs,
                        'countToUserID' => [],
                    ];

                    $job = new NotifyScheduleBackgroundJob($data);
                    BackgroundQueueHandler::getInstance()->performJob($job);
                }

                return;
            }
            if ($eventName == 'unsuspend') {
                // need valid bots
                $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_unban']);
                if (!\count($bots)) {
                    return;
                }

                // set data
                $affectedUserIDs = $placeholders = [];
                $userSuspension = $parameters['userSuspension'];
                $affectedUserIDs[] = $userSuspension->userID;

                $placeholders['count'] = \count($affectedUserIDs);
                if ($userSuspension->revoker) {
                    $revoker = new User($userSuspension->revoker);
                    $placeholders['ban-userid'] = $revoker->userID;
                    $placeholders['ban-username'] = $revoker->username;
                    $placeholders['ban-userlink'] = $revoker->getLink();
                    $placeholders['ban-userlink2'] = StringUtil::getAnchorTag($revoker->getLink(), $revoker->username);
                } else {
                    $placeholders['ban-userid'] = 0;
                    $placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
                    $placeholders['translate'][] = 'ban-username';
                    $placeholders['translate'][] = 'ban-userlink';
                }

                // step through bots
                foreach ($bots as $bot) {
                    // send to scheduler
                    $data = [
                        'bot' => $bot,
                        'placeholders' => $placeholders,
                        'affectedUserIDs' => $affectedUserIDs,
                        'countToUserID' => [],
                    ];

                    $job = new NotifyScheduleBackgroundJob($data);
                    BackgroundQueueHandler::getInstance()->performJob($job);
                }

                return;
            }

            return;
        }

        // need action ban or unban
        $action = $eventObj->getActionName();

        if ($action == 'ban') {
            // need valid bots
            $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_ban']);
            if (!\count($bots)) {
                return;
            }

            // get data
            $affectedUserIDs = $placeholders = [];
            $affectedUserIDs = $eventObj->getObjectIDs();
            $banner = WCF::getUser();

            // set placeholders
            $placeholders['count'] = \count($affectedUserIDs);
            $params = $eventObj->getParameters();

            $placeholders['ban-reason'] = $params['banReason'];
            $placeholders['ban-expire'] = $params['banExpires'] ?: 'wcf.uzbot.system.never';
            $placeholders['translate'][] = 'ban-expire';

            if ($banner->userID) {
                $placeholders['ban-userid'] = $banner->userID;
                $placeholders['ban-username'] = $banner->username;
                $placeholders['ban-userlink'] = $banner->getLink();
                $placeholders['ban-userlink2'] = StringUtil::getAnchorTag($banner->getLink(), $banner->username);
            } else {
                $placeholders['ban-userid'] = 0;
                $placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
                $placeholders['translate'][] = 'ban-username';
                $placeholders['translate'][] = 'ban-userlink';
            }

            // step through bots
            foreach ($bots as $bot) {
                // send to scheduler
                $data = [
                    'bot' => $bot,
                    'placeholders' => $placeholders,
                    'affectedUserIDs' => $affectedUserIDs,
                    'countToUserID' => [],
                ];

                $job = new NotifyScheduleBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->performJob($job);
            }
        }

        if ($action == 'unban') {
            // need valid bots
            $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_unban']);
            if (!\count($bots)) {
                return;
            }

            // get data
            $affectedUserIDs = $placeholders = [];
            $affectedUserIDs = $eventObj->getObjectIDs();
            $banner = WCF::getUser();

            // set placeholders
            $placeholders['count'] = \count($affectedUserIDs);

            if ($banner->userID) {
                $placeholders['ban-userid'] = $banner->userID;
                $placeholders['ban-username'] = $banner->username;
                $placeholders['ban-userlink'] = $banner->getLink();
                $placeholders['ban-userlink2'] = StringUtil::getAnchorTag($banner->getLink(), $banner->username);
            } else {
                $placeholders['ban-userid'] = 0;
                $placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
                $placeholders['translate'][] = 'ban-username';
                $placeholders['translate'][] = 'ban-userlink';
            }

            // step through bots
            foreach ($bots as $bot) {
                // send to scheduler
                $data = [
                    'bot' => $bot,
                    'placeholders' => $placeholders,
                    'affectedUserIDs' => $affectedUserIDs,
                    'countToUserID' => [],
                ];

                $job = new NotifyScheduleBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->performJob($job);
            }
        }
    }
}
