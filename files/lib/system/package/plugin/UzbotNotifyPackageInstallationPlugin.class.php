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
namespace wcf\system\package\plugin;

use wcf\data\uzbot\notification\UzbotNotifyEditor;
use wcf\system\WCF;

/**
 * Installs, updates and deletes additional Bot notifications.
 */
class UzbotNotifyPackageInstallationPlugin extends AbstractXMLPackageInstallationPlugin
{
    /**
     * @inheritDoc
     */
    public $className = UzbotNotifyEditor::class;

    /**
     * @inheritDoc
     */
    public $tableName = 'uzbot_notify';

    /**
     * @inheritDoc
     */
    public $tagName = 'uzbotNotify';

    /**
     * @inheritDoc
     */
    protected function handleDelete(array $items)
    {
        $sql = "DELETE FROM    wcf" . WCF_N . "_" . $this->tableName . "
                WHERE        notifyTitle = ?
                            AND packageID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        foreach ($items as $item) {
            $statement->execute([
                $item['attributes']['name'],
                $this->installation->getPackageID(),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function prepareImport(array $data)
    {
        return [
            'notifyTitle' => $data['attributes']['name'],
            'notifyID' => $data['elements']['notifyID'],
            'hasContent' => $data['elements']['hasContent'],
            'hasLabels' => $data['elements']['hasLabels'],
            'hasReceiver' => $data['elements']['hasReceiver'],
            'hasSender' => $data['elements']['hasSender'],
            'hasSubject' => $data['elements']['hasSubject'],
            'hasTags' => $data['elements']['hasTags'],
            'hasTeaser' => $data['elements']['hasTeaser'],
            'neededModule' => $data['elements']['neededModule'],
            'notifyFunction' => $data['elements']['notifyFunction'],
            'sortOrder' => $data['elements']['sortOrder'],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function findExistingItem(array $data)
    {
        $sql = "SELECT    *
                FROM    wcf" . WCF_N . "_" . $this->tableName . "
                WHERE    notifyTitle = ?
                        AND packageID = ?";
        $parameters = [
            $data['notifyTitle'],
            $this->installation->getPackageID(),
        ];

        return [
            'sql' => $sql,
            'parameters' => $parameters,
        ];
    }
}
