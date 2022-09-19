<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\UserList;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UserOptionCacheBuilder;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\language\LanguageFactory;
use wcf\system\user\UserBirthdayCache;
use wcf\system\WCF;
use wcf\util\DateUtil;

/**
 * User birthday and membership cronjob for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserBirthdayCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// Read all active, valid activity bots, abort if none
		$birthdayBots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_birthday']);
		$membershipBots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_membership']);
		if (!count($birthdayBots) && !count($membershipBots)) return;
		
		// get language for log
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// Step through all birthday bots
		if (count($birthdayBots)) {
			foreach ($birthdayBots as $bot) {
				// get today's birthdays
				$currentDay = DateUtil::format(null, 'm-d');
				$date = explode('-', DateUtil::format(null, 'Y-n-j'));
				$userIDs = UserBirthdayCache::getInstance()->getBirthdays($date[1], $date[2]);
				
				// get users matching conditions
				if (!empty($userIDs)) {
					$userList = new UserList();
					$userList->getConditionBuilder()->add('user_table.userID IN (?)', [$userIDs]);
					$conditions = $bot->getUserConditions();
					foreach ($conditions as $condition) {
						$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
					}
					$botConditions = $bot->getUserBotConditions();
					foreach ($botConditions as $condition) {
						$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
					}
					
					$userList->readObjects();
					$temp = $userList->getObjects();
					$userIDs = [];
					if (count($temp)) {
						foreach ($temp as $user) {
							$userIDs[] = $user->userID;
						}
					}
				}
				
				$affectedUserIDs = $countToUserID = $placeholders = [];
				
				// allow only users who give access to birthday (privacy)
				if (!empty($userIDs)) {
					$userOptions = UserOptionCacheBuilder::getInstance()->getData([], 'options');
					
					if (isset($userOptions['birthday'])) {
						$birthdayUserOption = $userOptions['birthday'];
						
						$userProfiles = UserProfileRuntimeCache::getInstance()->getObjects($userIDs);
						foreach ($userProfiles as $userProfile) {
							$birthdayUserOption->setUser($userProfile->getDecoratedObject());
							if ($bot->birthdayForce) {
								if (substr($userProfile->birthday, 5) == $currentDay) {
									$affectedUserIDs[] = $userProfile->userID;
									$countToUserID[$userProfile->userID] = 1;
								}
							}
							else {
								$profileOption = $userProfile->getDecoratedObject()->getUserOption('canViewProfile');
								if ($birthdayUserOption->isVisible() && $profileOption <= 1 && substr($userProfile->birthday, 5) == $currentDay) {
									$affectedUserIDs[] = $userProfile->userID;
									$countToUserID[$userProfile->userID] = 1;
								}
							}
						}
					}
				}
				
				$count = count($affectedUserIDs);
				$placeholders['count'] = $count;
				
				if ($count) {
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
					
					// create notification, send direct
					$data = [
							'bot' => $bot,
							'placeholders' => $placeholders,
							'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
							'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs
					];
					
					$job = new NotifyScheduleBackgroundJob($data);
					BackgroundQueueHandler::getInstance()->performJob($job);
					
					$additionalData = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
							'total' => count($affectedUserIDs),
							'userIDs' => implode(', ', $affectedUserIDs)
					]);
					if (mb_strlen($additionalData) > 64000) $additionalData = mb_substr($additionalData, 0, 64000) . ' ...';
				}
				else {
					$additionalData = $defaultLanguage->get('wcf.acp.uzbot.log.noUsers');
				}
				
				// log result
				if ($bot->enableLog) {
					if (!$bot->testMode) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => $count,
								'additionalData' => $additionalData
						]);
					}
					else {
						$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
								'objects' => $count,
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
			}
		}
		
		// Step through all membership bots
		if (count($membershipBots)) {
			foreach ($membershipBots as $bot) {
				$affectedUserIDs = $countToUserID = [];
				$counts = explode(',', $bot->userCount);
				
				$userList = new UserList();
				$userList->getConditionBuilder()->add("DATE_FORMAT(FROM_UNIXTIME(user_table.registrationDate),'%m-%d') = DATE_FORMAT(NOW(),'%m-%d')");
				
				// general user conditions
				$conditions = $bot->getUserConditions();
				foreach ($conditions as $condition) {
					$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
				}
				$botConditions = $bot->getUserBotConditions();
				foreach ($botConditions as $condition) {
					$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
				}
				
				$userList->readObjects();
				$users = $userList->getObjects();
				if (count($users)) {
					foreach ($users as $user) {
						$years = intval(date("Y", TIME_NOW) - date("Y", $user->registrationDate));
						if ($years && in_array($years, $counts)) {
							$affectedUserIDs[] = $user->userID;
							$countToUserID[$user->userID] = $years;
						}
					}
				}
				
				$count = count($affectedUserIDs);
				$placeholders['count'] = $count;
				
				if ($count) {
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
					
					// create notification, send direct
					$data = [
							'bot' => $bot,
							'placeholders' => $placeholders,
							'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
							'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs
					];
					
					$job = new NotifyScheduleBackgroundJob($data);
					BackgroundQueueHandler::getInstance()->performJob($job);
					
					$additionalData = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
							'total' => count($affectedUserIDs),
							'userIDs' => implode(', ', $affectedUserIDs)
					]);
				}
				else {
					$additionalData = $defaultLanguage->get('wcf.acp.uzbot.log.noUsers');
				}
				
				// log result
				if ($bot->enableLog) {
					if (!$bot->testMode) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => $count,
								'additionalData' => $additionalData
						]);
					}
					else {
						$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
								'objects' => $count,
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
			}
		}
	}
}
