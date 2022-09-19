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
namespace wcf\system\background\uzbot;

use wcf\data\user\User;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\notification\UzbotNotify;
use wcf\data\uzbot\notification\UzbotNotifyConversation;
use wcf\data\uzbot\type\UzbotType;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\UzbotUtils;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Schedules notifications for a bot.
 */
class NotifyScheduler
{
    /**
     * notification threshold / limit
     */
    const NOTIFY_LIMIT = 100;

    /**
     * schedules notification from / via background queue
     */
    public function schedule(array $data)
    {
        // get / preset data
        $bot = $data['bot'];
        $placeholders = $data['placeholders'];
        $affectedUserIDs = $data['affectedUserIDs'];
        $countToUserID = $data['countToUserID'];
        $notifyCount = 0;
        $notifyIDs = [];
        $jobData = [];

        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        // basic checks
        $comment = [];
        $tempBot = new Uzbot($bot->botID);
        if (!$tempBot->botID) {
            $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.bot.missing');
        }

        $notify = UzbotNotify::getNotifyByID($bot->notifyID);
        if (!$notify->notifyID) {
            $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.notify.missing');
        }

        $type = UzbotType::getTypeByID($bot->typeID);
        if (!$type->typeID) {
            $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.type.missing');
        }

        // check module
        if (!empty($notify->neededModule) && !\constant($notify->neededModule)) {
            $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.module.disabled');
        }

        if (\count($comment)) {
            if ($bot->enableLog) {
                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 2,
                    'additionalData' => \implode(', ', $comment),
                ]);
            }

            return;
        }

        // check languages and get texts
        $botContents = $bot->checkLanguages(true, true);
        if (empty($botContents)) {
            return;
        }

        // prepare texts; step through all languages, replace non-user and bot-specific placeholders
        $content = $subject = $tags = $teaser = $languages = [];
        foreach ($botContents as $key => $botContent) {
            // language
            if ($key == 0) {
                $languages[$key] = $defaultLanguage;
            } else {
                $languages[$key] = LanguageFactory::getInstance()->getLanguage($key);
            }
            if (!$languages[$key]->languageID) {
                $languages[$key] = $defaultLanguage;
            }

            // texts
            $subject[$key] = $teaser[$key] = '';
            $content[$key] = UzbotUtils::convertPlaceholders($botContent->content, $languages[$key], $placeholders);
            $subject[$key] = UzbotUtils::convertPlaceholders($botContent->subject, $languages[$key], $placeholders);
            $tags[$key] = \unserialize($botContent->tags);
            $teaser[$key] = UzbotUtils::convertPlaceholders($botContent->teaser, $languages[$key], $placeholders);
            $imageIDs[$key] = $botContent->imageID;
            $teaserImageIDs[$key] = $botContent->teaserImageID;
        }

        // preset notification
        $notification = new $notify->notifyFunction;

        // cover some special cases
        // report - has affected, but object owner / reporter may be guest - change to no affected to force notification
        if ($bot->typeDes == 'system_report') {
            if (empty($affectedUserIDs)) {
                $type->hasAffected = 0;
            }
        }
        // circular - has affected, but may be empty for single notification - change to no affected to force notification
        if ($bot->typeDes == 'system_circular') {
            if (empty($affectedUserIDs)) {
                $type->hasAffected = 0;
            }
        }

        // inactivity action delete
        if ($bot->typeDes == 'user_inactivity') {
            if (empty($affectedUserIDs)) {
                $type->hasAffected = 0;
            }
        }

        // if allow guest and no affected User
        if (empty($affectedUserIDs) && $type->allowGuest) {
            $type->hasAffected = 0;
        }

        // notify without receiver (article, post, thread etc.)
        if (!$notify->hasReceiver) {
            $receiver = null;

            // without affected users - single notification iaw bot's language setting
            // 0 = auto/default / -1 = all / >0 = specific language
            if (!$type->hasAffected) {
                //    $notifyCount = 0;

                // special case article
                if ($bot->notifyLanguageID == -1 && $notify->notifyTitle == 'article') {
                    $notifyContent = $notifySubject = $notifyTags = $notifyTeaser = $notifyImageIDs = $languageIDs = [];
                    foreach ($languages as $languageID => $language) {
                        if (!$languageID) {
                            continue;
                        }

                        $notifyContent[$languageID] = $content[$languageID];
                        $notifySubject[$languageID] = $subject[$languageID];
                        $notifyTeaser[$languageID] = $teaser[$languageID];
                        $notifyTags[$languageID] = $tags[$languageID];
                        $notifyImageIDs[$languageID] = $imageIDs[$languageID];
                        $notifyTeaserImageIDs[$languageID] = $teaserImageIDs[$languageID];
                        $languageIDs[] = $languageID;
                    }

                    $notification->sendMulti($bot, $notifyContent, $notifySubject, $notifyTeaser, $languageIDs, $receiver, $tags, $notifyImageIDs, $notifyTeaserImageIDs);
                    $notifyCount++;
                } else {
                    foreach ($languages as $languageID => $language) {
                        $send = 0;
                        if ($bot->notifyLanguageID == 0 && $languageID == 0) {
                            $send = 1;
                        }
                        if ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                            $send = 1;
                        }
                        if ($bot->notifyLanguageID == -1 && $languageID > 0) {
                            $send = 1;
                        }

                        if ($send) {
                            if ($notify->notifyTitle == 'article') {
                                $notification->send($bot, $content[$languageID], $subject[$languageID], $teaser[$languageID], $language, $receiver, $tags[$languageID], $imageIDs[$languageID], $teaserImageIDs[$languageID]);
                            } else {
                                $notification->send($bot, $content[$languageID], $subject[$languageID], $teaser[$languageID], $language, $receiver, $tags[$languageID]);
                            }
                            $notifyCount++;
                        }
                    }
                }

                if ($bot->enableLog && $notifyCount) {
                    UzbotLogEditor::create([
                        'bot' => $bot,
                        'count' => $notifyCount,
                        'status' => 0,
                        'testMode' => $bot->testMode ? 2 : 0,
                        'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count', ['count' => $notifyCount]),
                    ]);
                }
            }

            // with affected users - one notification per affected user iaw bot's language setting or one condensed notification in total
            if ($type->hasAffected && \count($affectedUserIDs)) {
                // condensed notification
                if ($type->canCondense && $bot->condenseEnable) {
                    //    $notifyCount = 0;

                    // get condense placeholders
                    $condenseComma = $condenseList = [];
                    foreach ($languages as $key => $language) {
                        $list = $comma = [];
                        foreach ($affectedUserIDs as $userID) {
                            $user = UserProfileRuntimeCache::getInstance()->getObject($userID);
                            if (!$user) {
                                continue;
                            }

                            // countToUser
                            $placeholders['user-count'] = 0;
                            if (isset($countToUserID[$userID])) {
                                $placeholders['user-count'] = $countToUserID[$userID];
                            }

                            $temp = UzbotUtils::convertPlaceholders($botContent->condense, $language, $placeholders, $user, true);
                            $comma[] = $temp;
                            $list[] = '<li>' . $temp . '</li>';
                        }

                        $condenseComma[$key] = \implode(', ', $comma);
                        $condenseList[$key] = '<ul>' . \implode(' ', $list) . '</ul>';

                        // reset placeholders and modify texts
                        //    $placeholders = [];
                        $placeholders['condense-comma'] = $condenseComma[$key];
                        $placeholders['condense-list'] = $condenseList[$key];

                        $content[$key] = UzbotUtils::convertPlaceholders($content[$key], $languages[$key], $placeholders, null);
                        $subject[$key] = UzbotUtils::convertPlaceholders($subject[$key], $languages[$key], $placeholders, null);
                        $teaser[$key] = UzbotUtils::convertPlaceholders($teaser[$key], $languages[$key], $placeholders, null);
                    }

                    // now send iaw notification setting

                    // special case article
                    if ($bot->notifyLanguageID == -1 && $notify->notifyTitle == 'article') {
                        $notifyContent = $notifySubject = $notifyTags = $notifyTeaser = $notifyImageIDs = $languageIDs = [];
                        foreach ($languages as $languageID => $language) {
                            if (!$languageID) {
                                continue;
                            }

                            $notifyContent[$languageID] = $content[$languageID];
                            $notifySubject[$languageID] = $subject[$languageID];
                            $notifyTeaser[$languageID] = $teaser[$languageID];
                            $notifyTags[$languageID] = $tags[$languageID];
                            $notifyImageIDs[$languageID] = $imageIDs[$languageID];
                            $notifyTeaserImageIDs[$languageID] = $teaserImageIDs[$languageID];
                            $languageIDs[] = $languageID;
                        }

                        $notification->sendMulti($bot, $notifyContent, $notifySubject, $notifyTeaser, $languageIDs, $receiver, $notifyTags, $notifyImageIDs, $notifyTeaserImageIDs);
                        $notifyCount++;
                    } else {
                        foreach ($languages as $languageID => $language) {
                            $send = 0;
                            if ($bot->notifyLanguageID == 0 && $languageID == 0) {
                                $send = 1;
                            }
                            if ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                $send = 1;
                            }
                            if ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                $send = 1;
                            }

                            if ($send) {
                                if ($notify->notifyTitle == 'article') {
                                    $notification->send($bot, $content[$languageID], $subject[$languageID], $teaser[$languageID], $language, $receiver, $tags[$languageID], $imageIDs[$languageID], $teaserImageIDs[$languageID]);
                                } else {
                                    $notification->send($bot, $content[$languageID], $subject[$languageID], $teaser[$languageID], $language, $receiver, $tags[$languageID]);
                                }

                                $notifyCount++;
                            }
                        }
                    }

                    if ($bot->enableLog && $notifyCount) {
                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'count' => $notifyCount,
                            'status' => 0,
                            'testMode' => $bot->testMode ? 2 : 0,
                            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count', ['count' => $notifyCount]),
                        ]);
                    }
                }
                // non condensed
                else {
                    // step through affected users
                    //    $notifyCount = 0;
                    //    $jobData = [];

                    foreach ($affectedUserIDs as $userID) {
                        $user = UserProfileRuntimeCache::getInstance()->getObject($userID);
                        if (!$user) {
                            continue;
                        }

                        // countToUser
                        $placeholders['user-count'] = 0;
                        if (isset($countToUserID[$userID])) {
                            $placeholders['user-count'] = $countToUserID[$userID];
                        }

                        // step through languages iaw notification

                        // special case article
                        if ($bot->notifyLanguageID == -1 && $notify->notifyTitle == 'article') {
                            $userContent = $userSubject = $userTags = $userTeaser = $userImageIDs = $languageIDs = [];
                            foreach ($languages as $languageID => $language) {
                                if (!$languageID) {
                                    continue;
                                }

                                // replace user-specific placeholders
                                $userContent[$languageID] = UzbotUtils::convertPlaceholders($content[$languageID], $languages[$languageID], $placeholders, $user, true);
                                $userSubject[$languageID] = UzbotUtils::convertPlaceholders($subject[$languageID], $languages[$languageID], $placeholders, $user, true);
                                $userTeaser[$languageID] = UzbotUtils::convertPlaceholders($teaser[$languageID], $languages[$languageID], $placeholders, $user, true);
                                $userTags[$languageID] = $tags[$languageID];
                                $userImageIDs[$languageID] = $imageIDs[$languageID];
                                $userTeaserImageIDs[$languageID] = $teaserImageIDs[$languageID];
                                $languageIDs[] = $languageID;
                            }

                            $notification->sendMulti($bot, $userContent, $userSubject, $userTeaser, $languageIDs, $receiver, $userTags, $userImageIDs, $userTeaserImageIDs);
                            $notifyCount++;
                        }
                        // not article
                        else {
                            foreach ($languages as $languageID => $language) {
                                // replace user-specific placeholders

                                $userContent = UzbotUtils::convertPlaceholders($content[$languageID], $languages[$languageID], $placeholders, $user, true);
                                $userSubject = UzbotUtils::convertPlaceholders($subject[$languageID], $languages[$languageID], $placeholders, $user, true);
                                $userTags = $tags[$languageID];
                                $userTeaser = UzbotUtils::convertPlaceholders($teaser[$languageID], $languages[$languageID], $placeholders, $user, true);
                                $userImageID = $imageIDs[$languageID];
                                $userTeaserImageID = $teaserImageIDs[$languageID];

                                // now send iaw notification setting
                                $send = 0;
                                if ($bot->notifyLanguageID == 0 && $languageID == 0) {
                                    $send = 1;
                                }
                                if ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                    $send = 1;
                                }
                                if ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                    $send = 1;
                                }

                                if ($send) {
                                    if ($notifyCount >= self::NOTIFY_LIMIT) {
                                        $jobData[] = [
                                            'bot' => $bot,
                                            'content' => $userContent,
                                            'subject' => $userSubject,
                                            'teaser' => $userTeaser,
                                            'languageID' => $language->languageID,
                                            'receiverID' => 0,
                                            'tags' => $userTags,
                                        ];
                                    } else {
                                        if ($notify->notifyTitle == 'article') {
                                            $notification->send($bot, $userContent, $userSubject, $userTeaser, $language, $receiver, $userTags, $userImageID, $userTeaserImageID);
                                        } else {
                                            $notification->send($bot, $userContent, $userSubject, $userTeaser, $language, $receiver, $userTags);
                                        }
                                    }

                                    $notifyCount++;
                                }
                            }
                        }
                    }

                    if ($bot->enableLog && $notifyCount) {
                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'count' => $notifyCount,
                            'status' => 0,
                            'testMode' => $bot->testMode ? 2 : 0,
                            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count', ['count' => $notifyCount]),
                        ]);
                    }
                }
            }
        }

        // notification has receivers - getting even more ;-)
        if ($notify->hasReceiver) {
            // without affected users - single notification iaw bot's language setting
            if (!$type->hasAffected) {
                $receiverIDs = $bot->getReceiverIDs([], true, false);

                if (\count($receiverIDs)) {
                    // special case conversation
                    if ($notify->notifyTitle == 'conversation' && $bot->conversationType == 1) {
                        // misuse $receiverTags for receiverIDs string
                        $receiverTags = \implode(',', $receiverIDs);
                        $receiver = null;

                        foreach ($languages as $languageID => $language) {
                            $send = 0;
                            if ($bot->notifyLanguageID == 0 && $languageID == $defaultLanguage->languageID) {
                                $send = 1;
                            } elseif ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                $send = 1;
                            } elseif ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                $send = 1;
                            }

                            if ($send) {
                                $notification->sendMulti($bot, $content[$languageID], $subject[$languageID], $teaser[$languageID], $language, $receiver, $receiverTags);
                                $notifyCount++;
                            }
                        }
                        if ($bot->enableLog && $notifyCount) {
                            $realCount = \ceil($notifyCount * \count($receiverIDs) / UzbotNotifyConversation::NOTIFY_CONVERSATION_MULTI);
                            UzbotLogEditor::create([
                                'bot' => $bot,
                                'count' => $realCount,
                                'status' => 0,
                                'testMode' => $bot->testMode ? 2 : 0,
                                'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count', ['count' => $realCount]),
                            ]);
                        }
                    } else {
                        foreach ($receiverIDs as $receiverID) {
                            $receiver = UserProfileRuntimeCache::getInstance()->getObject($receiverID);
                            if (!$receiver) {
                                continue;
                            }
                            $receiverLanguageID = $receiver->getLanguage()->languageID;

                            foreach ($languages as $languageID => $language) {
                                $send = 0;
                                if ($bot->notifyLanguageID == 0 && $languageID == $receiverLanguageID) {
                                    $send = 1;
                                } elseif ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                    $send = 1;
                                } elseif ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                    $send = 1;
                                }

                                if ($send) {
                                    $receiverContent = \str_replace('[receiver]', $receiver->username, $content[$languageID]);
                                    $receiverSubject = \str_replace('[receiver]', $receiver->username, $subject[$languageID]);
                                    $receiverTags = $tags[$languageID];
                                    $receiverTeaser = \str_replace('[receiver]', $receiver->username, $teaser[$languageID]);

                                    if ($notifyCount >= self::NOTIFY_LIMIT) {
                                        $jobData[] = [
                                            'bot' => $bot,
                                            'content' => $receiverContent,
                                            'subject' => $receiverSubject,
                                            'teaser' => $receiverTeaser,
                                            'languageID' => $language->languageID,
                                            'receiverID' => $receiver->userID,
                                            'tags' => $receiverTags,
                                        ];
                                    } else {
                                        $notification->send($bot, $receiverContent, $receiverSubject, $receiverTeaser, $language, $receiver, $receiverTags);
                                        $notifyCount++;
                                        $notifyIDs[] = $receiver->userID;
                                    }
                                }
                            }
                        }

                        if ($bot->enableLog && $notifyCount) {
                            UzbotLogEditor::create([
                                'bot' => $bot,
                                'count' => $notifyCount,
                                'status' => 0,
                                'testMode' => $bot->testMode ? 2 : 0,
                                'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count.id', ['count' => $notifyCount, 'id' => \implode(', ', $notifyIDs)]),
                            ]);
                        }
                    }
                }
            }

            // with affected users - one notification per affected user iaw bot's language setting or one condensed notification
            if ($type->hasAffected && \count($affectedUserIDs)) {
                // condensed notification
                if ($type->canCondense && $bot->condenseEnable) {
                    // all receivers receive one notification
                    $receiverIDs = $bot->getReceiverIDs($affectedUserIDs, true, false);

                    if (\count($receiverIDs)) {
                        // get condense placeholders
                        foreach ($languages as $languageID => $language) {
                            $list = $comma = [];
                            foreach ($affectedUserIDs as $userID) {
                                $user = UserProfileRuntimeCache::getInstance()->getObject($userID);
                                if (!$user) {
                                    continue;
                                }

                                // countToUser
                                $placeholders['user-count'] = 0;
                                if (isset($countToUserID[$userID])) {
                                    $placeholders['user-count'] = $countToUserID[$userID];
                                }

                                $temp = UzbotUtils::convertPlaceholders($botContent->condense, $language, $placeholders, $user, true);

                                $comma[] = $temp;
                                $list[] = '<li>' . $temp . '</li>';
                            }

                            $condenseComma = \implode(', ', $comma);
                            $condenseList = '<ul>' . \implode(' ', $list) . '</ul>';

                            $replace = [
                                '[condense-comma]' => $condenseComma,
                                '[condense-list]' => $condenseList,
                            ];

                            $content[$languageID] = \str_replace(\array_keys($replace), $replace, $content[$languageID]);
                            $subject[$languageID] = \str_replace(\array_keys($replace), $replace, $subject[$languageID]);
                            $teaser[$languageID] = \str_replace(\array_keys($replace), $replace, $teaser[$languageID]);
                        }

                        // now send iaw notification setting

                        // special case conversation
                        if ($notify->notifyTitle == 'conversation' && $bot->conversationType == 1) {
                            // misuse $receiverTags for receiverIDs string
                            $receiverTags = \implode(',', $receiverIDs);
                            $receiver = null;

                            foreach ($languages as $languageID => $language) {
                                $send = 0;
                                if ($bot->notifyLanguageID == 0 && $languageID == $defaultLanguage->languageID) {
                                    $send = 1;
                                } elseif ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                    $send = 1;
                                } elseif ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                    $send = 1;
                                }

                                if ($send) {
                                    $notification->sendMulti($bot, $content[$languageID], $subject[$languageID], $teaser[$languageID], $language, $receiver, $receiverTags);
                                    $notifyCount++;
                                }
                            }
                            if ($bot->enableLog && $notifyCount) {
                                $realCount = \ceil($notifyCount * \count($receiverIDs) / UzbotNotifyConversation::NOTIFY_CONVERSATION_MULTI);
                                UzbotLogEditor::create([
                                    'bot' => $bot,
                                    'count' => $realCount,
                                    'status' => 0,
                                    'testMode' => $bot->testMode ? 2 : 0,
                                    'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count', ['count' => $realCount]),
                                ]);
                            }
                        } else {
                            foreach ($receiverIDs as $receiverID) {
                                $receiver = UserProfileRuntimeCache::getInstance()->getObject($receiverID);
                                if (!$receiver) {
                                    continue;
                                }
                                $receiverLanguageID = $receiver->getLanguage()->languageID;

                                foreach ($languages as $languageID => $language) {
                                    $send = 0;
                                    if ($bot->notifyLanguageID == 0 && $languageID == $receiverLanguageID) {
                                        $send = 1;
                                    }
                                    if ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                        $send = 1;
                                    }
                                    if ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                        $send = 1;
                                    }

                                    if ($send) {
                                        $receiverContent = \str_replace('[receiver]', $receiver->username, $content[$languageID]);
                                        $receiverSubject = \str_replace('[receiver]', $receiver->username, $subject[$languageID]);
                                        $receiverTags = $tags[$languageID];
                                        $receiverTeaser = \str_replace('[receiver]', $receiver->username, $teaser[$languageID]);

                                        if ($notifyCount >= self::NOTIFY_LIMIT) {
                                            $jobData[] = [
                                                'bot' => $bot,
                                                'content' => $receiverContent,
                                                'subject' => $receiverSubject,
                                                'teaser' => $receiverTeaser,
                                                'languageID' => $language->languageID,
                                                'receiverID' => $receiver->userID,
                                                'tags' => $receiverTags,
                                            ];
                                        } else {
                                            $notification->send($bot, $receiverContent, $receiverSubject, $receiverTeaser, $language, $receiver, $receiverTags);
                                            $notifyCount++;
                                            $notifyIDs[] = $receiver->userID;
                                        }
                                    }
                                }
                            }
                        }

                        if ($bot->enableLog && $notifyCount) {
                            UzbotLogEditor::create([
                                'bot' => $bot,
                                'count' => $notifyCount,
                                'status' => 0,
                                'testMode' => $bot->testMode ? 2 : 0,
                                'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count.id', ['count' => $notifyCount, 'id' => \implode(', ', $notifyIDs)]),
                            ]);
                        }
                    }
                }

                // non condensed
                else {
                    // step through affected users
                    foreach ($affectedUserIDs as $userID) {
                        $user = UserProfileRuntimeCache::getInstance()->getObject($userID);
                        if (!$user) {
                            continue;
                        }

                        // countToUser
                        $placeholders['user-count'] = 0;
                        if (isset($countToUserID[$userID])) {
                            $placeholders['user-count'] = $countToUserID[$userID];
                        }

                        $receiverIDs = $bot->getReceiverIDs([$userID], true, false);
                        if (!\count($receiverIDs)) {
                            break;
                        }

                        // special case conversation
                        if ($notify->notifyTitle == 'conversation' && $bot->conversationType == 1) {
                            // misuse $receiverTags for receiverIDs string
                            $receiverTags = \implode(',', $receiverIDs);
                            $receiver = null;

                            foreach ($languages as $languageID => $language) {
                                $send = 0;
                                if ($bot->notifyLanguageID == 0 && $languageID == $defaultLanguage->languageID) {
                                    $send = 1;
                                } elseif ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                    $send = 1;
                                } elseif ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                    $send = 1;
                                }

                                if ($send) {
                                    // replace user-specific placeholders
                                    $userContent = UzbotUtils::convertPlaceholders($content[$languageID], $languages[$languageID], $placeholders, $user, true);
                                    $userSubject = UzbotUtils::convertPlaceholders($subject[$languageID], $languages[$languageID], $placeholders, $user, true);
                                    $userTeaser = UzbotUtils::convertPlaceholders($teaser[$languageID], $languages[$languageID], $placeholders, $user, true);

                                    $notification->sendMulti($bot, $userContent, $userSubject, $userTeaser, $language, $receiver, $receiverTags);
                                    $notifyCount++;
                                }
                            }
                            if ($bot->enableLog && $notifyCount) {
                                $realCount = \ceil($notifyCount * \count($receiverIDs) / UzbotNotifyConversation::NOTIFY_CONVERSATION_MULTI);
                                UzbotLogEditor::create([
                                    'bot' => $bot,
                                    'count' => $realCount,
                                    'status' => 0,
                                    'testMode' => $bot->testMode ? 2 : 0,
                                    'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count', ['count' => $realCount]),
                                ]);
                            }
                        } else {
                            foreach ($receiverIDs as $receiverID) {
                                $receiver = UserProfileRuntimeCache::getInstance()->getObject($receiverID);
                                if (!$receiver) {
                                    continue;
                                }
                                $receiverLanguageID = $receiver->getLanguage()->languageID;

                                foreach ($languages as $languageID => $language) {
                                    $send = 0;
                                    if ($bot->notifyLanguageID == 0 && $languageID == $receiverLanguageID) {
                                        $send = 1;
                                    }
                                    if ($bot->notifyLanguageID > 0 && $languageID == $bot->notifyLanguageID) {
                                        $send = 1;
                                    }
                                    if ($bot->notifyLanguageID == -1 && $languageID > 0) {
                                        $send = 1;
                                    }

                                    if ($send) {
                                        // replace user-specific placeholders
                                        $userContent = UzbotUtils::convertPlaceholders($content[$languageID], $languages[$languageID], $placeholders, $user, true);
                                        $userSubject = UzbotUtils::convertPlaceholders($subject[$languageID], $languages[$languageID], $placeholders, $user, true);
                                        $userTeaser = UzbotUtils::convertPlaceholders($teaser[$languageID], $languages[$languageID], $placeholders, $user, true);

                                        // replace receiver placeholder
                                        $receiverContent = \str_replace('[receiver]', $receiver->username, $userContent);
                                        $receiverSubject = \str_replace('[receiver]', $receiver->username, $userSubject);
                                        $receiverTags = $tags[$languageID];
                                        $receiverTeaser = \str_replace('[receiver]', $receiver->username, $userTeaser);

                                        if ($notifyCount >= self::NOTIFY_LIMIT) {
                                            $jobData[] = [
                                                'bot' => $bot,
                                                'content' => $receiverContent,
                                                'subject' => $receiverSubject,
                                                'teaser' => $receiverTeaser,
                                                'languageID' => $language->languageID,
                                                'receiverID' => $receiver->userID,
                                                'tags' => $receiverTags,
                                            ];
                                        } else {
                                            $notification->send($bot, $receiverContent, $receiverSubject, $receiverTeaser, $language, $receiver, $receiverTags);
                                            $notifyCount++;
                                            $notifyIDs[] = $receiver->userID;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($bot->enableLog && $notifyCount) {
                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'count' => $notifyCount,
                            'status' => 0,
                            'testMode' => $bot->testMode ? 2 : 0,
                            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count.id', ['count' => $notifyCount, 'id' => \implode(', ', $notifyIDs)]),
                        ]);
                    }
                }
            }
        }

        // check for jobs, pack NOTIFY_LIMIT
        if (\count($jobData)) {
            $count = 0;
            while (1) {
                $data = \array_slice($jobData, $count * self::NOTIFY_LIMIT, self::NOTIFY_LIMIT);

                if (empty($data)) {
                    break;
                }
                $count++;

                $job = new NotifyBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->enqueueIn([$job]);
                BackgroundQueueHandler::getInstance()->forceCheck();
                //    BackgroundQueueHandler::getInstance()->performJob($job);
            }
        }
    }
}
