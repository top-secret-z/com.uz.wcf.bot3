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
namespace wcf\acp\form;

use wcf\form\AbstractForm;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\XML;

/**
 * Shows the Bot user import/export form.
 */
class UzbotImportUserForm extends AbstractForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'wcf.acp.menu.link.uzbot.import.user';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.uzbot.canManageUzbot'];

    /**
     * upload file data
     */
    public $uzbotImportUser;

    /**
     * list of users
     */
    public $users = [];

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        parent::readFormParameters();

        if (isset($_FILES['uzbotImportUser'])) {
            $this->uzbotImportUser = $_FILES['uzbotImportUser'];
        }
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        parent::validate();

        // upload
        if ($this->uzbotImportUser && $this->uzbotImportUser['error'] != 4) {
            if ($this->uzbotImportUser['error'] != 0) {
                throw new UserInputException('uzbotImportUser', 'uploadFailed');
            }

            try {
                $xml = new XML();
                $xml->load($this->uzbotImportUser['tmp_name']);
                $xpath = $xml->xpath();

                foreach ($xpath->query('/users/user') as $user) {
                    $data = [];

                    try {
                        $data['userID'] = $xpath->query('userID', $user)->item(0)->nodeValue;
                        $data['uzbotReminded'] = $xpath->query('reminded', $user)->item(0)->nodeValue;
                        $data['uzbotReminders'] = $xpath->query('reminders', $user)->item(0)->nodeValue;
                        $data['uzbotDisabled'] = $xpath->query('disabled', $user)->item(0)->nodeValue;
                        $data['uzbotBanned'] = $xpath->query('banned', $user)->item(0)->nodeValue;
                    } catch (SystemException $e) {
                        break;
                    }
                    $this->users[] = $data;
                }
            } catch (SystemException $e) {
                @\unlink($this->uzbotImportUser['tmp_name']);
                throw new UserInputException('uzbotImportUser', 'importFailed');
            }
        } else {
            throw new UserInputException('uzbotImportUser');
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        $sql = "UPDATE    wcf" . WCF_N . "_user
                SET        uzbotReminded = ?, uzbotReminders = ?, uzbotDisabled = ?, uzbotBanned = ?
                WHERE    userID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);

        foreach ($this->users as $user) {
            $statement->execute([$user['uzbotReminded'], $user['uzbotReminders'], $user['uzbotDisabled'], $user['uzbotBanned'], $user['userID']]);
        }

        // delete import file
        @\unlink($this->uzbotImportUser['tmp_name']);

        $this->saved();

        // show success message
        WCF::getTPL()->assign('success', true);
    }
}
