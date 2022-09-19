<?php
namespace wcf\system\event\listener;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\exception\SystemException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Listen to infraction actions for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotInfractionListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// need modules
		if (!MODULE_UZBOT) return;
		if (!defined('MODULE_USER_INFRACTION') || !MODULE_USER_INFRACTION) return;
		
		// need action report
		if ($eventObj->getActionName() != 'create') return;
		
		// at present, only warning
		if ($className != 'wcf\data\user\infraction\warning\UserInfractionWarningAction') return;
		
		// need valid bots
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_warning'] );
		if (!count($bots)) return;
		
		// set /get data
		$returnValues = $eventObj->getReturnValues();
		$warning = $returnValues['returnValues'];
		$judge = $warning->getJudge();
		$user = $warning->getUser();
		$data = [];
		
		$data['reason'] = $warning->reason;
		$data['warningtitle'] = $warning->title;
		$data['points'] = $warning->points;
		$data['expires'] = $warning->expires;
		
		// try to get object. Null if warned in profile :-(
		$data['text'] = $data['title'] = $data['link'] = '';
		try {
			$object = $warning->getObject();
			if ($object !== null) {
				if (method_exists($object, 'getMessage')) $data['text'] = $object->getMessage();
				$data['title'] = $object->getTitle();
				$data['link'] = $object->getLink();
			}
			else {
				$data['text'] = $user->username;
				$data['title'] = $user->username;
				$data['link'] = $user->getLink();
			}
		}
		catch (SystemException $e) {
			// accept
		}
		
		// step through bots
		foreach ($bots as $bot) {
			// preset more data
			$affectedUserIDs = $placeholders = [];
			
			// set affected user
			if ($bot->changeAffected) $affectedUserIDs[] = $judge->userID;
			else $affectedUserIDs[] = $user->userID;
			
			// set placeholders
			$placeholders['count'] = 1;
			$placeholders['object-link'] = $data['link'];
			$placeholders['object-text'] = $data['text'];
			$placeholders['object-title'] = $data['title'];
			$placeholders['object-userid'] = $user->userID;
			$placeholders['object-userlink'] = $user->getLink();
			$placeholders['object-userlink2'] = StringUtil::getAnchorTag($user->getLink(), $user->username);
			$placeholders['object-username'] = $user->username;
			
			$placeholders['warning-expires'] = $data['expires'];
			$placeholders['warning-points'] = $data['points'];
			$placeholders['warning-reason'] = $data['reason'];
			$placeholders['warning-title'] = $data['warningtitle'];
			$placeholders['translate'] = ['warning-title', 'warning-expires'];
			
			$placeholders['warning-userid'] = $judge->userID;
			$placeholders['warning-userlink'] = $judge->getLink();
			$placeholders['warning-userlink2'] = StringUtil::getAnchorTag($judge->getLink(), $judge->username);
			$placeholders['warning-username'] = $judge->username;
			
			// send to scheduler
			$data2 = [
					'bot' => $bot,
					'placeholders' => $placeholders,
					'affectedUserIDs' => $affectedUserIDs,
					'countToUserID' => []
			];
			
			$job = new NotifyScheduleBackgroundJob($data2);
			BackgroundQueueHandler::getInstance()->performJob($job);
		}
	}
}
