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
use wcf\system\exception\UserInputException;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Condition implementation for the languages of a user.
 */
class UzbotReceiverLanguageCondition extends AbstractSingleFieldCondition implements IContentCondition, IObjectListCondition, IUserCondition
{
    use TObjectListUserCondition;

    /**
     * @inheritDoc
     */
    protected $label = 'wcf.user.condition.languages';

    /**
     * ids of the selected languages
     * @var    integer[]
     */
    protected $receiverLanguageIDs = [];

    /**
     * @inheritDoc
     */
    public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData)
    {
        if (!($objectList instanceof UserList)) {
            throw new InvalidArgumentException("Object list is no instance of '" . UserList::class . "', instance of '" . \get_class($objectList) . "' given.");
        }

        $objectList->getConditionBuilder()->add('user_table.languageID IN (?)', [$conditionData['receiverLanguageIDs']]);
    }

    /**
     * @inheritDoc
     */
    public function checkUser(Condition $condition, User $user)
    {
        if (!empty($condition->conditionData['receiverLanguageIDs']) && !\in_array($user->languageID, $condition->receiverLanguageIDs)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        if (!empty($this->receiverLanguageIDs)) {
            return [
                'receiverLanguageIDs' => $this->receiverLanguageIDs,
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function getFieldElement()
    {
        $returnValue = "";
        foreach (LanguageFactory::getInstance()->getLanguages() as $language) {
            $returnValue .= "<label><input type=\"checkbox\" name=\"receiverLanguageIDs[]\" value=\"" . $language->languageID . "\"" . (\in_array($language->languageID, $this->receiverLanguageIDs) ? ' checked' : "") . "> " . $language->languageName . "</label>";
        }

        return $returnValue;
    }

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        if (isset($_POST['receiverLanguageIDs']) && \is_array($_POST['receiverLanguageIDs'])) {
            $this->receiverLanguageIDs = ArrayUtil::toIntegerArray($_POST['receiverLanguageIDs']);
        }
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->receiverLanguageIDs = [];
    }

    /**
     * @inheritDoc
     */
    public function setData(Condition $condition)
    {
        if (!empty($condition->conditionData['receiverLanguageIDs'])) {
            $this->receiverLanguageIDs = $condition->conditionData['receiverLanguageIDs'];
        }
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        foreach ($this->receiverLanguageIDs as $languageID) {
            if (LanguageFactory::getInstance()->getLanguage($languageID) === null) {
                $this->errorMessage = 'wcf.global.form.error.noValidSelection';

                throw new UserInputException('receiverLanguageIDs', 'noValidSelection');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function showContent(Condition $condition)
    {
        if (WCF::getUser()->userID) {
            return $this->checkUser($condition, WCF::getUser());
        }

        if (!empty($condition->conditionData['receiverLanguageIDs']) && !\in_array(WCF::getLanguage()->languageID, $condition->receiverLanguageIDs)) {
            return false;
        }

        return true;
    }
}
