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
namespace wcf\data\uzbot\content;

use wcf\data\DatabaseObject;
use wcf\data\language\Language;
use wcf\data\uzbot\Uzbot;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Represents a Bot content.
 */
class UzbotContent extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'uzbot_content';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'contentID';

    /**
     * uzbot object
     */
    protected $uzbot;

    /**
     * Returns the uzbot's formatted content.
     */
    public function getFormattedContent()
    {
        $processor = new HtmlOutputProcessor();
        $processor->process($this->content, 'com.uz.wcf.bot.content', $this->contentID);

        return $processor->getHtml();
    }

    /**
     * Returns the language of this bot content or `null` if no language has been specified.
     */
    public function getLanguage()
    {
        if ($this->languageID) {
            return LanguageFactory::getInstance()->getLanguage($this->languageID);
        }

        return null;
    }

    /**
     * Returns a certain bot content or `null` if it does not exist.
     */
    public static function getBotContent($botID, $languageID)
    {
        if ($languageID !== null) {
            $sql = "SELECT    *
                    FROM    wcf" . WCF_N . "_uzbot_content
                    WHERE    botID = ? AND languageID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$botID, $languageID]);
        } else {
            $sql = "SELECT    *
                    FROM    wcf" . WCF_N . "_uzbot_content
                    WHERE    botID = ? AND languageID IS NULL";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$botID]);
        }

        if (($row = $statement->fetchSingleRow()) !== false) {
            return new self(null, $row);
        }

        return null;
    }
}
