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
namespace wcf\data\uzbot;

use wcf\data\DatabaseObject;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\uzbot\content\UzbotContent;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\notification\UzbotNotify;
use wcf\system\condition\ConditionHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\request\IRouteController;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

use const PREG_OFFSET_CAPTURE;

/**
 * Represents a Bot
 */
class Uzbot extends DatabaseObject implements IRouteController
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'uzbot';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'botID';

    /**
     * bot content grouped by language id
     */
    public $botContents;

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return WCF::getLanguage()->get($this->botTitle);
    }

    /**
     * Returns the active content version.
     */
    public function getBotContent($languageID)
    {
        $this->getBotContents();

        if ($this->isMultilingual) {
            if (isset($this->botContents[$languageID])) {
                return $this->botContents[$languageID];
            }
        } else {
            if (!empty($this->botContents[0])) {
                return $this->botContents[0];
            }
        }

        return null;
    }

    /**
     * Returns the bot's contents.
     */
    public function getBotContents()
    {
        if ($this->botContents === null) {
            $this->botContents = [];

            $sql = "SELECT    *
                    FROM    wcf" . WCF_N . "_uzbot_content
                    WHERE    botID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$this->botID]);
            while ($row = $statement->fetchArray()) {
                $this->botContents[$row['languageID'] ?: 0] = new UzbotContent(null, $row);
            }
        }

        return $this->botContents;
    }

    /**
     * Returns the active content string.
     */
    public function getBotString($length = 255)
    {
        $this->getBotContents();

        $string = '';
        if ($this->isMultilingual) {
            if (isset($this->botContents[WCF::getLanguage()->languageID])) {
                $string = $this->botContents[WCF::getLanguage()->languageID]->content;
            }
        } else {
            if (!empty($this->botContents[0])) {
                $string = $this->botContents[0]->content;
            }
        }

        return StringUtil::truncateHTML(StringUtil::stripHTML($string), $length);
    }

    /**
     * Returns the receiver conditions of the bot.
     */
    public function getReceiverConditions()
    {
        return ConditionHandler::getInstance()->getConditions('com.uz.wcf.bot.condition.receiver', $this->botID);
    }

    /**
     * Returns the user conditions of the bot.
     */
    public function getUserConditions()
    {
        return ConditionHandler::getInstance()->getConditions('com.uz.wcf.bot.condition.user', $this->botID);
    }

    /**
     * Returns the user bot conditions of the bot.
     */
    public function getUserBotConditions()
    {
        return ConditionHandler::getInstance()->getConditions('com.uz.wcf.bot.condition.userBot', $this->botID);
    }

    /**
     * Returns the receiverIDs of a bot
     * Email BCC and CC are not included
     */
    public function getReceiverIDs(array $affectedIDs = [], $log = false, $disable = false)
    {
        $userIDs = [];

        if (\count($affectedIDs) && $this->receiverAffected) {
            $userIDs = $affectedIDs;
        }

        if (!empty($this->receiverNames)) {
            $usernames = ArrayUtil::trim(\explode(',', $this->receiverNames));
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.username IN (?)', [$usernames]);
            $userList->readObjectIDs();
            $userIDs = \array_merge($userIDs, $userList->getObjectIDs());
        }

        $groupIDs = \unserialize($this->receiverGroupIDs);
        if (\count($groupIDs)) {
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.userID IN (SELECT userID FROM wcf' . WCF_N . '_user_to_group WHERE groupID IN (?))', [$groupIDs]);
            $userList->readObjectIDs();
            $userIDs = \array_merge($userIDs, $userList->getObjectIDs());
        }

        $userIDs = \array_unique($userIDs);

        if (\count($userIDs)) {
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.userID IN (?)', [$userIDs]);

            // add filter
            $conditions = $this->getReceiverConditions();
            foreach ($conditions as $condition) {
                $condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
            }

            $userList->readObjectIDs();
            $userIDs = $userList->getObjectIDs();
        }

        if (!\count($userIDs)) {
            if ($disable) {
                $editor = new UzbotEditor($this);
                $editor->update(['isDisabled' => 1]);
                UzbotEditor::resetCache();

                if ($log && $this->enableLog) {
                    UzbotLogEditor::create([
                        'bot' => $this,
                        'status' => 2,
                        'additionalData' => 'wcf.acp.uzbot.error.noReceiver',
                    ]);

                    UzbotLogEditor::create([
                        'bot' => $this,
                        'status' => 2,
                        'additionalData' => 'wcf.acp.uzbot.error.disabled',
                    ]);
                }
            } else {
                if ($log && $this->enableLog) {
                    UzbotLogEditor::create([
                        'bot' => $this,
                        'status' => 1,
                        'additionalData' => 'wcf.acp.uzbot.error.noReceiver',
                    ]);
                }
            }
        }

        return $userIDs;
    }

    /**
     * Check for valid sender
     */
    public function checkSender($log = false, $disable = false)
    {
        if (!$this->senderID) {
            if ($log && $this->enableLog) {
                UzbotLogEditor::create([
                    'bot' => $this,
                    'status' => 2,
                    'additionalData' => 'wcf.acp.uzbot.error.noSender',
                ]);
            }

            if ($disable) {
                $editor = new UzbotEditor($this);
                $editor->update(['isDisabled' => 1]);
                UzbotEditor::resetCache();

                if ($this->enableLog) {
                    UzbotLogEditor::create([
                        'bot' => $this,
                        'status' => 2,
                        'additionalData' => 'wcf.acp.uzbot.error.disabled',
                    ]);
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Check for valid notify
     */
    public function checkNotify($log = true, $disable = false)
    {
        $notify = UzbotNotify::getNotifyByID($this->notifyID);
        if (!$notify->notifyID) {
            if ($log && $this->enableLog) {
                UzbotLogEditor::create([
                    'bot' => $this,
                    'status' => 2,
                    'additionalData' => 'wcf.acp.uzbot.error.noNotification',
                ]);
            }

            if ($disable) {
                $editor = new UzbotEditor($this);
                $editor->update(['isDisabled' => 1]);
                UzbotEditor::resetCache();

                if ($this->enableLog) {
                    UzbotLogEditor::create([
                        'bot' => $this,
                        'status' => 2,
                        'additionalData' => 'wcf.acp.uzbot.error.disabled',
                    ]);
                }
            }

            return null;
        }

        return $notify;
    }

    /**
     * Check languages
     * Generally available and bot languages should match. However, changing languages after Bot configuration
     * may cause mismatches
     */
    public function checkLanguages($log = false, $disable = false)
    {
        $availableLanguages = LanguageFactory::getInstance()->getLanguages();
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        $configuredBotContents = $this->getBotContents();

        // remove email BBCode in email
        if ($this->notifyDes == 'email') {
            foreach ($configuredBotContents as $content) {
                $re = '/<woltlab-metacode data-name="email" data-attributes="(.*?)"><\/woltlab-metacode>/i';

                while (\preg_match($re, $content->content, $matches, PREG_OFFSET_CAPTURE, 0)) {
                    $replace = $matches[0][0];

                    $email = \base64_decode($matches[1][0]);
                    $email = \str_replace('["', '', $email);
                    $email = \str_replace('"]', '', $email);

                    $content->content = \str_replace($replace, $email, $content->content);
                }
            }
        }

        // check and set Bot content iaw lnguageIDs
        $botContents = [];

        // basic checks
        if (!$this->isMultilingual) {
            if (\count($availableLanguages) > 1) {
                // mismatch
                if ($disable) {
                    $editor = new UzbotEditor($this);
                    $editor->update(['isDisabled' => 1]);
                    UzbotEditor::resetCache();

                    if ($log && $this->enableLog) {
                        UzbotLogEditor::create([
                            'bot' => $this,
                            'status' => 2,
                            'additionalData' => 'wcf.acp.uzbot.log.languageMismatch',
                        ]);
                        UzbotLogEditor::create([
                            'bot' => $this,
                            'status' => 2,
                            'additionalData' => 'wcf.acp.uzbot.error.disabled',
                        ]);
                    }

                    return [];
                } else {
                    // set all contents to existing content
                    $botContents[0] = $configuredBotContents[0];
                    foreach ($availableLanguages as $language) {
                        $botContents[$language->languageID] = $configuredBotContents[0];
                    }

                    if ($log && $this->enableLog) {
                        UzbotLogEditor::create([
                            'bot' => $this,
                            'status' => 1,
                            'additionalData' => 'wcf.acp.uzbot.log.languageMismatch',
                        ]);
                    }
                }
            } else {
                $botContents[0] = $configuredBotContents[0];
                $botContents[$defaultLanguage->languageID] = $configuredBotContents[0];
            }
        } else {    // isMultilingual
            // set default / fallback
            if (isset($configuredBotContents[$defaultLanguage->languageID])) {
                $botContents[0] = $configuredBotContents[$defaultLanguage->languageID];
            } else {
                $botContents[0] = \reset($configuredBotContents);
            }

            $mismatch = 0;
            foreach ($availableLanguages as $language) {
                if (!isset($configuredBotContents[$language->languageID])) {
                    $mismatch = 1;
                    $botContents[$language->languageID] = $botContents[0];
                } else {
                    $botContents[$language->languageID] = $configuredBotContents[$language->languageID];
                }
            }

            if ($mismatch) {
                if ($disable) {
                    $editor = new UzbotEditor($this);
                    $editor->update(['isDisabled' => 1]);
                    UzbotEditor::resetCache();

                    if ($log && $this->enableLog) {
                        UzbotLogEditor::create([
                            'bot' => $this,
                            'status' => 2,
                            'additionalData' => 'wcf.acp.uzbot.log.languageMismatch',
                        ]);
                        UzbotLogEditor::create([
                            'bot' => $this,
                            'status' => 2,
                            'additionalData' => 'wcf.acp.uzbot.error.disabled',
                        ]);
                    }

                    return [];
                } else {
                    if ($log && $this->enableLog) {
                        UzbotLogEditor::create([
                            'bot' => $this,
                            'status' => 1,
                            'additionalData' => 'wcf.acp.uzbot.log.languageMismatch',
                        ]);
                    }
                }
            }
        }

        return $botContents;
    }
}
