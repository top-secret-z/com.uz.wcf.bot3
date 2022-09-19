<?php
namespace wcf\system\event\listener;
use wcf\data\user\User;
use wcf\data\user\infraction\warning\UserInfractionWarning;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Listen to bans of users for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserBanListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// need module
		if (!MODULE_UZBOT) return;
		
		if ($className == 'wcf\system\infraction\suspension\BanSuspensionAction') {
			// suspend
			if ($eventName == 'suspend') {
				// need valid bots
				$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_ban']);
				if (!count($bots)) return;
				
				// set data
				$affectedUserIDs = $placeholders = [];
				$suspension = $parameters['suspension'];
				$userSuspension = $parameters['userSuspension'];
				$warning = new UserInfractionWarning($userSuspension->warningID);
				
				$affectedUserIDs[] = $userSuspension->userID;
				$banner = WCF::getUser();
				
				$placeholders['count'] = count($affectedUserIDs);
				$placeholders['ban-reason'] = $warning->reason;
				$placeholders['ban-expire'] = $suspension->expires ? date('Y-m-d', TIME_NOW + $suspension->expires) : 'wcf.uzbot.system.never';
				$placeholders['translate'][] = 'ban-expire';
				
				if ($banner->userID) {
					$placeholders['ban-userid'] = $banner->userID;
					$placeholders['ban-username'] = $banner->username;
					$placeholders['ban-userlink'] = $banner->getLink();
					$placeholders['ban-userlink2'] = StringUtil::getAnchorTag($banner->getLink(), $banner->username);
				}
				else {
					$placeholders['ban-userid'] = 0;
					$placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
					$placeholders['translate'][] = 'ban-username';
					$placeholders['translate'][] = 'ban-userlink';
				}
				
				// step through bots
				foreach ($bots as $bot) {
					// send to scheduler
					$data = [
							'bot' => $bot,
							'placeholders' => $placeholders,
							'affectedUserIDs' => $affectedUserIDs,
							'countToUserID' => []
					];
					
					$job = new NotifyScheduleBackgroundJob($data);
					BackgroundQueueHandler::getInstance()->performJob($job);
				}
				
				return;
			}
			if ($eventName == 'unsuspend') {
				// need valid bots
				$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_unban']);
				if (!count($bots)) return;
				
				// set data
				$affectedUserIDs = $placeholders = [];
				$userSuspension = $parameters['userSuspension'];
				$affectedUserIDs[] = $userSuspension->userID;
				
				$placeholders['count'] = count($affectedUserIDs);
				if ($userSuspension->revoker) {
					$revoker = new User($userSuspension->revoker);
					$placeholders['ban-userid'] = $revoker->userID;
					$placeholders['ban-username'] = $revoker->username;
					$placeholders['ban-userlink'] = $revoker->getLink();
					$placeholders['ban-userlink2'] = StringUtil::getAnchorTag($revoker->getLink(), $revoker->username);
				}
				else {
					$placeholders['ban-userid'] = 0;
					$placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
					$placeholders['translate'][] = 'ban-username';
					$placeholders['translate'][] = 'ban-userlink';
				}
				
				// step through bots
				foreach ($bots as $bot) {
					// send to scheduler
					$data = [
							'bot' => $bot,
							'placeholders' => $placeholders,
							'affectedUserIDs' => $affectedUserIDs,
							'countToUserID' => []
					];
					
					$job = new NotifyScheduleBackgroundJob($data);
					BackgroundQueueHandler::getInstance()->performJob($job);
				}
				
				return;
			}
			
			return;
		}
		
		// need action ban or unban
		$action = $eventObj->getActionName();
		
		if ($action == 'ban') {
			// need valid bots
			$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_ban']);
			if (!count($bots)) return;
			
			// get data
			$affectedUserIDs = $placeholders = [];
			$affectedUserIDs = $eventObj->getObjectIDs();
			$banner = WCF::getUser();
			
			// set placeholders
			$placeholders['count'] = count($affectedUserIDs);
			$params = $eventObj->getParameters();
			
			$placeholders['ban-reason'] = $params['banReason'];
			$placeholders['ban-expire'] = $params['banExpires'] ? $params['banExpires'] : 'wcf.uzbot.system.never';
			$placeholders['translate'][] = 'ban-expire';
			
			if ($banner->userID) {
				$placeholders['ban-userid'] = $banner->userID;
				$placeholders['ban-username'] = $banner->username;
				$placeholders['ban-userlink'] = $banner->getLink();
				$placeholders['ban-userlink2'] = StringUtil::getAnchorTag($banner->getLink(), $banner->username);
			}
			else {
				$placeholders['ban-userid'] = 0;
				$placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
				$placeholders['translate'][] = 'ban-username';
				$placeholders['translate'][] = 'ban-userlink';
			}
			
			// step through bots
			foreach ($bots as $bot) {
				// send to scheduler
				$data = [
						'bot' => $bot,
						'placeholders' => $placeholders,
						'affectedUserIDs' => $affectedUserIDs,
						'countToUserID' => []
				];
				
				$job = new NotifyScheduleBackgroundJob($data);
				BackgroundQueueHandler::getInstance()->performJob($job);
			}
		}
		
		if ($action == 'unban') {
			// need valid bots
			$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_unban']);
			if (!count($bots)) return;
			
			// get data
			$affectedUserIDs = $placeholders = [];
			$affectedUserIDs = $eventObj->getObjectIDs();
			$banner = WCF::getUser();
			
			// set placeholders
			$placeholders['count'] = count($affectedUserIDs);
			
			if ($banner->userID) {
				$placeholders['ban-userid'] = $banner->userID;
				$placeholders['ban-username'] = $banner->username;
				$placeholders['ban-userlink'] = $banner->getLink();
				$placeholders['ban-userlink2'] = StringUtil::getAnchorTag($banner->getLink(), $banner->username);
			}
			else {
				$placeholders['ban-userid'] = 0;
				$placeholders['ban-username'] = $placeholders['ban-userlink'] = $placeholders['ban-userlink2'] = 'wcf.uzbot.system';
				$placeholders['translate'][] = 'ban-username';
				$placeholders['translate'][] = 'ban-userlink';
			}
			
			// step through bots
			foreach ($bots as $bot) {
				// send to scheduler
				$data = [
						'bot' => $bot,
						'placeholders' => $placeholders,
						'affectedUserIDs' => $affectedUserIDs,
						'countToUserID' => []
				];
				
				$job = new NotifyScheduleBackgroundJob($data);
				BackgroundQueueHandler::getInstance()->performJob($job);
			}
		}
	}
}
