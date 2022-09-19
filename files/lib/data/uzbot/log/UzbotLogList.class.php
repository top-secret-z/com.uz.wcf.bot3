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
namespace wcf\data\uzbot\log;

use wcf\data\DatabaseObjectList;
use wcf\system\WCF;

/**
 * Represents a list of Bots log entries.
 */
class UzbotLogList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = UzbotLog::class;

    /**
     * @inheritDoc
     */
    public $sqlOrderBy = 'time DESC';

    /**
     * Returns a list of used bots.
     */
    public function getBotNames()
    {
        $botTitles = [];
        $sql = "SELECT    DISTINCT botTitle
                FROM    wcf" . WCF_N . "_uzbot_log";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        while ($row = $statement->fetchArray()) {
            $botTitles[$row['botTitle']] = $row['botTitle'];
        }

        \ksort($botTitles);

        return $botTitles;
    }

    /**
     * Returns a list of used actions.
     */
    public function getBotActions()
    {
        $botActions = [];
        $sql = "SELECT    DISTINCT typeDes
                FROM    wcf" . WCF_N . "_uzbot_log";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        while ($row = $statement->fetchArray()) {
            $botActions[$row['typeDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.type.' . $row['typeDes']);
        }

        \ksort($botActions);

        return $botActions;
    }

    /**
     * Returns a list of used status.
     */
    public function getBotStatus()
    {
        $botStatus = [];
        $status = ['ok', 'warning', 'error'];

        $sql = "SELECT    DISTINCT status
                FROM    wcf" . WCF_N . "_uzbot_log";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        while ($row = $statement->fetchArray()) {
            $text = WCF::getLanguage()->get('wcf.acp.uzbot.log.' . $status[$row['status']]);
            $botStatus[$text] = $text;
        }

        \ksort($botStatus);

        return $botStatus;
    }

    /**
     * Returns a list of used notifies.
     */
    public function getBotNotifies()
    {
        $botNotifies = [];
        $sql = "SELECT    DISTINCT notifyDes
                FROM    wcf" . WCF_N . "_uzbot_log";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($this->getConditionBuilder()->getParameters());
        while ($row = $statement->fetchArray()) {
            $botNotifies[$row['notifyDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.notify.type.' . $row['notifyDes']);
        }

        \ksort($botNotifies);

        return $botNotifies;
    }
}
