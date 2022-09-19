<?php
namespace wcf\system\event\listener;
use wcf\data\user\trophy\UserTrophyList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Listen to trophy creation for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTrophyListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// check modules
		if (!MODULE_UZBOT) return;
		if (!MODULE_TROPHY) return;
		
		// only toggle
		if ($eventObj->getActionName() != 'toggle') return;
		
		// only if bots
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_trophy']);
		if (!count($bots)) return;
		
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// only on enable
		foreach($eventObj->getObjects() as $object) {
			$trophy = $object->getDecoratedObject();
			if (!$trophy->isDisabled) continue;
			
			// get user trophies
			$userTrophyList = new UserTrophyList();
			$userTrophyList->getConditionBuilder()->add('trophyID = ?', [$trophy->trophyID]);
			$userTrophyList->readObjects();
			$userTrophies = $userTrophyList->getObjects();
			if (empty($userTrophies)) continue;
			
			// get total number of user trophies
			$userTrophyTotal = 0;
			$sql = "SELECT	COUNT(*) AS count
					FROM	wcf".WCF_N."_user_trophy
					WHERE	trophyID IN (SELECT trophyID FROM wcf".WCF_N."_trophy WHERE isDisabled = ?)";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([0]);
			$userTrophyTotal = $statement->fetchColumn();
			
			// step through user trophies
			foreach($userTrophies as $userTrophy) {
				// set some data / placeholders
				$affectedUserIDs = $countToUserID = $placeholders = [];
				
				$affectedUserIDs[] = $userTrophy->userID;
				$countToUserID[$userTrophy->userID] = 1;
				
				// general / language independent data
				$placeholders['count'] = $userTrophyTotal;
				$placeholders['count-user'] = 1;
				$placeholders['trophy-id'] = $trophy->trophyID;
				$placeholders['trophy-link'] = $trophy->getLink();
				$placeholders['trophy-name'] = $trophy->title;
				$placeholders['translate'] = ['trophy-name'];
				
				foreach ($bots as $bot) {
					// check trophyIDs
					if (!empty($bot->userCount)) {
						$counts = explode(',', $bot->userCount);
						if (!in_array($trophy->trophyID, $counts)) continue;
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
}
