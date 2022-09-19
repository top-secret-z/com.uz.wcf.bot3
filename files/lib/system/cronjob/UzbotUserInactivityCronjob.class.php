<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\UserAction;
use wcf\data\user\UserList;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UserGroupCacheBuilder;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Inactivity cronjob for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserInactivityCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// remove uzbotDisabled and uzbotBanned if user status does not match; might have been changed in the meantime
		$sql = "UPDATE	wcf".WCF_N."_user
				SET		uzbotDisabled = ?
				WHERE	activationCode = ? AND uzbotDisabled > ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([0, 0, 0]);
		
		$sql = "UPDATE	wcf".WCF_N."_user
				SET		uzbotBanned = ?
				WHERE	banned = ? AND uzbotBanned > ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([0, 0, 0]);
		
		// Read all active inactivity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_inactivity']);
		if (!count($bots)) return;
		
		// get language for log
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// get owner / admin group members
		$adminIDs = $groupIDs = [];
		$groups = UserGroupCacheBuilder::getInstance()->getData();
		
		foreach ($groups['groups'] as $group) {
			if ($group->isAdminGroup()) {
				$groupIDs[] = $group->groupID;
			}
		}
		if (empty($groupIDs)) {
			$adminIDs[] = 1;
		}
		else {
			$condition = new PreparedStatementConditionBuilder();
			$condition->add('groupID IN (?)', [$groupIDs]);
			$sql = "SELECT	userID
					FROM	wcf".WCF_N."_user_to_group
					" . $condition;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($condition->getParameters());
			$adminIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
			$adminIDs = array_unique($adminIDs);
		}
		
		// Step through all bots
		foreach ($bots as $bot) {
			// get users iaw conditions
			$userList = new UserList();
			$conditions = $bot->getUserConditions();
			foreach ($conditions as $condition) {
				$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
			}
			$botConditions = $bot->getUserBotConditions();
			foreach ($botConditions as $condition) {
				$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
			}
			
			// additional conditions iaw action
			switch ($bot->inactiveAction) {
				case 'remind':
					$userList->getConditionBuilder()->add('user_table.uzbotReminders < ?', [$bot->inactiveReminderLimit]);
				break;
				case 'unremind':
					$userList->getConditionBuilder()->add('user_table.uzbotReminders > ?', [0]);
					break;
				case 'deactivate':
					$userList->getConditionBuilder()->add('user_table.uzbotDisabled = ?', [0]);
					$userList->getConditionBuilder()->add('user_table.userID NOT IN (?)', [$adminIDs]);
					break;
				case 'activate':
					$userList->getConditionBuilder()->add('user_table.uzbotDisabled > ?', [0]);
					break;
				case 'ban':
					$userList->getConditionBuilder()->add('user_table.uzbotBanned = ?', [0]);
					$userList->getConditionBuilder()->add('user_table.userID NOT IN (?)', [$adminIDs]);
					break;
				case 'delete':
					$userList->getConditionBuilder()->add('user_table.userID NOT IN (?)', [$adminIDs]);
					break;
			}
			
			// limit
			if (!$bot->testMode) {
				$userList->sqlLimit = UZBOT_DATA_LIMIT_USER;
			}
			else {
				$userList->sqlLimit = 1000;
			}
			
			$userList->readObjects();
			$temp = $userList->getObjects();
			$affectedUserIDs = [];
			if (count($temp)) {
				foreach ($temp as $user) {
					$affectedUserIDs[] = $user->userID;
				}
			}
			
			$count = count($affectedUserIDs);
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
			
			// actions
			$condition = new PreparedStatementConditionBuilder();
			$condition->add('userID IN (?)', [$affectedUserIDs]);
			$deletedUsers = $deletedUsernames = $countToUserID = [];
			
			// set countToUser for inactivity-time
			$users = UserProfileRuntimeCache::getInstance()->getObjects($affectedUserIDs);
			foreach ($affectedUserIDs as $userID) {
				$countToUserID[$userID] = 0;
				if (isset($users[$userID]) && $users[$userID]) {
					if ($users[$userID]->lastActivityTime > 0) {
						$countToUserID[$userID] = ceil((TIME_NOW - $users[$userID]->lastActivityTime) / 86400);
					}
					else {
						$countToUserID[$userID] = ceil((TIME_NOW - $users[$userID]->registrationDate) / 86400);
					}
				}
			}
			
			switch ($bot->inactiveAction) {
				case 'remind':
					if (!$bot->testMode) {
						$sql = "UPDATE 	wcf".WCF_N."_user
								SET		uzbotReminded = ?, uzbotReminders = uzbotReminders + ?
								" . $condition;
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute(array_merge([TIME_NOW, 1], $condition->getParameters()));
					}
					break;
					
				case 'unremind':
					if (!$bot->testMode) {
						$sql = "UPDATE 	wcf".WCF_N."_user
								SET	 	uzbotReminded = ?, uzbotReminders = ?
								" . $condition;
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute(array_merge([0, 0], $condition->getParameters()));
					}
					break;
					
				case 'deactivate':
					if (!$bot->testMode) {
						$sql = "UPDATE 	wcf".WCF_N."_user
								SET	 	uzbotDisabled = ?
								" . $condition;
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute(array_merge([TIME_NOW], $condition->getParameters()));
						
						// disable users
						$userAction = new UserAction($affectedUserIDs, 'disable');
						$userAction->executeAction();
					}
					break;
					
				case 'activate':
						// remove reminder, too
					if (!$bot->testMode) {
						$sql = "UPDATE 	wcf".WCF_N."_user
								SET	 	uzbotDisabled = ?, uzbotReminded = ?, uzbotReminders = ?
								" . $condition;
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute(array_merge([0, 0, 0], $condition->getParameters()));
						
						// enable users
						$userAction = new UserAction($affectedUserIDs, 'enable', ['skipNotification' => true]);
						$userAction->executeAction();
					}
					break;
					
				case 'ban':
					if (!$bot->testMode) {
						$sql = "UPDATE 	wcf".WCF_N."_user
								SET	 	uzbotBanned = ?
								" . $condition;
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute(array_merge([TIME_NOW], $condition->getParameters()));
						
						$userAction = new UserAction($affectedUserIDs, 'ban', [
								'banExpires' => 0,
								'banReason' => $bot->inactiveBanReason
						]);
						$userAction->executeAction();
					}
					break;
					
				case 'delete':
					// log basic user data first
					$users = UserProfileRuntimeCache::getInstance()->getObjects($affectedUserIDs);
					$mails = [];
					
					foreach ($users as $user) {
						$deletedUsers[] = $user->userID . ' | ' . $user->username . ' | ' . $user->email;
						$deletedUsernames[] = $user->username;
						
						// store emails if required and allowed
						if ($bot->notifyID == 2 && $bot->receiverAffected) {
							$allow = false;
							if (!$bot->emailPrivacy) {
								$allow = true;
							}
							else {
								$adminCanMail = $user->adminCanMail;
								if ($adminCanMail === null || $adminCanMail) {
									$allow = true;
								}
							}
							
							if ($allow) {
								$mails[$user->userID] = [
										'userID' => $user->userID,
										'username' => $user->username,
										'email' => $user->email,
										'languageID' => $user->languageID,
								];
							}
						}
					}
					
					// delete users
					if (!$bot->testMode) {
						// delete
						$deletedIDs = [];
						$userAction = new UserAction($affectedUserIDs, 'delete');
						$returnValue = $userAction->executeAction();
						
						$deletedIDs = $returnValue['objectIDs'];
						if (empty($deletedIDs)) break;
						
						// mail
						if ($bot->notifyID == 2 && $bot->receiverAffected && count($mails)) {
							$mailUserIDs = [];
							$sql = "INSERT INTO wcf".WCF_N."_uzbot_system
										(botID, userID, username, email, languageID, counter)
									VALUES	(?, ?, ?, ?, ?, ?)";
							$statement = WCF::getDB()->prepareStatement($sql);
							WCF::getDB()->beginTransaction();
							foreach ($deletedIDs as $userID) {
								if (isset($mails[$userID])) {
									$statement->execute([
											$bot->botID,
											$mails[$userID]['userID'],
											$mails[$userID]['username'],
											$mails[$userID]['email'],
											$mails[$userID]['languageID'],
											isset($countToUserID[$userID]) ? $countToUserID[$userID] : 0
									]);
									$mailUserIDs[] = $userID;
								}
							}
							WCF::getDB()->commitTransaction();
							
							if ($bot->enableLog) {
								UzbotLogEditor::create([
										'bot' => $bot,
										'count' => count($mails),
										'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count.id',[
												'count' => count($mailUserIDs),
												'id' => implode(', ', $mailUserIDs)
										])
								]);
							}
						}
					}
					break;
			}
			
			// log action
			if ($bot->enableLog) {
				if (!$bot->testMode) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => count($affectedUserIDs),
							'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
									'total' => count($affectedUserIDs),
									'userIDs' => $bot->inactiveAction == 'delete' ? implode(' || ', $deletedUsers) : implode(', ', $affectedUserIDs)
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
			$placeholders['count'] = count($affectedUserIDs);
			$placeholders['deleted'] = implode(', ', $deletedUsers);
			$placeholders['deleted-usernames'] = implode(', ', $deletedUsernames);
			
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
			
			// correct affected if action delete
			if ($bot->inactiveAction == 'delete') {
				$affectedUserIDs = $countToUserID = [];
			}
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
