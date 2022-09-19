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

use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents a Bot notification
 */
class UzbotNotify extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'uzbot_notify';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'id';

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return WCF::getLanguage()->get('wcf.acp.uzbot.notify.' . $this->notifyTitle);
    }

    /**
     * returns Notify with given notifyID
     */
    public static function getNotifyByID($notifyID)
    {
        $sql = "SELECT    *
                FROM     wcf" . WCF_N . "_uzbot_notify
                WHERE    notifyID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$notifyID]);
        $row = $statement->fetchArray();
        if (!$row) {
            $row = [];
        }

        return new self(null, $row);
    }

    /**
     * returns NotifyID matching the given notifyTitle or 0.
     */
    public static function getNotifyIDFromTitel($notifyTitle)
    {
        $sql = "SELECT    notifyID
                FROM     wcf" . WCF_N . "_uzbot_notify
                WHERE    notifyTitle = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$notifyTitle]);
        if ($row = $statement->fetchArray()) {
            return $row['notifyID'];
        }

        return 0;
    }
}
