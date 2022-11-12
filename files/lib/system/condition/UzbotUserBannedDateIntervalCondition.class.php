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
namespace wcf\system\condition;

use InvalidArgumentException;
use wcf\data\condition\Condition;
use wcf\data\DatabaseObjectList;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\system\WCF;

/**
 * Condition implementation for a relative interval for the ban time.
 */
class UzbotUserBannedDateIntervalCondition extends AbstractIntegerCondition implements IContentCondition, IObjectListCondition, IUserCondition
{
    use TObjectListUserCondition;

    /**
     * @inheritDoc
     */
    protected $label = 'wcf.acp.uzbot.condition.bannedDateInterval';

    /**
     * @inheritDoc
     */
    protected $minValue = 0;

    /**
     * @inheritDoc
     */
    public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData)
    {
        if (!($objectList instanceof UserList)) {
            throw new InvalidArgumentException("Object list is no instance of '" . UserList::class . "', instance of '" . \get_class($objectList) . "' given.");
        }

        if (isset($conditionData['greaterThan'])) {
            $objectList->getConditionBuilder()->add('user_table.uzbotBanned < ?', [TIME_NOW - $conditionData['greaterThan'] * 86400]);
        }
        if (isset($conditionData['lessThan'])) {
            $objectList->getConditionBuilder()->add('user_table.uzbotBanned > ?', [TIME_NOW - $conditionData['lessThan'] * 86400]);
        }
    }

    /**
     * @inheritDoc
     */
    public function checkUser(Condition $condition, User $user)
    {
        $greaterThan = $condition->greaterThan;
        if ($greaterThan !== null && $user->uzbotBanned >= TIME_NOW - $greaterThan * 86400) {
            return false;
        }

        $lessThan = $condition->lessThan;
        if ($lessThan !== null && $user->uzbotBanned <= TIME_NOW - $lessThan * 86400) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getIdentifier()
    {
        return 'uzbot_bannedDateInterval';
    }

    /**
     * @inheritDoc
     */
    public function showContent(Condition $condition)
    {
        if (!WCF::getUser()->userID) {
            return false;
        }

        return $this->checkUser($condition, WCF::getUser());
    }
}
