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
namespace wcf\acp\page;

use wcf\data\category\CategoryList;
use wcf\data\uzbot\UzbotList;
use wcf\page\SortablePage;
use wcf\system\category\CategoryHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Shows the Community Bot list page.
 */
class UzbotListPage extends SortablePage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'wcf.acp.menu.link.uzbot.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.uzbot.canManageUzbot'];

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_UZBOT'];

    /**
     * number of items shown per page
     */
    public $itemsPerPage = 15;

    /**
     * @inheritDoc
     */
    public $defaultSortField = 'botID';

    /**
     * @inheritDoc
     */
    public $validSortFields = ['botID', 'isDisabled', 'enableLog', 'testMode', 'categoryID', 'botTitle', 'typeDes', 'notifyDes', 'sendername'];

    /**
     * @inheritDoc
     */
    public $objectListClassName = UzbotList::class;

    /**
     * category list
     */
    public $categories;

    /**
     * Filter
     */
    public $botTitle = '';

    public $availableNotifyDes = [];

    public $availableCategories = [];

    public $notifyDes = '';

    public $availableTypeDes = [];

    public $typeDes = '';

    public $categoryID = '';

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        // read filter
        if (!empty($_REQUEST['botTitle'])) {
            $this->botTitle = StringUtil::trim($_REQUEST['botTitle']);
        }
        if (!empty($_REQUEST['categoryID'])) {
            $this->categoryID = $_REQUEST['categoryID'];
        }
        if (!empty($_REQUEST['notifyDes'])) {
            $this->notifyDes = $_REQUEST['notifyDes'];
        }
        if (!empty($_REQUEST['typeDes'])) {
            $this->typeDes = $_REQUEST['typeDes'];
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        $this->availableCategories = $this->objectList->getAvailableCategories();
        $this->availableNotifyDes = $this->objectList->getAvailableNotifyDes();
        $this->availableTypeDes = $this->objectList->getAvailableTypeDes();

        // filter
        if (!empty($this->botTitle)) {
            $this->objectList->getConditionBuilder()->add('botTitle LIKE ?', ['%' . $this->botTitle . '%']);
        }
        if (!empty($this->categoryID)) {
            $this->objectList->getConditionBuilder()->add('categoryID = ?', [$this->categoryID]);
        }
        if (!empty($this->notifyDes)) {
            $this->objectList->getConditionBuilder()->add('notifyDes LIKE ?', [$this->notifyDes]);
        }
        if (!empty($this->typeDes)) {
            $this->objectList->getConditionBuilder()->add('typeDes LIKE ?', [$this->typeDes]);
        }
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        $objectType = CategoryHandler::getInstance()->getObjectTypeByName('com.uz.wcf.bot.category');
        if ($objectType) {
            $categoryList = new CategoryList();
            $categoryList->getConditionBuilder()->add('category.objectTypeID = ?', [$objectType->objectTypeID]);
            $categoryList->readObjects();
            $this->categories = $categoryList->getObjects();
        }

        parent::readData();
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        // assign sorting parameters
        WCF::getTPL()->assign([
            'categories' => $this->categories,
            'botTitle' => $this->botTitle,
            'availableCategories' => $this->availableCategories,
            'categoryID' => $this->categoryID,
            'availableNotifyDes' => $this->availableNotifyDes,
            'notifyDes' => $this->notifyDes,
            'availableTypeDes' => $this->availableTypeDes,
            'typeDes' => $this->typeDes,
        ]);
    }
}
