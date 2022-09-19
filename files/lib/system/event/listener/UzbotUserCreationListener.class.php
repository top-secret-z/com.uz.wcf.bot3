<?php
namespace wcf\system\event\listener;
use wcf\data\user\User;
use wcf\data\user\UserEditor;
use wcf\data\user\UserProfileAction;
use wcf\data\user\group\UserGroup;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Listen to User creation for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserCreationListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// check module
		if (!MODULE_UZBOT) return;
		
		// only action create
		if ($eventObj->getActionName() != 'create') return;
		
		// Read all active, valid activity bots, abort if none
		$creationBots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_creation']);
		$countBots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_count']);
		if (!count($creationBots) && !count($countBots)) return;
		
		// get / set data
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		$resetCache = 0;
		
		$returnValues = $eventObj->getReturnValues();
		
		// set user
		$userID = $returnValues['returnValues']->userID;
		$user = new User($userID);
		$affectedUserIDs[] = $userID;
		$countToUserID[$userID] = 1;
		
		// get total users
		$sql = "SELECT	COUNT(*) AS count
				FROM	wcf".WCF_N."_user";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$totalUsers = $statement->fetchColumn();
		
		// step through creation bots
		if (count($creationBots)) {
			foreach ($creationBots as $bot) {
				$placeholders = [];
				
				// move to group if configured
				if ($bot->userCreationGroupID) {
					$group = new UserGroup($bot->userCreationGroupID);
					
					// disable bot if group doesn't exist or if it is admin / owner group
					if (!$group->groupID || $group->isAdminGroup()) {
						$editor = new UzbotEditor($bot);
						$editor->update(['isDisabled' => 1]);
						$resetCache = 1;
						
						if ($bot->enableLog) {
							UzbotLogEditor::create([
									'bot' => $bot,
									'status' => 2,
									'additionalData' => $defaultLanguage->get('wcf.acp.uzbot.log.error.disabled') . ' / ' . $defaultLanguage->get('wcf.acp.uzbot.log.error.userCreationGroupID')
							]);
						}
						continue;
					}
					
					// add user to group; use editor to avoid action, unless test mode
					if (!$bot->testMode) {
						$editor = new UserEditor($user);
						$editor->addToGroup($group->groupID);
						
						if (MODULE_USER_RANK) {
							$action = new UserProfileAction([$editor], 'updateUserRank');
							$action->executeAction();
						}
						if (MODULE_USERS_ONLINE) {
							$action = new UserProfileAction([$editor], 'updateUserOnlineMarking');
							$action->executeAction();
						}
					}
					
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
				}
				
				// check for and prepare notification
				if (!$bot->notifyID) continue;
				$notify = $bot->checkNotify(true, true);
				if ($notify === null) continue;
				
				if ($bot->userCreationGroupID) {
					$placeholders['usergroup'] = $group->groupName;
				}
				$placeholders['count'] = $totalUsers;
				
				// send to scheduler and execute
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
		
		// step through count bots
		if (count($countBots)) {
			foreach ($countBots as $bot) {
				$placeholders = [];
				
				// check count
				$counts = explode(',', $bot->userCount);
				if (!in_array($totalUsers, $counts)) continue;
				
				// check for and prepare notification
				if (!$bot->notifyID) continue;
				$notify = $bot->checkNotify(true, true);
				if ($notify === null) continue;
				
				$placeholders['count'] = $totalUsers;
				
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
		
		if ($resetCache) UzbotEditor::resetCache();
	}
}
