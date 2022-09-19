<?php
namespace wcf\system\event\listener;
use wcf\data\user\User;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Listen to User updates by user for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserSettingListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		// check module
		if (!MODULE_UZBOT) return;
		
		$action = $eventObj->getActionName();
		$change = [];
		
		// profile action for cover
		if ($className == 'wcf\data\user\UserProfileAction') {
			if ($action == 'deleteCoverPhoto') {
				$change['cover'] = 'deleted';
			}
			if ($action == 'uploadCoverPhoto') {
				$change['cover'] = 'uploaded';
			}
		}
		
		if ($className == 'wcf\data\user\UserAction') {
			// only action update
			if ($eventObj->getActionName() == 'update') {
				
				// only user itself
				$objects = $eventObj->getObjects();
				if (empty($objects)) return;
				if ($objects[0]->userID != WCF::getUser()->userID) return;
				
				// get changes
				$params = $eventObj->getParameters();
				
				if (isset($params['data']['enableGravatar'])) {
					if ($params['data']['enableGravatar']) $change['avatar'] = 'gravatar';
					else {
						if (isset($params['data']['avatarID'])) $change['avatar'] = 'no';
						else $change['avatar'] = 'yes';
					}
				}
				if (isset($params['data']['signature'])) $change['signature'] = $params['data']['signature'];
				if (isset($params['data']['email'])) $change['email'] = $params['data']['email'];
				if (isset($params['data']['username'])) $change['username'] = $params['data']['username'];
				if (isset($params['data']['oldUsername'])) $change['oldUsername'] = $params['data']['oldUsername'];
				if (isset($params['data']['userTitle']) && isset($objects[0]->userTitle)) {
					if ($objects[0]->userTitle != $params['data']['userTitle']) $change['userTitle'] = $params['data']['userTitle'];
				}
				if (isset($params['options'])) $change['options'] = '';
				if (isset($params['data']['quitStarted']) && $params['data']['quitStarted'] > 0) $change['quitStarted'] = $params['data']['quitStarted'];
				if (isset($params['data']['quitStarted']) && $params['data']['quitStarted'] == 0) $change['quitEnded'] = 0;
			}
		}
		
		if (empty($change)) return;
		
		// Read all active, valid activity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_setting']);
		if (!count($bots)) return;
		
		// get / set data
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// set user
		$user = WCF::getUser();
		$affectedUserIDs[] = $user->userID;
		$countToUserID[$user->userID] = 1;
		
		// step through bots
		foreach ($bots as $bot) {
			$placeholders = $setting = [];
			
			if ($bot->userSettingAvatarOption && isset($change['avatar'])) $setting['avatar'] = $change['avatar'];
			if ($bot->userSettingSignature && isset($change['signature'])) $setting['signature'] = $change['signature'];
			if ($bot->userSettingEmail && isset($change['email'])) $setting['email'] = $change['email'];
			if ($bot->userSettingUsername && isset($change['username'])) $setting['username'] = $change['username'];
			if ($bot->userSettingUsername && isset($change['oldUsername'])) $setting['oldUsername'] = $change['oldUsername'];
			if ($bot->userSettingUserTitle && isset($change['userTitle'])) $setting['userTitle'] = $change['userTitle'];
			if ($bot->userSettingSelfDeletion && isset($change['quitStarted'])) $setting['quitStarted'] = $change['quitStarted'] + 7 * 24 * 3600;
			if ($bot->userSettingSelfDeletion && isset($change['quitEnded'])) $setting['quitEnded'] = $change['quitEnded'];
			if ($bot->userSettingOther && isset($change['options'])) $setting['options'] = $change['options'];
			if ($bot->userSettingCover && isset($change['cover'])) $setting['cover'] = $change['cover'];
				
			if (empty($setting)) continue;
			
			$placeholders['user-setting'] = $setting;
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
