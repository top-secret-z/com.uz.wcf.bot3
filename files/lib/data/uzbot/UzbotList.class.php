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

use wcf\data\DatabaseObjectList;
use wcf\system\cache\builder\CategoryCacheBuilder;
use wcf\system\WCF;

/**
 * Represents a list of Bots.
 */
class UzbotList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Uzbot::class;

    /**
     * Returns a list of available categories.
     */
    public function getAvailableCategories()
    {
        $categories = CategoryCacheBuilder::getInstance()->getData([], 'categories');

        $categoryIDs = [];
        $sql = "SELECT    DISTINCT categoryID
                FROM    wcf" . WCF_N . "_uzbot";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            if ($row['categoryID']) {
                $categoryIDs[$row['categoryID']] = $categories[$row['categoryID']]->getTitle();
            }
        }
        \ksort($categoryIDs);

        return $categoryIDs;
    }

    /**
     * Returns a list of available notifies.
     */
    public function getAvailableNotifyDes()
    {
        $notifies = [];
        $sql = "SELECT    DISTINCT notifyDes
                FROM    wcf" . WCF_N . "_uzbot";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            if ($row['notifyDes']) {
                $notifies[$row['notifyDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.notify.' . $row['notifyDes']);
            }
        }
        \ksort($notifies);

        return $notifies;
    }

    /**
     * Returns a list of available types.
     */
    public function getAvailableTypeDes()
    {
        $types = [];
        $sql = "SELECT    DISTINCT typeDes
                FROM    wcf" . WCF_N . "_uzbot";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            if ($row['typeDes']) {
                $types[$row['typeDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.type.' . $row['typeDes']);
            }
        }
        \ksort($types);

        return $types;
    }
}
