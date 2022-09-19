<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\UserAction;
use wcf\data\user\UserList;
use wcf\data\user\group\UserGroup;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Group assignment cronjob for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserGroupAssignmentCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// Read all active assignment bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_groupAssignment']);
		if (!count($bots)) return;
		
		// get language for log
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// Step through all bots
		foreach ($bots as $bot) {
			// bot checks groupID, must exist and may not be admin / owner group
			$ok = true;
			if (!$bot->groupAssignmentGroupID) $ok = false;
			else {
				$group = new UserGroup($bot->groupAssignmentGroupID);
				if (!$group->groupID || $group->isAdminGroup()) {
					$ok = false;
				}
			}
			
			if (!$ok) {
				$editor = new UzbotEditor($bot);
				$editor->update(['isDisabled' => 1]);
				UzbotEditor::resetCache();
				
				if ($bot->enableLog) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'status' => 2,
							'additionalData' => $defaultLanguage->get('wcf.acp.uzbot.log.error.disabled') . ' / ' . $defaultLanguage->get('wcf.acp.uzbot.log.error.groupAssignmentGroupID')
					]);
				}
				continue;
			}
			
			// get users
			$count = 0;
			$userList = new UserList();
			if ($bot->groupAssignmentAction == 'add') {
				$userList->getConditionBuilder()->add("user_table.userID NOT IN (SELECT userID FROM wcf".WCF_N."_user_to_group WHERE groupID = ?)", [$bot->groupAssignmentGroupID]);
			}
			else {
				$userList->getConditionBuilder()->add("user_table.userID IN (SELECT userID FROM wcf".WCF_N."_user_to_group WHERE groupID = ?)", [$bot->groupAssignmentGroupID]);
			}
			$conditions = $bot->getUserConditions();
			foreach ($conditions as $condition) {
				$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
			}
			$botConditions = $bot->getUserBotConditions();
			foreach ($botConditions as $condition) {
				$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
			}
			
			// limit
			if (!$bot->testMode) {
				$userList->sqlLimit = UZBOT_DATA_LIMIT_USER;
			}
			else {
				$userList->sqlLimit = 1000;
			}
			
			$userList->readObjects();
			$users = $userList->getObjects();
			$count = count($users);
			
			if (!$count) {
				// abort and log result
				if ($bot->enableLog) {
					if (!$bot->testMode) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => 0,
								'additionalData' => $defaultLanguage->get('wcf.acp.uzbot.log.noUsers')
						]);
					}
					else {
						$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
								'objects' => 0,
								'users' => 0,
								'userIDs' => ''
						]);
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => $count,
								'testMode' => 1,
								'additionalData' => serialize(['', '', $result])
						]);
					}
				}
				continue;
			}
			
			// execute action
			$affectedUserIDs = $countToUserID = [];
			foreach ($users as $user) {
				$affectedUserIDs[] = $user->userID;
				$countToUserID[$user->userID] = 1;
				
				if (!$bot->testMode) {
					if ($bot->groupAssignmentAction == 'remove') {
						$action = new UserAction([$user], 'removeFromGroups', [
								'groups' => [$bot->groupAssignmentGroupID],
								'isBot' => 1
						]);
						$action->executeAction();
					}
					
					if ($bot->groupAssignmentAction == 'add') {
						$userAction = new UserAction([$user], 'addToGroups', [
								'addDefaultGroups' => false,
								'deleteOldGroups' => false,
								'groups' => [$bot->groupAssignmentGroupID],
								'isBot' => 1
						]);
						$userAction->executeAction();
					}
				}
			}
			
			// log action
			if ($bot->enableLog) {
				if (!$bot->testMode) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => count($affectedUserIDs),
							'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
									'total' => count($affectedUserIDs),
									'userIDs' => implode(', ', $affectedUserIDs)
							])
					]);
				}
				else {
					$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
							'objects' => count($affectedUserIDs),
							'users' => count($affectedUserIDs),
							'userIDs' => implode(', ', $affectedUserIDs)
					]);
					if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => $count,
							'testMode' => 1,
							'additionalData' => serialize(['', '', $result])
					]);
				}
			}
			
			// check for and prepare notification
			if (!$bot->notifyID) continue;
			$notify = $bot->checkNotify(true, true);
			if ($notify === null) continue;
			
			$placeholders = [];
			$group = new UserGroup($bot->groupAssignmentGroupID);
			$placeholders['usergroup'] = $group->groupName;
			$placeholders['count'] = count($affectedUserIDs);
			
			// test mode
			$testUserIDs = $testToUserIDs = [];
			if (count($affectedUserIDs)) {
				if ($bot->condenseEnable) {
					$testUserIDs = $affectedUserIDs;
					$testToUserIDs = $countToUserID;
				}
				else {
					$userID = reset($affectedUserIDs);
					$testUserIDs[] = $userID;
					$testToUserIDs[$userID] = $countToUserID[$userID];
				}
			}
			
			// send to scheduler
			$data = [
					'bot' => $bot,
					'placeholders' => $placeholders,
					'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
					'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs
			];
			
			$job = new NotifyScheduleBackgroundJob($data);
			BackgroundQueueHandler::getInstance()->performJob($job);
		}
	}
}
