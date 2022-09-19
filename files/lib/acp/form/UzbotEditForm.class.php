<?php
namespace wcf\acp\form;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\UzbotAction;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\UzbotList;
use wcf\data\uzbot\UzbotUtils;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\form\AbstractForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\label\object\UzbotActionLabelObjectHandler;
use wcf\system\label\object\UzbotConditionLabelObjectHandler;
use wcf\system\label\object\UzbotNotificationLabelObjectHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\condition\ConditionHandler;
use wcf\system\WCF;

/**
 * Shows the Bot edit form.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotEditForm extends UzbotAddForm {
	/**
	 * @inheritDoc
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.uzbot.list';
	
	// Bot data
	public $botID = 0;
	public $uzbot = null;
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		if (!empty($_POST) && !WCF::getSession()->getPermission('admin.content.cms.canUseMedia')) {
			foreach ($this->uzbot->getBotContents() as $languageID => $content) {
				$this->imageID[$languageID] = $content->imageID;
				$this->teaserImageID[$languageID] = $content->teaserImageID;
			}
			
			$this->readImages();
		}
		
		parent::readData();
		
		if (empty($_POST)) {
			$this->botDescription = $this->uzbot->botDescription;
			$this->botTitle = $this->uzbot->botTitle;
			$this->categoryID = $this->uzbot->categoryID;
			$this->enableLog = $this->uzbot->enableLog;
			$this->isDisabled = $this->uzbot->isDisabled;
			$this->testMode = $this->uzbot->testMode;
			
			$this->articleConditionCategoryID = $this->uzbot->articleConditionCategoryID;
			if ($this->uzbot->typeDes == 'article_new') {
				$this->articleConditionCategoryID_new = $this->uzbot->articleConditionCategoryID;
			}
			
			// notify
			$this->notifyID = $this->uzbot->notifyID;
			$this->articleCategoryID = $this->uzbot->articleCategoryID;
			$this->articleEnableComments = $this->uzbot->articleEnableComments;
			$this->articlePublicationStatus = $this->uzbot->articlePublicationStatus;
			$this->commentActivity = $this->uzbot->commentActivity;
			$this->conversationAllowAdd = $this->uzbot->conversationAllowAdd;
			$this->conversationClose = $this->uzbot->conversationClose;
			$this->conversationInvisible = $this->uzbot->conversationInvisible;
			$this->conversationLeave = $this->uzbot->conversationLeave;
			$this->conversationType = $this->uzbot->conversationType;
			$this->emailBCC = $this->uzbot->emailBCC;
			$this->emailCC = $this->uzbot->emailCC;
			$this->emailPrivacy = $this->uzbot->emailPrivacy;
			$this->emailAttachmentFile = $this->uzbot->emailAttachmentFile;
			$this->notifyLanguageID = $this->uzbot->notifyLanguageID;
			$this->receiverAffected = $this->uzbot->receiverAffected;
			$this->receiverGroupIDs = unserialize($this->uzbot->receiverGroupIDs);
			$this->receiverNames = $this->uzbot->receiverNames;
			$this->sendername = $this->uzbot->sendername;
			
			foreach ($this->uzbot->getBotContents() as $languageID => $content) {
				$this->condense[$languageID] = $content->condense;
				$this->content[$languageID] = $content->content;
				$this->subject[$languageID] = $content->subject;
				$this->teaser[$languageID] = $content->teaser;
				if (MODULE_TAGGING) $this->tags[$languageID] = unserialize($content->tags);
				$this->imageID[$languageID] = $content->imageID;
				$this->teaserImageID[$languageID] = $content->teaserImageID;
			}
			
			$this->typeID = $this->uzbot->typeID;
			
			$this->actionLabelDelete = $this->uzbot->actionLabelDelete;
			$this->articlePublished = $this->uzbot->articlePublished;
			$this->birthdayForce = $this->uzbot->birthdayForce;
			$this->changeAffected = $this->uzbot->changeAffected;
			$this->condenseEnable = $this->uzbot->condenseEnable;
			
			$cirData = unserialize($this->uzbot->cirData);
			$this->cirCounter = $this->uzbot->cirCounter;
			$this->cirCounterInterval = $this->uzbot->cirCounterInterval;
			
			$this->cirRepeatCount = $cirData['cirRepeatCount'];
			$this->cirRepeatType = $cirData['cirRepeatType'];
			$this->cirTimezone = $cirData['cirTimezone'];
			
			$this->cirTimezoneObj = new \DateTimeZone($this->cirTimezone);
			$d = new \DateTime($cirData['cirStartTime']);
			$d->setTimezone($this->cirTimezoneObj);
			$this->cirStartTime = $d->format('c');
			
			$this->cirMonthlyDoM_day = $cirData['cirMonthlyDoM_day'];
			$this->cirMonthlyDoW_index = $cirData['cirMonthlyDoW_index'];
			$this->cirMonthlyDoW_day = $cirData['cirMonthlyDoW_day'];
			$this->cirWeekly_day = $cirData['cirWeekly_day'];
			$this->cirYearlyDoM_day = $cirData['cirYearlyDoM_day'];
			$this->cirYearlyDoM_month = $cirData['cirYearlyDoM_month'];
			$this->cirYearlyDoW_day = $cirData['cirYearlyDoW_day'];
			$this->cirYearlyDoW_index = $cirData['cirYearlyDoW_index'];
			$this->cirYearlyDoW_month = $cirData['cirYearlyDoW_month'];
			
			$this->commentDays = $this->uzbot->commentDays;
			$this->commentDaysAfter = $this->uzbot->commentDaysAfter;
			$this->commentNoAnswers = $this->uzbot->commentNoAnswers;
			$this->commentNoUser = $this->uzbot->commentNoUser;
			$this->commentTypeIDs = unserialize($this->uzbot->commentTypeIDs);
			
			$this->conversationDays = $this->uzbot->conversationDays;
			$this->conversationDaysAfter = $this->uzbot->conversationDaysAfter;
			$this->conversationNoAnswers = $this->uzbot->conversationNoAnswers;
			$this->conversationNoLabels = $this->uzbot->conversationNoLabels;
			
			$this->feedreaderExclude = $this->uzbot->feedreaderExclude;
			$this->feedreaderFrequency = $this->uzbot->feedreaderFrequency;
			$this->feedreaderInclude = $this->uzbot->feedreaderInclude;
			$this->feedreaderMaxAge = $this->uzbot->feedreaderMaxAge;
			$this->feedreaderMaxItems = $this->uzbot->feedreaderMaxItems;
			$this->feedreaderUseTags = $this->uzbot->feedreaderUseTags;
			$this->feedreaderUseTime = $this->uzbot->feedreaderUseTime;
			$this->feedreaderUrl = $this->uzbot->feedreaderUrl;
			
			$this->groupAssignmentGroupID = $this->uzbot->groupAssignmentGroupID;
			$this->groupAssignmentAction = $this->uzbot->groupAssignmentAction;
			$this->groupChangeGroupIDs = unserialize($this->uzbot->groupChangeGroupIDs);
			$this->groupChangeType = $this->uzbot->groupChangeType;
			
			$this->inactiveAction = $this->uzbot->inactiveAction;
			$this->inactiveBanReason = $this->uzbot->inactiveBanReason;
			$this->inactiveReminderLimit = $this->uzbot->inactiveReminderLimit;
			
			$this->likeAction = $this->uzbot->likeAction;
			
			$this->userCount = $this->uzbot->userCount;
			$this->userCreationGroupID = $this->uzbot->userCreationGroupID;
			$this->userSettingAvatarOption = $this->uzbot->userSettingAvatarOption;
			$this->userSettingCover = $this->uzbot->userSettingCover;
			$this->userSettingEmail = $this->uzbot->userSettingEmail;
			$this->userSettingOther = $this->uzbot->userSettingOther;
			$this->userSettingSelfDeletion = $this->uzbot->userSettingSelfDeletion;
			$this->userSettingSignature = $this->uzbot->userSettingSignature;
			$this->userSettingUsername = $this->uzbot->userSettingUsername;
			$this->userSettingUserTitle = $this->uzbot->userSettingUserTitle;
			
			$this->readImages();
			
			// conditions
			$conditions = $this->uzbot->getReceiverConditions();
			foreach ($conditions as $condition) {
				$this->receiverConditions[$condition->getObjectType()->conditiongroup][$condition->objectTypeID]->getProcessor()->setData($condition);
			}
			
			$conditions = $this->uzbot->getUserConditions();
			foreach ($conditions as $condition) {
				$this->userConditions[$condition->getObjectType()->conditiongroup][$condition->objectTypeID]->getProcessor()->setData($condition);
			}
			
			$conditions = $this->uzbot->getUserBotConditions();
			foreach ($conditions as $condition) {
				$this->userBotConditions[$condition->getObjectType()->conditiongroup][$condition->objectTypeID]->getProcessor()->setData($condition);
			}
			
			// labels for notification, condition and action
			$assignedLabels = UzbotNotificationLabelObjectHandler::getInstance()->getAssignedLabels([$this->uzbot->botID], true);
			if (isset($assignedLabels[$this->uzbot->botID])) {
				foreach ($assignedLabels[$this->uzbot->botID] as $label) {
					$this->notifyLabelIDs[$label->groupID] = $label->labelID;
				}
			}
			
			$assignedLabels = UzbotActionLabelObjectHandler::getInstance()->getAssignedLabels([$this->uzbot->botID], true);
			if (isset($assignedLabels[$this->uzbot->botID])) {
				foreach ($assignedLabels[$this->uzbot->botID] as $label) {
					$this->actionLabelIDs[$label->groupID] = $label->labelID;
				}
			}
			
			$assignedLabels = UzbotConditionLabelObjectHandler::getInstance()->getAssignedLabels([$this->uzbot->botID], true);
			if (isset($assignedLabels[$this->uzbot->botID])) {
				foreach ($assignedLabels[$this->uzbot->botID] as $label) {
					$this->conditionLabelIDs[$label->groupID] = $label->labelID;
				}
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->botID = intval($_REQUEST['id']);
		$this->uzbot = new Uzbot($this->botID);
		if (!$this->uzbot->botID) {
			throw new IllegalLinkException();
		}
		
		if ($this->uzbot->isMultilingual) $this->isMultilingual = 1;
		
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		$botList = new UZbotList();
		$botList->sqlOrderBy = 'botTitle ASC';
		$botList->readObjects();
		
		WCF::getTPL()->assign([
				'action' => 'edit',
				'availableBots' => $botList->getObjects(),
				'uzbot' => $this->uzbot,
				'botID' => $this->uzbot->botID
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function save() {
		AbstractForm::save();
		
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
						'htmlInputProcessor' => isset($this->htmlInputProcessors[$language->languageID]) ? $this->htmlInputProcessors[$language->languageID] : null,
						'imageID' => !empty($this->imageID[$language->languageID]) ? $this->imageID[$language->languageID] : null,
						'teaserImageID' => !empty($this->teaserImageID[$language->languageID]) ? $this->teaserImageID[$language->languageID] : null
				];
			}
		}
		else {
			$content[0] = [
					'condense' => !empty($this->condense[0]) ? $this->condense[0] : '',
					'content' => !empty($this->content[0]) ? $this->content[0] : '',
					'subject' => !empty($this->subject[0]) ? $this->subject[0] : '',
					'tags' => !empty($this->tags[0]) ? $this->tags[0] : [],
					'teaser' => !empty($this->teaser[0]) ? $this->teaser[0] : '',
					'htmlInputProcessor' => isset($this->htmlInputProcessors[0]) ? $this->htmlInputProcessors[0] : null,
					'imageID' => !empty($this->imageID[0]) ? $this->imageID[0] : null,
					'teaserImageID' => !empty($this->teaserImageID[0]) ? $this->teaserImageID[0] : null
			];
		}
		
		// data
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
				'cirYearlyDoW_month' => $this->cirYearlyDoW_month
		];
		
		$execs = UzbotUtils::calcExecution($cirData);
		
		$data = [
				'botDescription' => $this->botDescription,
				'botTitle' => $this->botTitle,
				'categoryID' => $this->categoryID ? $this->categoryID : null,
				'enableLog' => $this->enableLog,
				'isDisabled' => $this->isDisabled,
				'testMode' => $this->testMode,
				'isMultilingual' => $this->isMultilingual,
				
				'typeID' => $this->typeID,
				'typeDes' => $this->typeID ? $this->type->typeTitle : '',
				
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
				'receiverGroupIDs' => serialize($this->receiverGroupIDs),
				'receiverNames' => $this->receiverNames,
				'senderID' => $this->notifyID ? $this->sender->userID : null,
				'sendername' => $this->notifyID ? $this->sender->username : '',
				
				'actionLabelDelete' => $this->actionLabelDelete,
				'articlePublished' => $this->articlePublished,
				'birthdayForce' => $this->birthdayForce,
				'changeAffected' => $this->changeAffected,
				'condenseEnable' => $this->condenseEnable,
				
				'cirCounter' => $this->cirCounter,
				'cirCounterInterval' => $this->cirCounterInterval,
				'cirData' => serialize($cirData),
				'cirExecution' => serialize($execs),
				
				'commentDays' => $this->commentDays,
				'commentDaysAfter' => $this->commentDaysAfter,
				'commentNoAnswers' => $this->commentNoAnswers,
				'commentNoUser' => $this->commentNoUser,
				'commentTypeIDs' => serialize($this->commentTypeIDs),
				
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
				'groupChangeGroupIDs' => serialize($this->groupChangeGroupIDs),
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
				'userSettingUserTitle' => $this->userSettingUserTitle
		];
		
		$this->objectAction = new UzbotAction([$this->uzbot], 'update', [
				'data' => array_merge($this->additionalFields, $data), 
				'content' => $content,
				'actionLabelIDs' => $this->actionLabelIDs,
				'conditionLabelIDs' => $this->conditionLabelIDs,
				'notifyLabelIDs' => $this->notifyLabelIDs
		]);
		$this->objectAction->executeAction();
		
		// transform conditions array into one-dimensional array and save
		$conditions = [];
		foreach ($this->receiverConditions as $groupedObjectTypes) {
			$conditions = array_merge($conditions, $groupedObjectTypes);
		}
		ConditionHandler::getInstance()->updateConditions($this->uzbot->botID, $this->uzbot->getReceiverConditions(), $conditions);
		
		$conditions = [];
		foreach ($this->userConditions as $groupedObjectTypes) {
			$conditions = array_merge($conditions, $groupedObjectTypes);
		}
		ConditionHandler::getInstance()->updateConditions($this->uzbot->botID, $this->uzbot->getUserConditions(), $conditions);
		
		$conditions = [];
		foreach ($this->userBotConditions as $groupedObjectTypes) {
			$conditions = array_merge($conditions, $groupedObjectTypes);
		}
		ConditionHandler::getInstance()->updateConditions($this->uzbot->botID, $this->uzbot->getUserBotConditions(), $conditions);
		
		// reset cache
		UzbotEditor::resetCache();
		
		// log action
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		UzbotLogEditor::create([
				'bot' => $this->uzbot,
				'count' => 1,
				'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.bot.edited', [
						'username' => WCF::getUser()->username
				])
		]);
		
		$this->saved();
		
		// show success
		WCF::getTPL()->assign([
				'success' => true
		]);
	}
}
