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

use wcf\data\cronjob\Cronjob;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\package\PackageUpdateDispatcher;
use wcf\system\WCF;

/**
 * Updates cronjob for Bot
 */
class UzbotUpdatesCronjob extends AbstractCronjob
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
        $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_update']);
        if (!\count($bots)) {
            return;
        }

        // Step through all bots and get updates
        foreach ($bots as $bot) {
            // get data
            $placeholders = $temp = [];
            $placeholders['count'] = 0;
            $placeholders['updates'] = '';

            $updates = PackageUpdateDispatcher::getInstance()->getAvailableUpdates();
            if (\count($updates)) {
                foreach ($updates as $update) {
                    $placeholders['count']++;
                    $temp[] = $update['packageName'];
                }

                $placeholders['updates'] = \implode(', ', $temp);
            }

            // log result
            if ($bot->enableLog) {
                if (!$bot->testMode) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $placeholders['count'],
                        'additionalData' => '',
                    ]);
                } else {
                    $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
                    $result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
                        'objects' => $placeholders['count'],
                        'users' => 0,
                        'userIDs' => '',
                    ]);
                    if (\mb_strlen($result) > 64000) {
                        $result = \mb_substr($result, 0, 64000) . ' ...';
                    }
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $placeholders['count'],
                        'testMode' => 1,
                        'additionalData' => \serialize(['', '', $result]),
                    ]);
                }
            }

            if (\count($updates)) {
                // send to scheduler
                $notify = $bot->checkNotify(true, true);
                if ($notify === null) {
                    continue;
                }

                $data = [
                    'bot' => $bot,
                    'placeholders' => $placeholders,
                    'affectedUserIDs' => [],
                    'countToUserID' => [],
                ];

                $job = new NotifyScheduleBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->performJob($job);
            }
        }
    }
}
