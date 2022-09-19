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

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\condition\ConditionList;
use wcf\data\IToggleAction;
use wcf\data\uzbot\content\UzbotContent;
use wcf\data\uzbot\content\UzbotContentEditor;
use wcf\data\uzbot\content\UzbotContentList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\cache\builder\ConditionCacheBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\label\object\UzbotActionLabelObjectHandler;
use wcf\system\label\object\UzbotConditionLabelObjectHandler;
use wcf\system\label\object\UzbotNotificationLabelObjectHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Executes Bot-related actions.
 */
class UzbotAction extends AbstractDatabaseObjectAction implements IToggleAction
{
    /**
     * @inheritDoc
     */
    protected $className = UzbotEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.uzbot.canManageUzbot'];

    protected $permissionsUpdate = ['admin.uzbot.canManageUzbot'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'toggle', 'update'];

    public $bot;

    /**
     * @inheritDoc
     */
    public function create()
    {
        $bot = parent::create();

        // save bot content
        if (!empty($this->parameters['content'])) {
            foreach ($this->parameters['content'] as $languageID => $content) {
                if (!empty($content['htmlInputProcessor'])) {
                    $content['content'] = $content['htmlInputProcessor']->getHtml();
                }

                $botContent = UzbotContentEditor::create([
                    'botID' => $bot->botID,
                    'languageID' => $languageID ?: null,
                    'condense' => $content['condense'],
                    'subject' => $content['subject'],
                    'tags' => \serialize($content['tags']),
                    'teaser' => $content['teaser'],
                    'content' => $content['content'],
                    'imageID' => $content['imageID'],
                    'teaserImageID' => $content['teaserImageID'],
                ]);
                $botContentEditor = new UzbotContentEditor($botContent);

                // save embedded objects - not ufn
                //if (!empty($content['htmlInputProcessor'])) {
                //    $content['htmlInputProcessor']->setObjectID($botContent->contentID);
                //    if (MessageEmbeddedObjectManager::getInstance()->registerObjects($content['htmlInputProcessor'])) {
                //        $botContentEditor->update(['hasEmbeddedObjects' => 1]);
                //    }
                //}
            }
        }

        // labels
        if (!empty($this->parameters['actionLabelIDs'])) {
            UzbotActionLabelObjectHandler::getInstance()->setLabels($this->parameters['actionLabelIDs'], $bot->botID);
        }
        if (!empty($this->parameters['conditionLabelIDs'])) {
            UzbotConditionLabelObjectHandler::getInstance()->setLabels($this->parameters['conditionLabelIDs'], $bot->botID);
        }
        if (!empty($this->parameters['notifyLabelIDs'])) {
            UzbotNotificationLabelObjectHandler::getInstance()->setLabels($this->parameters['notifyLabelIDs'], $bot->botID);
        }

        // reset  cache
        UzbotEditor::resetCache();

        return $bot;
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        parent::update();

        // update bot content
        if (!empty($this->parameters['content'])) {
            foreach ($this->getObjects() as $bot) {
                foreach ($this->parameters['content'] as $languageID => $content) {
                    if (!empty($content['htmlInputProcessor'])) {
                        $content['content'] = $content['htmlInputProcessor']->getHtml();
                    }

                    $botContent = UzbotContent::getBotContent($bot->botID, ($languageID ?: null));
                    $botContentEditor = null;
                    if ($botContent !== null) {
                        // update
                        $botContentEditor = new UzbotContentEditor($botContent);
                        $botContentEditor->update([
                            'content' => $content['content'],
                            'condense' => $content['condense'],
                            'subject' => $content['subject'],
                            'tags' => \serialize($content['tags']),
                            'teaser' => $content['teaser'],
                            'imageID' => $content['imageID'],
                            'teaserImageID' => $content['teaserImageID'],
                        ]);
                    } else {
                        $botContent = UzbotContentEditor::create([
                            'botID' => $bot->botID,
                            'languageID' => $languageID ?: null,
                            'content' => $content['content'],
                            'condense' => $content['condense'],
                            'subject' => $content['subject'],
                            'tags' => \serialize($content['tags']),
                            'teaser' => $content['teaser'],
                            'imageID' => $content['imageID'],
                            'teaserImageID' => $content['teaserImageID'],
                        ]);
                        $botContentEditor = new UzbotContentEditor($botContent);
                    }

                    // save embedded objects - not ufn
                    //if (!empty($content['htmlInputProcessor'])) {
                    //    $content['htmlInputProcessor']->setObjectID($botContent->contentID);
                    //    if ($botContent->hasEmbeddedObjects != MessageEmbeddedObjectManager::getInstance()->registerObjects($content['htmlInputProcessor'])) {
                    //        $botContentEditor->update(['hasEmbeddedObjects' => $botContent->hasEmbeddedObjects ? 0 : 1]);
                    //    }
                    //}
                }
            }
        }

        // update labels
        foreach ($this->getObjects() as $bot) {
            UzbotActionLabelObjectHandler::getInstance()->setLabels($this->parameters['actionLabelIDs'], $bot->botID);
            UzbotConditionLabelObjectHandler::getInstance()->setLabels($this->parameters['conditionLabelIDs'], $bot->botID);
            UzbotNotificationLabelObjectHandler::getInstance()->setLabels($this->parameters['notifyLabelIDs'], $bot->botID);
        }

        // reset cache
        UzbotEditor::resetCache();
    }

    /**
     * @inheritDoc
     */
    public function validateDelete()
    {
        parent::validateDelete();

        // check system
        foreach ($this->objects as $bot) {
            $sql = "SELECT    COUNT(*)
                    FROM    wcf" . WCF_N . "_uzbot_system
                    WHERE    botID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$bot->botID]);
            $count = $statement->fetchSingleColumn();
            if ($count && !$bot->isDisabled) {
                throw new NamedUserException(WCF::getLanguage()->get('wcf.acp.uzbot.general.error.noDeleteAllowed'));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        parent::delete();

        // reset cache
        UzbotEditor::resetCache();
    }

    /**
     * @inheritDoc
     */
    public function validateToggle()
    {
        parent::validateUpdate();
    }

    /**
     * @inheritDoc
     */
    public function toggle()
    {
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        foreach ($this->objects as $uzbot) {
            $uzbot->update([
                'isDisabled' => $uzbot->isDisabled ? 0 : 1,
            ]);

            $bot = $uzbot->getDecoratedObject();
            UzbotLogEditor::create([
                'bot' => $bot,
                'count' => 1,
                'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.bot.edited', [
                    'username' => WCF::getUser()->username,
                ]),
            ]);
        }

        // reset BotList cache
        UzbotEditor::resetCache();
    }

    /**
     * Validates the get help dialog action.
     */
    public function validateGetHelp()
    {
        // do nothing
    }

    /**
     * Executes the get help dialog action.
     */
    public function getHelp()
    {
        $helpItem = $this->parameters['helpItem'];

        WCF::getTPL()->assign([
            'subject' => WCF::getLanguage()->get('wcf.acp.uzbot.' . $helpItem),
            'text' => WCF::getLanguage()->getDynamicVariable('wcf.acp.uzbot.' . $helpItem . '.description'),
        ]);

        return [
            'template' => WCF::getTPL()->fetch('uzbotHelpDialog'),
        ];
    }

    /**
     * Validates the feedReset action.
     */
    public function validateFeedReset()
    {
        $this->bot = new Uzbot($this->parameters['objectID']);
        if (!$this->bot->botID) {
            throw new IllegalLinkException();
        }
    }

    /**
     * Executes the feedReset action.
     */
    public function feedReset()
    {
        $editor = new UzbotEditor($this->bot);
        $editor->update(['feedreaderLast' => 0]);

        $sql = "DELETE FROM    wcf" . WCF_N . "_uzbot_feedreader_hash
                WHERE        botID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->bot->botID]);
    }

    /**
     * Validates the copy action.
     */
    public function validateCopy()
    {
        $this->bot = new Uzbot($this->parameters['objectID']);
        if (!$this->bot->botID) {
            throw new IllegalLinkException();
        }
    }

    /**
     * Executes the feedReset action.
     */
    public function copy()
    {
        $data = $this->bot->getData();
        $oldBotID = $data['botID'];
        unset($data['botID']);

        // copy bot, set to disable
        $data['isDisabled'] = 1;
        $data['botTitle'] = \substr($data['botTitle'], 0, 75) . ' (2)';
        $this->parameters['data'] = $data;
        $uzBot = $this->create();

        // copy conditions
        $definitionIDs = [];
        $sql = "SELECT        definitionID
                FROM        wcf" . WCF_N . "_object_type_definition
                WHERE        definitionName LIKE ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(['com.uz.wcf.bot.condition.%']);
        while ($row = $statement->fetchArray()) {
            $definitionIDs[] = $row['definitionID'];
        }

        foreach ($definitionIDs as $definitionID) {
            $objectTypeIDs = [];
            $sql = "SELECT        objectTypeID
                    FROM        wcf" . WCF_N . "_object_type
                    WHERE        definitionID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$definitionID]);
            while ($row = $statement->fetchArray()) {
                $objectTypeIDs[] = $row['objectTypeID'];
            }

            $conditionList = new ConditionList();
            $conditionList->getConditionBuilder()->add('objectTypeID IN (?)', [$objectTypeIDs]);
            $conditionList->getConditionBuilder()->add('objectID = ?', [$oldBotID]);
            $conditionList->readObjects();
            $conditions = $conditionList->getObjects();

            if (\count($conditions)) {
                WCF::getDB()->beginTransaction();
                $sql = "INSERT INTO wcf" . WCF_N . "_condition
                                (objectID, objectTypeID, conditionData)
                        VALUES    (?, ?, ?)";
                $statement = WCF::getDB()->prepareStatement($sql);

                foreach ($conditions as $condition) {
                    $statement->execute([$uzBot->botID, $condition->objectTypeID, \serialize($condition->conditionData)]);
                }
                WCF::getDB()->commitTransaction();
            }
        }

        ConditionCacheBuilder::getInstance()->reset();

        // copy content
        $contentList = new UzbotContentList();
        $contentList->getConditionBuilder()->add('botID = ?', [$oldBotID]);
        $contentList->readObjects();
        $contents = $contentList->getObjects();

        WCF::getDB()->beginTransaction();
        $sql = "INSERT INTO wcf" . WCF_N . "_uzbot_content
                            (botID, languageID, condense, content, subject, tags, teaser, imageID, teaserImageID)
                VALUES    (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $statement = WCF::getDB()->prepareStatement($sql);

        foreach ($contents as $content) {
            $statement->execute([$uzBot->botID, $content->languageID, $content->condense, $content->content, $content->subject, $content->tags, $content->teaser, $content->imageID, $content->teaserImageID]);
        }
        WCF::getDB()->commitTransaction();

        // log action
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        UzbotLogEditor::create([
            'bot' => $uzBot,
            'count' => 1,
            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.bot.created', [
                'username' => WCF::getUser()->username,
            ]),
        ]);

        return [
            'redirectURL' => LinkHandler::getInstance()->getLink('UzbotEdit', ['id' => $uzBot->botID]), ];
    }
}
