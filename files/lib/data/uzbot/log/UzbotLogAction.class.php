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

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Executes Bot Log related actions.
 */
class UzbotLogAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = UzbotLogEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.uzbot.canManageUzbot'];

    protected $permissionsUpdate = ['admin.uzbot.canManageUzbot'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['delete', 'update'];

    /**
     * Validates the clearAll action.
     */
    public function validateClearAll()
    {
        // do nothing
    }

    /**
     * Executes the deleteAll action.
     */
    public function clearAll()
    {
        $sql = "DELETE FROM    wcf" . WCF_N . "_uzbot_log";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
    }
}
