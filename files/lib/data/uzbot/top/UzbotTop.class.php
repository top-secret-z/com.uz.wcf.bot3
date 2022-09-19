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
namespace wcf\data\uzbot\top;

use wcf\data\DatabaseObject;
use wcf\data\like\Like;
use wcf\system\WCF;

/**
 * Represents a Bot Top entry
 */
class UzbotTop extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'uzbot_top';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'topID';

    /**
     * Likes
     */
    public static function refreshLike()
    {
        $userID = null;
        $sql = "SELECT        objectUserID as userID, COUNT(*) AS count
                FROM        wcf" . WCF_N . "_like
                WHERE        likeValue = ?
                GROUP BY    objectUserID
                ORDER BY     count DESC";
        $statement = WCF::getDB()->prepareStatement($sql, 1);
        $statement->execute([Like::LIKE]);
        if ($row = $statement->fetchArray()) {
            $userID = $row['userID'];
        }

        if ($userID) {
            $editor = new UzbotTopEditor(new self(1));
            $editor->update(['liked' => $userID]);
        }

        return $userID;
    }
}
