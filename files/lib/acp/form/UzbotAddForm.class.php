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

use DateTime;
use DateTimeZone;
use Exception;
use wcf\data\article\category\ArticleCategory;
use wcf\data\category\Category;
use wcf\data\category\CategoryNodeTree;
use wcf\data\media\ViewableMediaList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\object\type\ObjectTypeList;
use wcf\data\user\group\UserGroup;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\notification\UzbotNotify;
use wcf\data\uzbot\notification\UzbotNotifyList;
use wcf\data\uzbot\type\UzbotType;
use wcf\data\uzbot\type\UzbotTypeList;
use wcf\data\uzbot\UzbotAction;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\UzbotUtils;
use wcf\form\AbstractForm;
use wcf\system\condition\ConditionHandler;
use wcf\system\condition\uzbot\UzbotReceiverConditionHandler;
use wcf\system\condition\uzbot\UzbotUserBotConditionHandler;
use wcf\system\condition\uzbot\UzbotUserConditionHandler;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\label\LabelHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\DateUtil;
use wcf\util\HTTPRequest;
use wcf\util\StringUtil;
use wcf\util\UserUtil;

/**
 * Shows the Bot add form.
 */
class UzbotAddForm extends AbstractForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'wcf.acp.menu.link.uzbot.add';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.uzbot.canManageUzbot'];

    /**
     * general data
     */
    public $availableCommentTypes = [];

    public $availableGroups = [];

    public $availableGroupsPassive = [];

    public $availableGroupsPassiveIDs = [];

    public $availableLabels = [];

    public $availableLanguages = [];

    public $availableNotifies = [];

    public $availableTimezones = [];

    public $availableTypes = [];

    public $availableWeekdays = [];

    public $categoryNodeTree;

    public $htmlInputProcessors = [];

    public $labelGroups;

    public $receiverGroups = [];

    /**
     * Bot data
     */
    public $botID = 0;

    public $botDescription = '';

    public $botTitle = '';

    public $categoryID = 0;

    public $enableLog = 1;

    public $isDisabled = 0;

    public $testMode = 0;

    public $notify;

    public $notifyID = 0;

    public $articleCategoryID = 0;

    public $articleConditionCategoryID = 0;

    public $articleConditionCategoryID_new = 0;

    public $articleEnableComments = 0;

    public $articlePublicationStatus = 1;

    public $commentActivity = 1;

    public $condense = [];

    public $content = [];

    public $conversationAllowAdd = 0;

    public $conversationClose = 0;

    public $conversationInvisible = '';

    public $conversationLeave = 0;

    public $conversationType = 0;

    public $emailBCC = '';

    public $emailCC = '';

    public $emailPrivacy = 0;

    public $emailAttachmentFile = '';

    public $isMultilingual = 0;

    public $notifyLanguageID = 0;

    public $receiverAffected = 0;

    public $receiverGroupIDs = [];

    public $receiverNames = '';

    public $sender;

    public $sendername = '';

    public $subject = [];

    public $tags = [];

    public $teaser = [];

    public $type;

    public $typeID = 0;

    public $actionLabelDelete = 0;

    public $actionLabelIDs = [];

    public $conditionLabelIDs = [];

    public $notifyLabelIDs = [];

    public $articlePublished = 0;

    public $birthdayForce = 1;

    public $changeAffected = 0;

    public $condenseEnable = 0;

    public $cirData;

    public $cirCounter = 0;

    public $cirCounterInterval = 0;

    public $cirEndTime = '';

    public $cirEndDateTime;

    public $cirRepeatCount = 1;

    public $cirRepeatType = 'none';

    public static $cirRepeatTypes = ['daily', 'weekly', 'monthlyDoM', 'monthlyDoW', 'quarterly', 'halfyearly', 'yearlyDoM', 'yearlyDoW'];

    public $cirStartTime = '';

    public $cirStartDateTime;

    public $cirTimezone = '';

    public $cirTimezoneObj;

    public $cirWeekly_day = 1;

    public $cirMonthlyDoM_day = 1;

    public $cirMonthlyDoW_day = 1;

    public $cirMonthlyDoW_index = 1;

    public $cirYearlyDoM_day = 1;

    public $cirYearlyDoM_month = 1;

    public $cirYearlyDoW_day = 1;

    public $cirYearlyDoW_index = 1;

    public $cirYearlyDoW_month = 1;

    public $commentDays = 365;

    public $commentDaysAfter = 'reply';

    public $commentNoAnswers = 0;

    public $commentNoUser = 0;

    public $commentTypeIDs = [];

    public $conversationDays = 365;

    public $conversationDaysAfter = 'reply';

    public $conversationNoAnswers = 1;

    public $conversationNoLabels = 0;

    public $feedreaderExclude = '';

    public $feedreaderFrequency = 1800;

    public $feedreaderInclude = '';

    public $feedreaderMaxAge = 0;

    public $feedreaderMaxItems = 0;

    public $feedreaderUseTags = 0;

    public $feedreaderUseTime = 0;

    public $feedreaderUrl = '';

    public $groupAssignmentGroupID;

    public $groupAssignmentAction = 'add';

    public $groupChangeGroupIDs = [];

    public $groupChangeType = 0;

    public $inactiveAction = 'remind';

    public $inactiveBanReason = '';

    public $inactiveReminderLimit = 1;

    public $likeAction = 'likeTotal';

    public $userCount = '';

    public $userCreationGroupID = 0;

    public $userSettingAvatarOption = 0;

    public $userSettingCover = 0;

    public $userSettingEmail = 0;

    public $userSettingOther = 0;

    public $userSettingSelfDeletion = 0;

    public $userSettingSignature = 0;

    public $userSettingUsername = 0;

    public $userSettingUserTitle = 0;

    public $receiverConditions = [];

    public $userConditions = [];

    public $userBotConditions = [];

    public $imageID = [];

    public $images = [];

    public $teaserImageID = [];

    public $teaserImages = [];

    /**
     * @inheritDoc
     */
    public function readData()
    {
        // categories
        $this->categoryNodeTree = new CategoryNodeTree('com.uz.wcf.bot.category', 0, true);

        // conditions
        $this->receiverConditions = UzbotReceiverConditionHandler::getInstance()->getGroupedObjectTypes();
        $this->userConditions = UzbotUserConditionHandler::getInstance()->getGroupedObjectTypes();
        $this->userBotConditions = UzbotUserBotConditionHandler::getInstance()->getGroupedObjectTypes();

        // available data
        $this->availableNotifies = new UzbotNotifyList();
        $this->availableNotifies->readObjects();
        $this->availableWeekdays = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            0 => 'sunday',
        ];
        $this->availableTypes = new UzbotTypeList();
        $this->availableTypes->readObjects();
        $this->receiverGroups = UserGroup::getAccessibleGroups([], [UserGroup::GUESTS, UserGroup::EVERYONE, UserGroup::USERS]);

        // time zones / start time
        foreach (DateUtil::getAvailableTimezones() as $timezone) {
            $this->availableTimezones[$timezone] = WCF::getLanguage()->get('wcf.date.timezone.' . \str_replace('/', '.', \strtolower($timezone)));
        }

        // labels
        $sql = "SELECT        label.*, label_group.groupName
                FROM        wcf" . WCF_N . "_label label
                LEFT JOIN    wcf" . WCF_N . "_label_group label_group
                            ON (label.groupID = label_group.groupID)
                ORDER BY    label_group.groupName ASC, label.label ASC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $this->availableLabels[] = $row;
        }

        // get accessible groups, exclude admin/owner group (no OWNER in 3.1)
        $this->availableGroups = UserGroup::getAccessibleGroups([], [UserGroup::GUESTS, UserGroup::EVERYONE, UserGroup::USERS]);
        foreach ($this->availableGroups as $key => $group) {
            if ($group->isAdminGroup()) {
                unset($this->availableGroups[$key]);
            }
        }
        $this->availableGroupsPassive = UserGroup::getAccessibleGroups([], [UserGroup::GUESTS, UserGroup::EVERYONE, UserGroup::USERS]);
        foreach ($this->availableGroupsPassive as $key => $group) {
            $this->availableGroupsPassiveIDs[] = $key;
        }

        // comment types
        $definition = ObjectTypeCache::getInstance()->getDefinitionByName('com.woltlab.wcf.comment.commentableContent');
        $commentTypes = new ObjectTypeList();
        $commentTypes->getConditionBuilder()->add('object_type.definitionID = ?', [$definition->definitionID]);
        $commentTypes->readObjects();
        $this->availableCommentTypes = $commentTypes->getObjects();

        foreach ($this->availableCommentTypes as $type) {
            // try to translate...
            $temp = WCF::getLanguage()->getDynamicVariable('wcf.user.recentActivity.' . $type->objectType . '.recentActivityEvent');

            if ($type->objectType == 'com.woltlab.wcf.moderation.queue') {
                $temp = WCF::getLanguage()->get('wcf.acp.uzbot.comment.types.moderation');
            }
            if ($type->objectType == 'com.woltlab.wcf.moderatedUserGroup.application') {
                $temp = WCF::getLanguage()->get('wcf.acp.uzbot.comment.types.application');
            }

            $type->objectType = $temp;
        }

        // labels for notification
        $this->labelGroups = LabelHandler::getInstance()->getLabelGroups([], false);

        parent::readData();

        // timezone and initial time
        if (empty($_POST)) {
            $this->cirTimezone = WCF::getUser()->getTimeZone()->getName();
            $this->cirTimezoneObj = WCF::getUser()->getTimeZone();

            $d = DateUtil::getDateTimeByTimestamp(TIME_NOW + 3600);
            $d->setTimezone($this->cirTimezoneObj);
            $this->cirStartTime = $d->format('c');
        }
    }

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        // languages
        $this->isMultilingual = 0;
        $this->availableLanguages = LanguageFactory::getInstance()->getLanguages();
        if (\count($this->availableLanguages) > 1) {
            $this->isMultilingual = 1;
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'action' => 'add',

            'availableCommentTypes' => $this->availableCommentTypes,
            'availableGroups' => $this->availableGroups,
            'availableGroupsPassive' => $this->availableGroupsPassive,
            'availableLabels' => $this->availableLabels,
            'availableLanguages' => $this->availableLanguages,
            'availableNotifies' => $this->availableNotifies,
            'availableTimezones' => $this->availableTimezones,
            'availableTypes' => $this->availableTypes,
            'availableWeekdays' => $this->availableWeekdays,
            'receiverGroups' => $this->receiverGroups,

            'botDescription' => $this->botDescription,
            'botTitle' => $this->botTitle,
            'categoryID' => $this->categoryID,
            'categoryNodeList' => $this->categoryNodeTree->getIterator(),
            'enableLog' => $this->enableLog,
            'isDisabled' => $this->isDisabled,
            'testMode' => $this->testMode,

            'notifyID' => $this->notifyID,
            'articleCategoryID' => $this->articleCategoryID,
            'articleConditionCategoryID' => $this->articleConditionCategoryID,
            'articleConditionCategoryID_new' => $this->articleConditionCategoryID,
            'articleCategoryNodeList' => (new CategoryNodeTree('com.woltlab.wcf.article.category'))->getIterator(),
            'articleEnableComments' => $this->articleEnableComments,
            'articlePublicationStatus' => $this->articlePublicationStatus,
            'commentActivity' => $this->commentActivity,
            'condense' => $this->condense,
            'content' => $this->content,
            'conversationAllowAdd' => $this->conversationAllowAdd,
            'conversationClose' => $this->conversationClose,
            'conversationInvisible' => $this->conversationInvisible,
            'conversationLeave' => $this->conversationLeave,
            'conversationType' => $this->conversationType,
            'emailBCC' => $this->emailBCC,
            'emailCC' => $this->emailCC,
            'emailPrivacy' => $this->emailPrivacy,
            'emailAttachmentFile' => $this->emailAttachmentFile,
            'isMultilingual' => $this->isMultilingual,
            'labelGroups' => $this->labelGroups,
            'notifyLanguageID' => $this->notifyLanguageID,
            'receiverAffected' => $this->receiverAffected,
            'receiverGroupIDs' => $this->receiverGroupIDs,
            'receiverNames' => $this->receiverNames,
            'sendername' => $this->sendername,
            'subject' => $this->subject,
            'tags' => $this->tags,
            'teaser' => $this->teaser,
            'imageID' => $this->imageID,
            'images' => $this->images,
            'teaserImageID' => $this->teaserImageID,
            'teaserImages' => $this->teaserImages,

            'typeID' => $this->typeID,

            'actionLabelDelete' => $this->actionLabelDelete,
            'actionLabelIDs' => $this->actionLabelIDs,
            'conditionLabelIDs' => $this->conditionLabelIDs,
            'notifyLabelIDs' => $this->notifyLabelIDs,

            'articlePublished' => $this->articlePublished,
            'birthdayForce' => $this->birthdayForce,
            'changeAffected' => $this->changeAffected,
            'condenseEnable' => $this->condenseEnable,

            'receiverConditions' => $this->receiverConditions,
            'userConditions' => $this->userConditions,
            'userBotConditions' => $this->userBotConditions,

            'cirCounter' => $this->cirCounter,
            'cirCounterInterval' => $this->cirCounterInterval,
            'cirRepeatCount' => $this->cirRepeatCount,
            'cirRepeatType' => $this->cirRepeatType,
            'cirEndTime' => $this->cirEndTime,
            'cirStartTime' => $this->cirStartTime,
            'cirTimezone' => $this->cirTimezone,
            'cirMonthlyDoM_day' => $this->cirMonthlyDoM_day,
            'cirMonthlyDoW_index' => $this->cirMonthlyDoW_index,
            'cirMonthlyDoW_day' => $this->cirMonthlyDoW_day,
            'cirWeekly_day' => $this->cirWeekly_day,
            'cirYearlyDoM_day' => $this->cirYearlyDoM_day,
            'cirYearlyDoM_month' => $this->cirYearlyDoM_month,
            'cirYearlyDoW_day' => $this->cirYearlyDoW_day,
            'cirYearlyDoW_index' => $this->cirYearlyDoW_index,
            'cirYearlyDoW_month' => $this->cirYearlyDoW_month,

            'commentDays' => $this->commentDays,
            'commentDaysAfter' => $this->commentDaysAfter,
            'commentNoAnswers' => $this->commentNoAnswers,
            'commentNoUser' => $this->commentNoUser,
            'commentTypeIDs' => $this->commentTypeIDs,

            'conversationDays' => $this->conversationDays,
            'conversationDaysAfter' => $this->conversationDaysAfter,
            'conversationNoAnswers' => $this->conversationNoAnswers,
            'conversationNoLabels' => $this->conversationNoLabels,

            'feedreaderExclude' => $this->feedreaderExclude,
            'feedreaderFrequency' => $this->feedreaderFrequency,
            'feedreaderInclude' => $this->feedreaderInclude,
            'feedreaderMaxAge' => $this->feedreaderMaxAge,
            'feedreaderMaxItems' => $this->feedreaderMaxItems,
            'feedreaderUseTags' => $this->feedreaderUseTags,
            'feedreaderUseTime' => $this->feedreaderUseTime,
            'feedreaderUrl' => $this->feedreaderUrl,

            'groupAssignmentGroupID' => $this->groupAssignmentGroupID,
            'groupAssignmentAction' => $this->groupAssignmentAction,
            'groupChangeGroupIDs' => $this->groupChangeGroupIDs,
            'groupChangeType' => $this->groupChangeType,

            'inactiveAction' => $this->inactiveAction,
            'inactiveBanReason' => $this->inactiveBanReason,
            'inactiveReminderLimit' => $this->inactiveReminderLimit,

            'likeAction' => $this->likeAction,

            'userCount' => $this->userCount,
            'userCreationGroupID' => $this->userCreationGroupID,
            'userSettingAvatarOption' => $this->userSettingAvatarOption,
            'userSettingCover' => $this->userSettingCover,
            'userSettingEmail' => $this->userSettingEmail,
            'userSettingOther' => $this->userSettingOther,
            'userSettingSelfDeletion' => $this->userSettingSelfDeletion,
            'userSettingSignature' => $this->userSettingSignature,
            'userSettingUsername' => $this->userSettingUsername,
            'userSettingUserTitle' => $this->userSettingUserTitle,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        parent::readFormParameters();

        // general
        $this->enableLog = $this->isDisabled = $this->testMode = 0;
        if (isset($_POST['botDescription'])) {
            $this->botDescription = StringUtil::trim($_POST['botDescription']);
        }
        if (isset($_POST['botTitle'])) {
            $this->botTitle = StringUtil::trim($_POST['botTitle']);
        }
        if (isset($_POST['categoryID'])) {
            $this->categoryID = \intval($_POST['categoryID']);
        }
        if (isset($_POST['enableLog'])) {
            $this->enableLog = 1;
        }
        if (isset($_POST['isDisabled'])) {
            $this->isDisabled = 1;
        }
        if (isset($_POST['testMode'])) {
            $this->testMode = 1;
        }

        // read notify data
        $this->articlePublicationStatus = $this->commentActivity = $this->emailPrivacy = 0;
        if (isset($_POST['notifyID'])) {
            $this->notifyID = \intval($_POST['notifyID']);
        }
        if (isset($_POST['articleCategoryID'])) {
            $this->articleCategoryID = \intval($_POST['articleCategoryID']);
        }
        if (isset($_POST['articleConditionCategoryID'])) {
            $this->articleConditionCategoryID = \intval($_POST['articleConditionCategoryID']);
        }
        if (isset($_POST['articleConditionCategoryID_new'])) {
            $this->articleConditionCategoryID_new = \intval($_POST['articleConditionCategoryID_new']);
        }
        if (isset($_POST['articleEnableComments'])) {
            $this->articleEnableComments = \intval($_POST['articleEnableComments']);
        }
        if (isset($_POST['articlePublicationStatus'])) {
            $this->articlePublicationStatus = \intval($_POST['articlePublicationStatus']);
        }
        if (isset($_POST['commentActivity'])) {
            $this->commentActivity = \intval($_POST['commentActivity']);
        }
        if (isset($_POST['condense']) && \is_array($_POST['condense'])) {
            $this->condense = ArrayUtil::trim($_POST['condense']);
        }
        if (isset($_POST['content']) && \is_array($_POST['content'])) {
            $this->content = ArrayUtil::trim($_POST['content']);
        }
        if (isset($_POST['conversationAllowAdd'])) {
            $this->conversationAllowAdd = \intval($_POST['conversationAllowAdd']);
        }
        if (isset($_POST['conversationClose'])) {
            $this->conversationClose = \intval($_POST['conversationClose']);
        }
        if (isset($_POST['conversationInvisible'])) {
            $this->conversationInvisible = StringUtil::trim($_POST['conversationInvisible']);
        }
        if (isset($_POST['conversationLeave'])) {
            $this->conversationLeave = \intval($_POST['conversationLeave']);
        }
        if (isset($_POST['conversationType'])) {
            $this->conversationType = \intval($_POST['conversationType']);
        }
        if (isset($_POST['emailBCC'])) {
            $this->emailBCC = StringUtil::trim($_POST['emailBCC']);
        }
        if (isset($_POST['emailCC'])) {
            $this->emailCC = StringUtil::trim($_POST['emailCC']);
        }
        if (isset($_POST['emailPrivacy'])) {
            $this->emailPrivacy = \intval($_POST['emailPrivacy']);
        }
        if (isset($_POST['emailAttachmentFile'])) {
            $this->emailAttachmentFile = StringUtil::trim($_POST['emailAttachmentFile']);
        }
        if (isset($_POST['notifyLanguageID'])) {
            $this->notifyLanguageID = \intval($_POST['notifyLanguageID']);
        }
        if (isset($_POST['receiverAffected'])) {
            $this->receiverAffected = \intval($_POST['receiverAffected']);
        }
        if (isset($_POST['receiverGroupIDs']) && \is_array($_POST['receiverGroupIDs'])) {
            $this->receiverGroupIDs = ArrayUtil::toIntegerArray($_POST['receiverGroupIDs']);
        }
        if (isset($_POST['receiverNames'])) {
            $this->receiverNames = StringUtil::trim($_POST['receiverNames']);
        }
        if (isset($_POST['sendername'])) {
            $this->sendername = StringUtil::trim($_POST['sendername']);
        }
        if (isset($_POST['subject']) && \is_array($_POST['subject'])) {
            $this->subject = ArrayUtil::trim($_POST['subject']);
        }
        if (MODULE_TAGGING && isset($_POST['tags']) && \is_array($_POST['tags'])) {
            $this->tags = ArrayUtil::trim($_POST['tags']);
        }
        if (isset($_POST['teaser']) && \is_array($_POST['teaser'])) {
            $this->teaser = ArrayUtil::trim($_POST['teaser']);
        }

        if (WCF::getSession()->getPermission('admin.content.cms.canUseMedia')) {
            if (isset($_POST['imageID']) && \is_array($_POST['imageID'])) {
                $this->imageID = ArrayUtil::toIntegerArray($_POST['imageID']);
            }
            if (isset($_POST['teaserImageID']) && \is_array($_POST['teaserImageID'])) {
                $this->teaserImageID = ArrayUtil::toIntegerArray($_POST['teaserImageID']);
            }

            $this->readImages();
        }

        // read conditions
        foreach ($this->receiverConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->readFormParameters();
            }
        }
        foreach ($this->userConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->readFormParameters();
            }
        }
        foreach ($this->userBotConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->readFormParameters();
            }
        }

        if (isset($_POST['typeID'])) {
            $this->typeID = \intval($_POST['typeID']);
        }

        if (isset($_POST['actionLabelIDs']) && \is_array($_POST['actionLabelIDs'])) {
            $this->actionLabelIDs = $_POST['actionLabelIDs'];
        } else {
            $this->actionLabelIDs = [];
        }
        if (isset($_POST['conditionLabelIDs']) && \is_array($_POST['conditionLabelIDs'])) {
            $this->conditionLabelIDs = $_POST['conditionLabelIDs'];
        } else {
            $this->conditionLabelIDs = [];
        }
        if (isset($_POST['notifyLabelIDs']) && \is_array($_POST['notifyLabelIDs'])) {
            $this->notifyLabelIDs = $_POST['notifyLabelIDs'];
        } else {
            $this->notifyLabelIDs = [];
        }

        $this->condenseEnable = $this->actionLabelDelete = $this->articlePublished = $this->birthdayForce = $this->changeAffected = 0;
        if (isset($_POST['actionLabelDelete'])) {
            $this->actionLabelDelete = \intval($_POST['actionLabelDelete']);
        }
        if (isset($_POST['articlePublished'])) {
            $this->articlePublished = \intval($_POST['articlePublished']);
        }
        if (isset($_POST['birthdayForce'])) {
            $this->birthdayForce = \intval($_POST['birthdayForce']);
        }
        if (isset($_POST['changeAffected'])) {
            $this->changeAffected = \intval($_POST['changeAffected']);
        }
        if (isset($_POST['condenseEnable'])) {
            $this->condenseEnable = \intval($_POST['condenseEnable']);
        }

        if (isset($_POST['cirCounterInterval'])) {
            $this->cirCounterInterval = \intval($_POST['cirCounterInterval']);
        }
        if (isset($_POST['cirCounter'])) {
            $this->cirCounter = \intval($_POST['cirCounter']);
        }
        if (isset($_POST['cirRepeatCount'])) {
            $this->cirRepeatCount = \intval($_POST['cirRepeatCount']);
        }
        if (isset($_POST['cirRepeatType'])) {
            $this->cirRepeatType = $_POST['cirRepeatType'];
        }
        if (isset($_POST['cirStartTime'])) {
            $this->cirStartTime = $_POST['cirStartTime'];
        }
        if (isset($_POST['cirTimezone'])) {
            $this->cirTimezone = $_POST['cirTimezone'];
        }
        if (isset($_POST['cirMonthlyDoM_day'])) {
            $this->cirMonthlyDoM_day = \intval($_POST['cirMonthlyDoM_day']);
        }
        if (isset($_POST['cirMonthlyDoW_index'])) {
            $this->cirMonthlyDoW_index = \intval($_POST['cirMonthlyDoW_index']);
        }
        if (isset($_POST['cirMonthlyDoW_day'])) {
            $this->cirMonthlyDoW_day = \intval($_POST['cirMonthlyDoW_day']);
        }
        if (isset($_POST['cirWeekly_day'])) {
            $this->cirWeekly_day = \intval($_POST['cirWeekly_day']);
        }
        if (isset($_POST['cirYearlyDoM_day'])) {
            $this->cirYearlyDoM_day = \intval($_POST['cirYearlyDoM_day']);
        }
        if (isset($_POST['cirYearlyDoM_month'])) {
            $this->cirYearlyDoM_month = \intval($_POST['cirYearlyDoM_month']);
        }
        if (isset($_POST['cirYearlyDoW_day'])) {
            $this->cirYearlyDoW_day = \intval($_POST['cirYearlyDoW_day']);
        }
        if (isset($_POST['cirYearlyDoW_index'])) {
            $this->cirYearlyDoW_index = \intval($_POST['cirYearlyDoW_index']);
        }
        if (isset($_POST['cirYearlyDoW_month'])) {
            $this->cirYearlyDoW_month = \intval($_POST['cirYearlyDoW_month']);
        }

        // calculate times
        try {
            $this->cirTimezoneObj = new DateTimeZone($this->cirTimezone);
        } catch (Exception $e) {
            $this->cirTimezoneObj = WCF::getUser()->getTimeZone();
        }

        $this->cirStartDateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', $this->cirStartTime, $this->cirTimezoneObj);
        if ($this->cirStartDateTime !== false) {
            $this->cirStartTime = $this->cirStartDateTime->format('c');
        }

        $this->commentNoAnswers = $this->commentNoUser = 0;
        if (isset($_POST['commentDays'])) {
            $this->commentDays = \intval($_POST['commentDays']);
        }
        if (isset($_POST['commentDaysAfter'])) {
            $this->commentDaysAfter = StringUtil::trim($_POST['commentDaysAfter']);
        }
        if (isset($_POST['commentNoAnswers'])) {
            $this->commentNoAnswers = \intval($_POST['commentNoAnswers']);
        }
        if (isset($_POST['commentNoUser'])) {
            $this->commentNoUser = \intval($_POST['commentNoUser']);
        }
        if (isset($_POST['commentTypeIDs']) && \is_array($_POST['commentTypeIDs'])) {
            $this->commentTypeIDs = ArrayUtil::toIntegerArray($_POST['commentTypeIDs']);
        }

        $this->conversationNoAnswers = $this->conversationNoLabels = 0;
        if (isset($_POST['conversationDays'])) {
            $this->conversationDays = \intval($_POST['conversationDays']);
        }
        if (isset($_POST['conversationDaysAfter'])) {
            $this->conversationDaysAfter = StringUtil::trim($_POST['conversationDaysAfter']);
        }
        if (isset($_POST['conversationNoAnswers'])) {
            $this->conversationNoAnswers = \intval($_POST['conversationNoAnswers']);
        }
        if (isset($_POST['conversationNoLabels'])) {
            $this->conversationNoLabels = \intval($_POST['conversationNoLabels']);
        }

        // feedreader
        $this->feedreaderUseTags = $this->feedreaderUseTime = 0;
        if (isset($_POST['feedreaderExclude'])) {
            $this->feedreaderExclude = StringUtil::trim($_POST['feedreaderExclude']);
        }
        if (isset($_POST['feedreaderFrequency'])) {
            $this->feedreaderFrequency = \intval($_POST['feedreaderFrequency']);
        }
        if (isset($_POST['feedreaderInclude'])) {
            $this->feedreaderInclude = StringUtil::trim($_POST['feedreaderInclude']);
        }
        if (isset($_POST['feedreaderMaxAge'])) {
            $this->feedreaderMaxAge = \intval($_POST['feedreaderMaxAge']);
        }
        if (isset($_POST['feedreaderMaxItems'])) {
            $this->feedreaderMaxItems = \intval($_POST['feedreaderMaxItems']);
        }
        if (isset($_POST['feedreaderUseTags'])) {
            $this->feedreaderUseTags = \intval($_POST['feedreaderUseTags']);
        }
        if (isset($_POST['feedreaderUseTime'])) {
            $this->feedreaderUseTime = \intval($_POST['feedreaderUseTime']);
        }
        if (isset($_POST['feedreaderUrl'])) {
            $this->feedreaderUrl = StringUtil::trim($_POST['feedreaderUrl']);
        }

        if (isset($_POST['groupAssignmentGroupID'])) {
            $this->groupAssignmentGroupID = \intval($_POST['groupAssignmentGroupID']);
        }
        if (isset($_POST['groupAssignmentAction'])) {
            $this->groupAssignmentAction = StringUtil::trim($_POST['groupAssignmentAction']);
        }
        if (isset($_POST['groupChangeGroupIDs']) && \is_array($_POST['groupChangeGroupIDs'])) {
            $this->groupChangeGroupIDs = ArrayUtil::toIntegerArray($_POST['groupChangeGroupIDs']);
        }
        $this->groupChangeType = 0;
        if (isset($_POST['groupChangeType'])) {
            $this->groupChangeType = \intval($_POST['groupChangeType']);
        }

        if (isset($_POST['inactiveAction'])) {
            $this->inactiveAction = StringUtil::trim($_POST['inactiveAction']);
        }
        if (isset($_POST['inactiveBanReason'])) {
            $this->inactiveBanReason = StringUtil::trim($_POST['inactiveBanReason']);
        }
        if (isset($_POST['inactiveReminderLimit'])) {
            $this->inactiveReminderLimit = \intval($_POST['inactiveReminderLimit']);
        }

        if (isset($_POST['likeAction'])) {
            $this->likeAction = StringUtil::trim($_POST['likeAction']);
        }

        if (isset($_POST['userCount'])) {
            $this->userCount = StringUtil::trim($_POST['userCount']);
        }
        if (isset($_POST['userCreationGroupID'])) {
            $this->userCreationGroupID = \intval($_POST['userCreationGroupID']);
        }

        $this->userSettingAvatarOption = $this->userSettingEmail = $this->userSettingOther = $this->userSettingSelfDeletion = 0;
        $this->userSettingSignature = $this->userSettingUsername = $this->userSettingUserTitle = $this->userSettingCover = 0;
        if (isset($_POST['userSettingAvatarOption'])) {
            $this->userSettingAvatarOption = \intval($_POST['userSettingAvatarOption']);
        }
        if (isset($_POST['userSettingCover'])) {
            $this->userSettingCover = \intval($_POST['userSettingCover']);
        }
        if (isset($_POST['userSettingEmail'])) {
            $this->userSettingEmail = \intval($_POST['userSettingEmail']);
        }
        if (isset($_POST['userSettingOther'])) {
            $this->userSettingOther = \intval($_POST['userSettingOther']);
        }
        if (isset($_POST['userSettingSelfDeletion'])) {
            $this->userSettingSelfDeletion = \intval($_POST['userSettingSelfDeletion']);
        }
        if (isset($_POST['userSettingSignature'])) {
            $this->userSettingSignature = \intval($_POST['userSettingSignature']);
        }
        if (isset($_POST['userSettingUsername'])) {
            $this->userSettingUsername = \intval($_POST['userSettingUsername']);
        }
        if (isset($_POST['userSettingUserTitle'])) {
            $this->userSettingUserTitle = \intval($_POST['userSettingUserTitle']);
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        // texts
        $content = [];
        if ($this->isMultilingual) {
            foreach (LanguageFactory::getInstance()->getLanguages() as $language) {
                $content[$language->languageID] = [
                    'condense' => !empty($this->condense[$language->languageID]) ? $this->condense[$language->languageID] : '',
                    'content' => !empty($this->content[$language->languageID]) ? $this->content[$language->languageID] : '',
                    'subject' => !empty($this->subject[$language->languageID]) ? $this->subject[$language->languageID] : '',
                    'tags' => !empty($this->tags[$language->languageID]) ? $this->tags[$language->languageID] : [],
                    'teaser' => !empty($this->teaser[$language->languageID]) ? $this->teaser[$language->languageID] : '',
                    'htmlInputProcessor' => $this->htmlInputProcessors[$language->languageID] ?? null,
                    'imageID' => !empty($this->imageID[$language->languageID]) ? $this->imageID[$language->languageID] : null,
                    'teaserImageID' => !empty($this->teaserImageID[$language->languageID]) ? $this->teaserImageID[$language->languageID] : null,
                ];
            }
        } else {
            $content[0] = [
                'condense' => !empty($this->condense[0]) ? $this->condense[0] : '',
                'content' => !empty($this->content[0]) ? $this->content[0] : '',
                'subject' => !empty($this->subject[0]) ? $this->subject[0] : '',
                'tags' => !empty($this->tags[0]) ? $this->tags[0] : [],
                'teaser' => !empty($this->teaser[0]) ? $this->teaser[0] : '',
                'htmlInputProcessor' => $this->htmlInputProcessors[0] ?? null,
                'imageID' => !empty($this->imageID[0]) ? $this->imageID[0] : null,
                'teaserImageID' => !empty($this->teaserImageID[0]) ? $this->teaserImageID[0] : null,
            ];
        }

        $cirData = [
            'cirRepeatCount' => $this->cirRepeatCount,
            'cirRepeatType' => $this->cirRepeatType,
            'cirStartTime' => $this->cirStartTime,
            'cirTimezone' => $this->cirTimezone,
            'cirMonthlyDoM_day' => $this->cirMonthlyDoM_day,
            'cirMonthlyDoW_index' => $this->cirMonthlyDoW_index,
            'cirMonthlyDoW_day' => $this->cirMonthlyDoW_day,
            'cirWeekly_day' => $this->cirWeekly_day,
            'cirYearlyDoM_day' => $this->cirYearlyDoM_day,
            'cirYearlyDoM_month' => $this->cirYearlyDoM_month,
            'cirYearlyDoW_day' => $this->cirYearlyDoW_day,
            'cirYearlyDoW_index' => $this->cirYearlyDoW_index,
            'cirYearlyDoW_month' => $this->cirYearlyDoW_month,
        ];

        $execs = UzbotUtils::calcExecution($cirData);

        // data
        $data = [
            'botDescription' => $this->botDescription,
            'botTitle' => $this->botTitle,
            'categoryID' => $this->categoryID ? $this->categoryID : '',
            'enableLog' => $this->enableLog,
            'isDisabled' => $this->isDisabled,
            'testMode' => $this->testMode,
            'isMultilingual' => $this->isMultilingual,

            'notifyID' => $this->notifyID,
            'notifyDes' => $this->notifyID ? $this->notify->notifyTitle : 'none',
            'articleCategoryID' => $this->articleCategoryID ? $this->articleCategoryID : null,
            'articleConditionCategoryID' => $this->articleConditionCategoryID,
            'articleEnableComments' => $this->articleEnableComments,
            'articlePublicationStatus' => $this->articlePublicationStatus,
            'commentActivity' => $this->commentActivity,
            'conversationAllowAdd' => $this->conversationAllowAdd,
            'conversationClose' => $this->conversationClose,
            'conversationInvisible' => $this->conversationInvisible,
            'conversationLeave' => $this->conversationLeave,
            'conversationType' => $this->conversationType,
            'emailBCC' => $this->emailBCC,
            'emailCC' => $this->emailCC,
            'emailPrivacy' => $this->emailPrivacy,
            'emailAttachmentFile' => $this->emailAttachmentFile,
            'notifyLanguageID' => $this->notifyLanguageID,
            'receiverAffected' => $this->receiverAffected,
            'receiverGroupIDs' => \serialize($this->receiverGroupIDs),
            'receiverNames' => $this->receiverNames,
            'senderID' => $this->notifyID ? $this->sender->userID : null,
            'sendername' => $this->notifyID ? $this->sender->username : '',

            'typeID' => $this->typeID,
            'typeDes' => $this->typeID ? $this->type->typeTitle : '',

            'actionLabelDelete' => $this->actionLabelDelete,
            'articlePublished' => $this->articlePublished,
            'birthdayForce' => $this->birthdayForce,
            'changeAffected' => $this->changeAffected,
            'condenseEnable' => $this->condenseEnable,

            'cirCounter' => $this->cirCounter,
            'cirCounterInterval' => $this->cirCounterInterval,
            'cirData' => \serialize($cirData),
            'cirExecution' => \serialize($execs),

            'commentDays' => $this->commentDays,
            'commentDaysAfter' => $this->commentDaysAfter,
            'commentNoAnswers' => $this->commentNoAnswers,
            'commentNoUser' => $this->commentNoUser,
            'commentTypeIDs' => \serialize($this->commentTypeIDs),

            'conversationDays' => $this->conversationDays,
            'conversationDaysAfter' => $this->conversationDaysAfter,
            'conversationNoAnswers' => $this->conversationNoAnswers,
            'conversationNoLabels' => $this->conversationNoLabels,

            'feedreaderExclude' => $this->feedreaderExclude,
            'feedreaderFrequency' => $this->feedreaderFrequency,
            'feedreaderInclude' => $this->feedreaderInclude,
            'feedreaderMaxAge' => $this->feedreaderMaxAge,
            'feedreaderMaxItems' => $this->feedreaderMaxItems,
            'feedreaderUseTags' => $this->feedreaderUseTags,
            'feedreaderUseTime' => $this->feedreaderUseTime,
            'feedreaderUrl' => $this->feedreaderUrl,

            'groupAssignmentGroupID' => $this->groupAssignmentGroupID,
            'groupAssignmentAction' => $this->groupAssignmentAction,
            'groupChangeGroupIDs' => \serialize($this->groupChangeGroupIDs),
            'groupChangeType' => $this->groupChangeType,

            'inactiveAction' => $this->inactiveAction,
            'inactiveBanReason' => $this->inactiveBanReason,
            'inactiveReminderLimit' => $this->inactiveReminderLimit,

            'likeAction' => $this->likeAction,

            'userCount' => $this->userCount,
            'userCreationGroupID' => $this->userCreationGroupID,
            'userSettingAvatarOption' => $this->userSettingAvatarOption,
            'userSettingCover' => $this->userSettingCover,
            'userSettingEmail' => $this->userSettingEmail,
            'userSettingOther' => $this->userSettingOther,
            'userSettingSelfDeletion' => $this->userSettingSelfDeletion,
            'userSettingSignature' => $this->userSettingSignature,
            'userSettingUsername' => $this->userSettingUsername,
            'userSettingUserTitle' => $this->userSettingUserTitle,
        ];

        // save
        $objectAction = new UzbotAction([], 'create', [
            'data' => \array_merge($this->additionalFields, $data),
            'content' => $content,
            'actionLabelIDs' => $this->actionLabelIDs,
            'conditionLabelIDs' => $this->conditionLabelIDs,
            'notifyLabelIDs' => $this->notifyLabelIDs,
        ]);
        $objectAction->executeAction();

        $returnValues = $objectAction->getReturnValues();
        $uzBotEditor = new UzbotEditor($returnValues['returnValues']);
        $this->botID = $returnValues['returnValues']->botID;

        // transform conditions array into one-dimensional array and save
        $conditions = [];
        foreach ($this->receiverConditions as $groupedObjectTypes) {
            $conditions = \array_merge($conditions, $groupedObjectTypes);
        }
        ConditionHandler::getInstance()->createConditions($returnValues['returnValues']->botID, $conditions);

        $conditions = [];
        foreach ($this->userConditions as $groupedObjectTypes) {
            $conditions = \array_merge($conditions, $groupedObjectTypes);
        }
        ConditionHandler::getInstance()->createConditions($returnValues['returnValues']->botID, $conditions);

        $conditions = [];
        foreach ($this->userBotConditions as $groupedObjectTypes) {
            $conditions = \array_merge($conditions, $groupedObjectTypes);
        }
        ConditionHandler::getInstance()->createConditions($returnValues['returnValues']->botID, $conditions);

        // Reset values
        $this->botDescription = '';
        $this->botTitle = '';
        $this->categoryID = 0;
        $this->enableLog = 1;
        $this->isDisabled = 0;
        $this->testMode = 0;

        $this->notify = null;
        $this->notifyID = 0;
        $this->articleCategoryID = 0;
        $this->articleConditionCategoryID = 0;
        $this->articleEnableComments = 0;
        $this->articlePublicationStatus = 1;
        $this->commentActivity = 1;
        $this->condense = [];
        $this->content = [];
        $this->conversationAllowAdd = 0;
        $this->conversationClose = 0;
        $this->conversationInvisible = '';
        $this->conversationLeave = 0;
        $this->conversationType = 0;
        $this->emailBCC = '';
        $this->emailCC = '';
        $this->emailPrivacy = 0;
        $this->emailAttachmentFile = '';
        $this->notifyLanguageID = 0;
        $this->receiverAffected = 0;
        $this->receiverGroupIDs = [];
        $this->receiverNames = '';
        $this->sendername = '';
        $this->subject = [];
        $this->tags = [];
        $this->teaser = [];

        $this->images = $this->imageID = [];
        $this->teaserImages = $this->teaserImageID = [];

        // reset conditions
        foreach ($this->receiverConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->reset();
            }
        }
        foreach ($this->userConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->reset();
            }
        }
        foreach ($this->userBotConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->reset();
            }
        }

        $this->type = null;
        $this->typeID = 0;

        $this->actionLabelIDs = [];
        $this->conditionLabelIDs = [];
        $this->notifyLabelIDs = [];

        $this->actionLabelDelete = 0;
        $this->articlePublished = 0;
        $this->birthdayForce = 1;
        $this->changeAffected = 0;
        $this->condenseEnable = 0;

        $this->cirCounter = 0;
        $this->cirCounterInterval = 0;
        $this->cirEndTime = '';
        $this->cirEndDateTime = null;
        $this->cirRepeatCount = 1;
        $this->cirRepeatType = 'none';
        $this->cirMonthlyDoM_day = 1;
        $this->cirMonthlyDoW_index = 1;
        $this->cirMonthlyDoW_day = 1;
        $this->cirWeekly_day = 1;
        $this->cirYearlyDoM_day = 1;
        $this->cirYearlyDoM_month = 1;
        $this->cirYearlyDoW_day = 1;
        $this->cirYearlyDoW_index = 1;
        $this->cirYearlyDoW_month = 1;

        $this->commentDays = 365;
        $this->commentDaysAfter = 'reply';
        $this->commentNoAnswers = 0;
        $this->commentNoUser = 0;
        $this->commentTypeIDs = [];

        $this->conversationDays = 365;
        $this->conversationDaysAfter = 'reply';
        $this->conversationNoAnswers = 1;
        $this->conversationNoLabels = 0;
        $this->commentTypeIDs = [];

        $this->feedreaderExclude = '';
        $this->feedreaderFrequency = 1800;
        $this->feedreaderInclude = '';
        $this->feedreaderMaxAge = 0;
        $this->feedreaderMaxItems = 0;
        $this->feedreaderUseTags = 0;
        $this->feedreaderUseTime = 0;
        $this->feedreaderUrl = '';

        $this->groupAssignmentGroupID = 0;
        $this->groupAssignmentAction = 'add';
        $this->groupChangeGroupIDs = [];
        $this->groupChangeType = 0;

        $this->inactiveAction = 'remind';
        $this->inactiveBanReason = '';
        $this->inactiveReminderLimit = 1;

        $this->likeAction = 'likeTotal';

        $this->userCount = '';
        $this->userCreationGroupID = 0;
        $this->userSettingAvatarOption = 0;
        $this->userSettingCover = 0;
        $this->userSettingEmail = 0;
        $this->userSettingOther = 0;
        $this->userSettingSelfDeletion = 0;
        $this->userSettingSignature = 0;
        $this->userSettingUsername = 0;
        $this->userSettingUserTitle = 0;

        // reset cache
        UzbotEditor::resetCache();

        // log action
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        UzbotLogEditor::create([
            'bot' => $returnValues['returnValues'],
            'count' => 1,
            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.bot.created', [
                'username' => WCF::getUser()->username,
            ]),
        ]);

        $this->saved();

        // Show success message
        WCF::getTPL()->assign('success', true);
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        parent::validate();

        // bot title required, max 80 chars
        if (empty($this->botTitle)) {
            throw new UserInputException('botTitle', 'required');
        }
        if (\mb_strlen($this->botTitle) > 80) {
            throw new UserInputException('botTitle', 'tooLong');
        }

        // bot description not required, max 255 chars
        if (\mb_strlen($this->botDescription) > 255) {
            throw new UserInputException('botDescription', 'tooLong');
        }

        // bot category must exist
        if (!$this->categoryID) {
            throw new UserInputException('categoryID', 'missing');
        }
        $category = new Category($this->categoryID);
        if (!$category->categoryID) {
            throw new UserInputException('categoryID', 'notValid');
        }

        // type must exist
        if (!$this->typeID) {
            throw new UserInputException('typeID', 'missing');
        }
        $this->type = UzbotType::getTypeByID($this->typeID);

        // must be enabled and have logging in testMode
        if ($this->testMode && ($this->isDisabled || !$this->enableLog)) {
            throw new UserInputException('testMode', 'misconfigured');
        }

        // prevent change of system
        if (isset($this->botID) && $this->botID != 0) {
            $sql = "SELECT        COUNT(*)
                    FROM        wcf" . WCF_N . "_uzbot_system
                    WHERE        botID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$this->botID]);
            $count = $statement->fetchSingleColumn();
            if ($count) {
                if ($this->type->typeTitle != 'user_inactivity' || $this->inactiveAction != 'delete' || !$this->receiverAffected || $this->notifyID != 2) {
                    throw new UserInputException('typeID', 'noChangeAllowed');
                }
            }
        }

        // switch off condense
        if (!$this->type->canCondense) {
            $this->condenseEnable = 0;
        }

        // feedreader
        if ($this->type->typeTitle == 'system_feedreader') {
            if (empty($this->feedreaderUrl)) {
                throw new UserInputException('feedreaderUrl');
            }

            // test feed
            try {
                $request = new HTTPRequest($this->feedreaderUrl);
                $request->execute();
                $result = $request->getReply();
            } catch (SystemException $e) {
                throw new UserInputException('feedreaderUrl', 'invalid');
            }
            if ($result['statusCode'] != 200) {
                throw new UserInputException('feedreaderUrl', 'invalid');
            }
        }

        // groupAssignment
        if ($this->type->typeTitle == 'user_groupAssignment') {
            if (!isset($this->availableGroups[$this->groupAssignmentGroupID])) {
                throw new UserInputException('groupAssignmentGroupID', 'invalidSelection');
            }
        } else {
            $this->groupAssignmentGroupID = null;
        }

        // need Count for trigger values
        if ($this->type->needCount) {
            $counts = ArrayUtil::trim(\explode(',', $this->userCount));
            $counts = ArrayUtil::toIntegerArray($counts);

            if (!empty($this->type->needCountAction)) {
                $temp = $this->type->needCountAction;

                // exceptions, add dislikeNotifyReceiver
                $without = \explode(',', $this->type->needCountNo);
                $without[] = 'dislikeNotifyRe';

                if (isset($this->{$temp}) && !\in_array($this->{$temp}, $without)) {
                    if (!\count($counts)) {
                        throw new UserInputException('userCount', 'empty');
                    }
                }
            } else {
                if (!\count($counts)) {
                    throw new UserInputException('userCount', 'empty');
                }
            }

            $this->userCount = \implode(',', $counts);
        }

        // notify may exist
        if ($this->type->needNotify && !$this->notifyID) {
            throw new UserInputException('notifyID', 'missing');
        }
        if ($this->notifyID) {
            $this->notify = UzbotNotify::getNotifyByID($this->notifyID);

            if (empty($this->sendername)) {
                throw new UserInputException('sendername');
            }
            $this->sender = User::getUserByUsername($this->sendername);
            if (!$this->sender->userID) {
                throw new UserInputException('sendername', 'notFound');
            }

            // article category
            if ($this->notify->notifyTitle == 'article') {
                if (empty($this->articleCategoryID)) {
                    throw new UserInputException('articleCategoryID');
                }
                $category = ArticleCategory::getCategory($this->articleCategoryID);
                if ($category === null) {
                    throw new UserInputException('articleCategoryID', 'invalid');
                }
            }

            // email CC and BCC
            if ($this->notify->notifyTitle == 'email') {
                if (!empty($this->emailBCC)) {
                    $emails = ArrayUtil::trim(\explode(",", $this->emailBCC));
                    foreach ($emails as $email) {
                        if (!UserUtil::isValidEmail($email)) {
                            throw new UserInputException('emailBCC', 'notValid');
                        }
                    }
                }

                if (!empty($this->emailCC)) {
                    $emails = ArrayUtil::trim(\explode(",", $this->emailCC));
                    foreach ($emails as $email) {
                        if (!UserUtil::isValidEmail($email)) {
                            throw new UserInputException('emailCC', 'notValid');
                        }
                    }
                }
            }

            // email file
            if ($this->notify->notifyTitle == 'email') {
                if (!empty($this->emailAttachmentFile)) {
                    if (!\is_file($this->emailAttachmentFile) || !\is_readable($this->emailAttachmentFile)) {
                        throw new UserInputException('emailAttachmentFile', 'notValid');
                    }
                }
            }

            // receiver
            if ($this->notify->hasReceiver) {
                // affected
                if ($this->receiverAffected && !$this->type->hasAffected) {
                    $this->receiverAffected = 0;
                }

                // must have at least one receiver type
                if (!$this->receiverAffected && empty($this->receiverNames) && empty($this->receiverGroupIDs)) {
                    throw new UserInputException('receiverGroupIDs', 'notConfigured');
                }
                // check names
                $names = UserProfile::getUserProfilesByUsername(ArrayUtil::trim(\explode(',', $this->receiverNames)));
                foreach ($names as $name => $user) {
                    if ($user === null) {
                        WCF::getTPL()->assign('name', $name);
                        throw new UserInputException('receiverNames', 'invalid');
                    }
                }

                // groups
                if (!empty($this->receiverGroupIDs)) {
                    if (\count(\array_diff($this->receiverGroupIDs, $this->availableGroupsPassiveIDs))) {
                        throw new UserInputException('receiverGroupIDs', 'invalidGroup');
                    }
                }
            }

            // sender may not be invisible receiver on conversations
            if ($this->notify->notifyTitle == 'conversation') {
                if (!empty($this->conversationInvisible)) {
                    $invisibles = ArrayUtil::trim(\explode(',', $this->conversationInvisible));

                    if (\in_array($this->sendername, $invisibles)) {
                        throw new UserInputException('conversationInvisible', 'sender');
                    }
                }
            }

            // texts
            if ($this->isMultilingual) {
                foreach ($this->availableLanguages as $language) {
                    if ($this->notify->hasSubject == 1 && empty($this->subject[$language->languageID])) {
                        throw new UserInputException('subject' . $language->languageID);
                    }

                    // presently no notification strictly requires a teaser => skip UserInputException, but leave code for later use
                    // if ($this->notify->hasTeaser == 1 && empty($this->teaser[$language->languageID])) throw new UserInputException('teaser'.$language->languageID);

                    if (empty($this->content[$language->languageID])) {
                        throw new UserInputException('content' . $language->languageID);
                    }

                    $this->htmlInputProcessors[$language->languageID] = new HtmlInputProcessor();
                    $this->htmlInputProcessors[$language->languageID]->process($this->content[$language->languageID], 'com.uz.wcf.bot.content', 0);

                    if ($this->condenseEnable == 1 && empty($this->condense[$language->languageID])) {
                        throw new UserInputException('condense' . $language->languageID);
                    }
                }
            } else {
                if ($this->notify->hasSubject == 1 && empty($this->subject[0])) {
                    throw new UserInputException('subject');
                }

                // presently no notification strictly requires a teaser => skip UserInputException, but leave code for later use
                //if ($this->notify->hasTeaser == 1 && empty($this->teaser[0])) throw new UserInputException('teaser');

                if (empty($this->content[0])) {
                    throw new UserInputException('content');
                }

                $this->htmlInputProcessors[0] = new HtmlInputProcessor();
                $this->htmlInputProcessors[0]->process($this->content[0], 'com.uz.wcf.bot.content', 0);

                if ($this->condenseEnable == 1 && empty($this->condense[0])) {
                    throw new UserInputException('condense');
                }
            }
        }

        // specials
        // new user - either group assignment or notification
        if ($this->type->typeTitle == 'user_creation') {
            if (!$this->userCreationGroupID && !$this->notifyID) {
                throw new UserInputException('notifyID', 'groupOrNotify');
            }

            // allowed groups
            if ($this->userCreationGroupID) {
                if (!isset($this->availableGroups[$this->userCreationGroupID])) {
                    throw new UserInputException('userCreationGroupID', 'invalidGroup');
                }
            }
        }

        // groupChange - must have at least one groupID
        // not for active group changes, only for monitoring of group changes
        if ($this->type->typeTitle == 'user_groupChange') {
            if (empty($this->groupChangeGroupIDs)) {
                throw new UserInputException('groupChangeGroupIDs', 'notConfigured');
            }

            // all groups?
            if (!\in_array(0, $this->groupChangeGroupIDs)) {
                if (\count(\array_diff($this->groupChangeGroupIDs, $this->availableGroupsPassiveIDs))) {
                    throw new UserInputException('groupChangeGroupIDs', 'invalidGroup');
                }
            }
        }

        // inactivity remind needs notification
        if ($this->type->typeTitle == 'user_inactivity' && $this->inactiveAction == 'remind' && !$this->notifyID) {
            throw new UserInputException('notifyID', 'missing');
        }

        // inactivity delete affected no direct notifications
        if ($this->type->typeTitle == 'user_inactivity' && $this->inactiveAction == 'delete' && $this->receiverAffected) {
            if ($this->notifyID == 1 || $this->notifyID == 4 || $this->notifyID == 5 || $this->notifyID == 30) {
                throw new UserInputException('notifyID', 'affected');
            }
        }

        // inactivity ban reason
        if (\mb_strlen($this->inactiveBanReason) > 60000) {
            $this->inactiveBanReason = \mb_substr($this->inactiveBanReason, 0, 60000) . ' ...';
        }

        // user setting needs action and notification
        if ($this->type->typeTitle == 'user_setting') {
            $sum = 0;
            $sum += $this->userSettingAvatarOption + $this->userSettingEmail + $this->userSettingSelfDeletion;
            $sum += $this->userSettingSignature + $this->userSettingUsername + $this->userSettingOther;
            $sum += $this->userSettingUserTitle + $this->userSettingCover;
            if (!$sum) {
                throw new UserInputException('userSetting', 'notConfigured');
            }
        }

        // circular
        if ($this->type->typeTitle == 'system_circular') {
            // none or existing repeat
            if (!\in_array($this->cirRepeatType, self::$cirRepeatTypes, true)) {
                $this->repeatType = 'none';
            }

            // start time must be valid in in future
            if ($this->cirStartDateTime === false || $this->cirStartDateTime->getTimestamp() < 0 || $this->cirStartDateTime->getTimestamp() > 2147483647) {
                throw new UserInputException('cirStartTime', 'invalid');
            }
            if ($this->cirStartDateTime->getTimestamp() < TIME_NOW) {
                throw new UserInputException('cirStartTime', 'inPast');
            }
        }

        // comment action needs at least one type
        if ($this->type->typeTitle == 'system_comment') {
            if (empty($this->commentTypeIDs)) {
                throw new UserInputException('commentTypeIDs', 'notConfigured');
            }
        }

        // article new/change
        if ($this->type->typeTitle == 'article_new') {
            $this->articleConditionCategoryID = $this->articleConditionCategoryID_new;
        }

        // conditions
        foreach ($this->receiverConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->validate();
            }
        }

        foreach ($this->userConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->validate();
            }
        }

        foreach ($this->userBotConditions as $conditions) {
            foreach ($conditions as $condition) {
                $condition->getProcessor()->validate();
            }
        }
    }

    /**
     * Reads the box images.
     */
    protected function readImages()
    {
        if (!empty($this->imageID) || !empty($this->teaserImageID)) {
            $mediaList = new ViewableMediaList();
            $mediaList->setObjectIDs(\array_merge($this->imageID, $this->teaserImageID));
            $mediaList->readObjects();

            foreach ($this->imageID as $languageID => $imageID) {
                $image = $mediaList->search($imageID);
                if ($image !== null && $image->isImage) {
                    $this->images[$languageID] = $image;
                }
            }
            foreach ($this->teaserImageID as $languageID => $imageID) {
                $image = $mediaList->search($imageID);
                if ($image !== null && $image->isImage) {
                    $this->teaserImages[$languageID] = $image;
                }
            }
        }
    }
}
