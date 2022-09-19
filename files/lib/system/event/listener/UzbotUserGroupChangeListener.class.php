<?php
namespace wcf\system\event\listener;
use wcf\data\user\User;
use wcf\data\user\group\UserGroupList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Listen to User group changes for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserGroupChangeListener implements IParameterizedEventListener {
	/**
	 * groupIDs before group change action
	 */
	private $isUserActionUpdate = 0;
	private $beforeAddIDs = [];
	private $beforeRemoveIDs = [];
	private $beforeUpdateIDs = [];
	
	private $add = [];
	private $remove = [];
	private $groups = [];
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// check module
		if (!MODULE_UZBOT) return;
		
		if ($className !== 'wcf\data\user\UserAction') return;
		
		// Read all active, valid activity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_groupChange']);
		if (!count($bots)) return;
		
		// get groupIDs before group change on UserAction only
		if ($eventName === 'initializeAction') {
			$this->beforeAddIDs = $this->beforeRemoveIDs = [];
			
			$action = $eventObj->getActionName();
			
			// might not have objects set
			$userIDs = $eventObj->getObjectIDs();
			if (empty($userIDs)) return;
			$users = User::getUsers($userIDs);
			if (empty($users)) return;
			
			if ($action == 'addToGroups') {
				foreach ($users as $user) {
					$this->beforeAddIDs[$user->userID] = $user->getGroupIDs();
				}
			}
			
			if ($action == 'removeFromGroups') {
				foreach ($users as $user) {
					$this->beforeRemoveIDs[$user->userID] = $user->getGroupIDs();
				}
			}
			
			if ($action == 'update') {
				$params = $eventObj->getParameters();
				
				if (isset($params['groups'])) {
				foreach ($users as $user) {
					$this->beforeUpdateIDs[$user->userID] = $user->getGroupIDs();
				}
				
				$this->isUserActionUpdate = 1;
				}
			}
			
			return;
		}
		
		// finalizeAtion
		$this->add = $this->remove = $this->groups = [];
		$user = null;
		$users = [];
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		$action = $eventObj->getActionName();
		
		// UserAction: Plugins, UserAssignToGroupAction, paid memberships, user group assignment cronjob
		if ($action == 'addToGroups') {
			$params = $eventObj->getParameters();
			
			// skip if uzbot group assignment
			if (isset($params['isBot'])) return;
			
			// might not have objects set
			$userIDs = $eventObj->getObjectIDs();
			if (empty($userIDs)) return;
			$users = User::getUsers($userIDs);
			if (empty($userIDs)) return;
			
			// groups might be string array; e.g. paid memberships
			foreach ($params['groups'] as $groupID) {
				$this->add[] = intval($groupID);
			}
			
			if ($this->isUserActionUpdate) return;
		}
		
		if ($action == 'removeFromGroups') {
			$params = $eventObj->getParameters();
			
			// skip if uzbot group assignment
			if (isset($params['isBot'])) return;
			
			// might not have objects set
			$userIDs = $eventObj->getObjectIDs();
			if (empty($userIDs)) return;
			$users = User::getUsers($userIDs);
			if (empty($userIDs)) return;
			
			// groups might be string array
			foreach ($params['groups'] as $groupID) {
				$this->remove[] = intval($groupID);
			}
			
			if ($this->isUserActionUpdate) return;
		}
		
		if ($action == 'update') {
			$params = $eventObj->getParameters();
			
			// skip if uzbot group assignment
			if (isset($params['isBot'])) return;
			
			if (!isset($params['groups']) && !isset($params['removeGroups']) ) return;
			
			// might not have objects set
			$userIDs = $eventObj->getObjectIDs();
			if (empty($userIDs)) return;
			$users = User::getUsers($userIDs);
			if (empty($userIDs)) return;
			
			// groups might be string array
			if (isset($params['groups'])) {
				$groups = $params['groups'];
				foreach ($groups as $groupID) {
					$this->groups[] = intval($groupID);
				}
			}
			//else {
			//	$groups = $params['removeGroups'];
			//	foreach ($groups as $groupID) {
			//		$this->remove[] = intval($groupID);
			//	}
			//}
		}
		
		if (empty($users)) return;
		
		// get group names
		$names = [];
		$tempList = new UserGroupList();
		$tempList->readObjects();
		$groupList = $tempList->getObjects();
		foreach($groupList as $group) {
			$names[$group->groupID] = $group->groupName;
		}
		
		// step through affected users, then each bot
		foreach ($users as $user) {
			$addNames = $removeNames = $temp = [];
			
			$beforeAdd = isset($this->beforeAddIDs[$user->userID]) ? $this->beforeAddIDs[$user->userID] : [];
			$beforeRemove = isset($this->beforeRemoveIDs[$user->userID]) ? $this->beforeRemoveIDs[$user->userID] : [];
			$beforeUpdate = isset($this->beforeUpdateIDs[$user->userID]) ? $this->beforeUpdateIDs[$user->userID] : [];
			
			// get old and new groups; always add default groups
			$oldGroups = array_unique(array_merge($beforeAdd, $beforeRemove, $beforeUpdate));
			$newGroups = $oldGroups;
			
			if ($this->isUserActionUpdate) {
				//$temp = array_unique(array_merge($oldGroups, $this->add, $this->groups));
				$temp = array_unique(array_merge($this->add, $this->groups));
				//$temp = array_unique(array_merge($temp, UserGroup::getGroupIDsByType([UserGroup::EVERYONE, UserGroup::USERS])));
				$newGroups = array_diff($temp, $this->remove);
			}
			else {
				if (!empty($this->add)) {
					$newGroups = array_unique(array_merge($oldGroups, $this->add));
				}
				if (!empty($this->remove)) {
					$newGroups = array_diff($oldGroups, $this->remove);
				}
			}
			
			$add = array_diff($newGroups, $oldGroups);
			$remove = array_diff($oldGroups, $newGroups);
			
			if (empty($add) && empty($remove)) continue;
			
			if (!empty($add)) {
				foreach ($add as $groupID) {
					$addNames[$groupID] = $names[$groupID];
				}
			}
			
			if (!empty($remove)) {
				foreach ($remove as $groupID) {
					$removeNames[$groupID] = $names[$groupID];
				}
			}
			
			// set user data
			$affectedUserIDs = $countToUserID = [];
			$affectedUserIDs[] = $user->userID;
			$countToUserID[$user->userID] = 1;
			
			foreach ($bots as $bot) {
				// preset
				$placeholders = $newAddNames = $newRemoveNames = [];
				
				// check selected groups
				$botGroups = unserialize($bot->groupChangeGroupIDs);
				$allGroups = 0;
				if (in_array(0, $botGroups)) $allGroups = 1;
				
				switch($bot->groupChangeType) {
					case 0:		// add + remove
						if ($allGroups) {
							$placeholders['group-change'] = [
									'add' => $addNames,
									'remove' => $removeNames
							];
						}
						else {
							$newAddNames = $newRemoveNames = [];
							$ids = array_intersect($add, $botGroups);
							if (!empty($ids)) {
								foreach ($ids as $id) {
									$newAddNames[] = $addNames[$id];
								}
							}
							$ids = array_intersect($remove, $botGroups);
							if (!empty($ids)) {
								foreach ($ids as $id) {
									$newRemoveNames[] = $removeNames[$id];
								}
							}
							
							$placeholders['group-change'] = [
									'add' => $newAddNames,
									'remove' => $newRemoveNames
							];
						}
						break;
						
					case 1:		// add
						if ($allGroups) {
							$placeholders['group-change'] = [
									'add' => $addNames,
									'remove' => []
							];
						}
						else {
							$newAddNames = [];
							$ids = array_intersect($add, $botGroups);
							if (!empty($ids)) {
								foreach ($ids as $id) {
									$newAddNames[] = $addNames[$id];
								}
							}
							
							$placeholders['group-change'] = [
									'add' => $newAddNames,
									'remove' => []
							];
						}
						break;
						
					case 2:		// remove
						if ($allGroups) {
							$placeholders['group-change'] = [
									'add' => [],
									'remove' => $removeNames
							];
						}
						else {
							$newRemoveNames= [];
							$ids = array_intersect($remove, $botGroups);
							if (!empty($ids)) {
								foreach ($ids as $id) {
									$newRemoveNames[] = $removeNames[$id];
								}
							}
							
							$placeholders['group-change'] = [
									'add' => [],
									'remove' => $newRemoveNames
							];
						}
						break;
				}
				
				// only if change and groups match
				if (empty($placeholders['group-change']['add']) && empty($placeholders['group-change']['remove'])) {
					continue;
				}
				
				$placeholders['count'] = 1;
				
				// log action
				if ($bot->enableLog) {
					if (!$bot->testMode) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => 1,
								'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
										'total' => 1,
										'userIDs' => implode(', ', $affectedUserIDs)
								])
						]);
					}
					else {
						$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
								'objects' => 1,
								'users' => count($affectedUserIDs),
								'userIDs' => implode(', ', $affectedUserIDs)
						]);
						if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => 1,
								'testMode' => 1,
								'additionalData' => serialize(['', '', $result])
						]);
					}
				}
				
				// check for and prepare notification
				$notify = $bot->checkNotify(true, true);
				if ($notify === null) continue;
				
				// send to scheduler
				$data = [
						'bot' => $bot,
						'placeholders' => $placeholders,
						'affectedUserIDs' => $affectedUserIDs,
						'countToUserID' => $countToUserID
				];
				
				$job = new NotifyScheduleBackgroundJob($data);
				BackgroundQueueHandler::getInstance()->performJob($job);
			}
		}
	}
}
