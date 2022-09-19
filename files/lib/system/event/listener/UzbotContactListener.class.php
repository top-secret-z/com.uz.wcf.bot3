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

use wcf\data\user\User;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Listen to Article creation for Bot
 */
class UzbotContactListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        // check modules
        if (!MODULE_UZBOT) {
            return;
        }
        if (!MODULE_CONTACT_FORM) {
            return;
        }

        // only send
        if ($eventObj->getActionName() != 'send') {
            return;
        }

        // Read all active, valid activity bots, abort if none
        $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_contact']);
        if (!\count($bots)) {
            return;
        }

        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        $user = WCF::getUser();

        // collect data
        $affectedUserIDs = $countToUserID = $placeholders = [];
        $params = $eventObj->getParameters();
        $optionHandler = $params['optionHandler'];

        $receiver = new User($params['recipientID']);
        $affectedUserIDs[] = $receiver->userID;
        $countToUserID[$receiver->userID] = 1;

        $placeholders['count'] = 1;
        $placeholders['contact-email'] = $params['email'] ?? '';
        $placeholders['contact-guest'] = $user->userID ? 'wcf.acp.uzbot.yes' : 'wcf.acp.uzbot.no';
        $placeholders['contact-name'] = $params['name'] ?? '';
        $placeholders['contact-username'] = $user->userID ? $user->username : 'wcf.user.guest';
        $placeholders['contact-userlink'] = $user->userID ? $user->getLink() : 'wcf.user.guest';

        $text = [];
        foreach ($optionHandler->getOptions() as $option) {
            $object = $option['object'];
            $text[] = $object->getLocalizedName($defaultLanguage) . ': ' . $object->getFormattedOptionValue(true);
        }
        $placeholders['contact-text'] = \implode('<br><br>', $text);

        $placeholders['translate'] = ['contact-guest', 'contact-username', 'contact-userlink'];

        foreach ($bots as $bot) {
            // log action
            if ($bot->enableLog) {
                if (!$bot->testMode) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => 1,
                        'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
                            'total' => 1,
                            'userIDs' => \implode(', ', $affectedUserIDs),
                        ]),
                    ]);
                } else {
                    $result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
                        'objects' => 1,
                        'users' => \count($affectedUserIDs),
                        'userIDs' => \implode(', ', $affectedUserIDs),
                    ]);
                    if (\mb_strlen($result) > 64000) {
                        $result = \mb_substr($result, 0, 64000) . ' ...';
                    }
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => 1,
                        'testMode' => 1,
                        'additionalData' => \serialize(['', '', $result]),
                    ]);
                }
            }

            // check for and prepare notification
            $notify = $bot->checkNotify(true, true);
            if ($notify === null) {
                continue;
            }

            // send to scheduler
            $data = [
                'bot' => $bot,
                'placeholders' => $placeholders,
                'affectedUserIDs' => $affectedUserIDs,
                'countToUserID' => $countToUserID,
            ];

            $job = new NotifyScheduleBackgroundJob($data);
            BackgroundQueueHandler::getInstance()->performJob($job);
        }
    }
}
