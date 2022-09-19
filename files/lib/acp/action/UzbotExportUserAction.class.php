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
namespace wcf\acp\action;

use wcf\action\AbstractAction;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Exports users' inactivity fields.
 */
class UzbotExportUserAction extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.uzbot.canManageUzbot'];

    /**
     * @inheritDoc
     */
    public function execute()
    {
        parent::execute();

        // get user data
        $users = [];
        $sql = "SELECT        userID, uzbotReminded, uzbotReminders, uzbotDisabled, uzbotBanned
                FROM        wcf" . WCF_N . "_user
                ORDER BY     userID ASC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $users[] = [
                'userID' => $row['userID'],
                'uzbotReminded' => $row['uzbotReminded'],
                'uzbotReminders' => $row['uzbotReminders'],
                'uzbotDisabled' => $row['uzbotDisabled'],
                'uzbotBanned' => $row['uzbotBanned'],
            ];
        }

        // send content type
        \header('Content-Type: text/xml; charset=UTF-8');
        \header('Content-Disposition: attachment; filename="UzbotUserExport.xml"');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<users>\n";

        foreach ($users as $user) {
            echo "\t<user>\n";
            echo "\t\t<userID><![CDATA[" . StringUtil::escapeCDATA($user['userID']) . "]]></userID>\n";
            echo "\t\t<reminded><![CDATA[" . StringUtil::escapeCDATA($user['uzbotReminded']) . "]]></reminded>\n";
            echo "\t\t<reminders><![CDATA[" . StringUtil::escapeCDATA($user['uzbotReminders']) . "]]></reminders>\n";
            echo "\t\t<disabled><![CDATA[" . StringUtil::escapeCDATA($user['uzbotDisabled']) . "]]></disabled>\n";
            echo "\t\t<banned><![CDATA[" . StringUtil::escapeCDATA($user['uzbotBanned']) . "]]></banned>\n";
            echo "\t</user>\n";
        }

        echo "</users>";

        $this->executed();

        exit;
    }
}
