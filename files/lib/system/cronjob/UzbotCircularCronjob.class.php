<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\UserList;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\UzbotList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Circular cronjob for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotCircularCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// Read all active, valid activity bots, abort if none / no cache
		$botList = new UzbotList();
		$botList->getConditionBuilder()->add('typeDes = ?', ['system_circular']);
		$botList->getConditionBuilder()->add('isDisabled = ?', [0]);
		$botList->readObjects();
		$bots = $botList->getObjects();
		if (empty($bots)) return;
		
		// get language for log
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// Step through all bots and get matching users
		foreach ($bots as $bot) {
			// preset data
			$affectedUserIDs = $countToUserID = [];
			$count = 0;
			
			// check for execution, disable bot if required
			$execs = unserialize($bot->cirExecution);
			
			if (empty($execs)) {
				$editor = new UzbotEditor($bot);
				$editor->update(['isDisabled' => 1]);
				continue;
			}
			
			$next = reset($execs);
			if (!$bot->testMode) {
				if ($next > TIME_NOW) continue;
			}
			
			// must be sent; get data straight
			array_shift($execs);
			$cirCounter = $bot->cirCounter + $bot->cirCounterInterval;
			if (!$bot->testMode) {
				$editor = new UzbotEditor($bot);
				$editor->update([
							'cirExecution' => serialize($execs),
							'cirCounter' => $cirCounter
				]);
			}
			
			// get matching users
			$userList = new UserList();
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
						if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
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
			
			// check for and prepare notification
			if (!$bot->notifyID || !$count) continue;
			$notify = $bot->checkNotify(true, true);
			if ($notify === null) continue;
			
			$placeholders = [];
			$placeholders['count'] = $count;
			$placeholders['counter'] = $cirCounter;
			$placeholders['counter+1'] = $cirCounter + 1;
			$placeholders['counter-1'] = $cirCounter - 1;
			
			// special case for circular
			// if notification without receiver, send only one notification
			if (!$notify->hasReceiver) {
				$countToUserID = $affectedUserIDs = $testUserIDs = $testToUserIDs = [];
			}
			else {
				foreach ($affectedUserIDs as $id) {
					$countToUserID[$id] = 1;
				}
			}
			
			// test mode
			$testUserIDs = $testToUserIDs = [];
			if (count($affectedUserIDs)) {
				$userID = reset($affectedUserIDs);
				$testUserIDs[] = $userID;
				$testToUserIDs[$userID] = $countToUserID[$userID];
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
