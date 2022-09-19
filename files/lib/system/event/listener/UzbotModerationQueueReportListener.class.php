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

use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\User;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\exception\SystemException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Listen to reports by users for Bot
 */
class UzbotModerationQueueReportListener implements IParameterizedEventListener
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

        // need action report
        if ($eventObj->getActionName() != 'report') {
            return;
        }

        // need valid bots
        $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_report']);
        if (!\count($bots)) {
            return;
        }

        // preset data; users, object and placeholders
        $report = [];
        $params = $eventObj->getParameters();

        $report['message'] = $params['message'];

        try {
            $definition = ObjectTypeCache::getInstance()->getDefinitionByName('com.woltlab.wcf.moderation.report');
            if ($definition->definitionID) {
                $sql = "SELECT    className
                        FROM    wcf" . WCF_N . "_object_type
                        WHERE    definitionID = ?
                                AND objectType = ?";
                $statement = WCF::getDB()->prepareStatement($sql, 1);
                $statement->execute([$definition->definitionID, $params['objectType']]);
                $row = $statement->fetchArray();

                $handler = new $row['className'];
                $object = $handler->getReportedObject($params['objectID']);
                $report['link'] = $object->getLink();
                $report['title'] = $object->getTitle();
                if (\method_exists($object, 'getMessage')) {
                    $report['text'] = $object->getMessage();
                } else {
                    $report['text'] = '';
                }
                $report['userID'] = $object->userID ?: '0';
            }
        } catch (SystemException $e) {
            if (!isset($report['link'])) {
                $report['link'] = '?';
            }
            if (!isset($report['title'])) {
                $report['title'] = '?';
            }
            if (!isset($report['text'])) {
                $report['text'] = '?';
            }
            if (!isset($report['userID'])) {
                $report['userID'] = 0;
            }
        }

        // step through bots
        foreach ($bots as $bot) {
            // preset more data
            $affectedUserIDs = $placeholders = [];

            // set affected user
            $reporter = WCF::getUser();
            $user = new User($report['userID']);

            if ($bot->changeAffected) {
                if ($reporter->userID) {
                    $affectedUserIDs[] = $reporter->userID;
                }
            } else {
                if ($user->userID) {
                    $affectedUserIDs[] = $user->userID;
                }
            }

            // set placeholders
            $placeholders['count'] = 1;
            $placeholders['object-link'] = $report['link'];
            $placeholders['object-text'] = $report['text'];
            $placeholders['object-title'] = $report['title'];
            $placeholders['object-userid'] = $report['userID'];
            $placeholders['object-username'] = $placeholders['object-userlink'] = 'wcf.user.guest';
            if ($report['userID']) {
                $placeholders['object-username'] = $user->username;
                $placeholders['object-userlink'] = $user->getLink();
                $placeholders['object-userlink2'] = StringUtil::getAnchorTag($user->getLink(), $user->username);
            } else {
                $placeholders['translate'] = ['object-username', 'object-userlink'];
            }

            $placeholders['report-text'] = $report['message'];

            if ($reporter->userID) {
                $placeholders['report-userid'] = $reporter->userID;
                $placeholders['report-username'] = $reporter->username;
                $placeholders['report-userlink'] = $reporter->getLink();
                $placeholders['report-userlink2'] = StringUtil::getAnchorTag($reporter->getLink(), $reporter->username);
            } else {
                $placeholders['report-userid'] = 0;
                $placeholders['report-username'] = $placeholders['report-userlink'] = 'wcf.user.guest';
                $placeholders['translate'][] = 'report-username';
                $placeholders['translate'][] = 'report-userlink';
            }

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
