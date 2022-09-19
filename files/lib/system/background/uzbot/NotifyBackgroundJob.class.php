<?php
namespace wcf\system\background\uzbot;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\notification\UzbotNotify;
use wcf\data\uzbot\type\UzbotType;
use wcf\system\background\job\AbstractBackgroundJob;
use wcf\system\background\uzbot\NotifyBackgroundJob;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Sends notifications for a bot.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class NotifyBackgroundJob extends AbstractBackgroundJob {
	/**
	 * job data
	 */
	protected $data;
	protected $count;
	protected $notification;
	
	/**
	 * Creates the job.
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}
	
	/**
	 * Notifies will be sent with an increasing timeout between the tries.
	 */
	public function retryAfter() {
		switch ($this->getFailures()) {
			case 1:
				return 5 * 60;
			case 2:
				return 10 * 60;
			case 3:
				return 20 * 60;
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function perform() {
		// get and check basic data
		$first = reset($this->data);
		$bot = $first['bot'];
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// basic checks
		$comment = [];
		$status = 2;	// disable
		$tempBot = new Uzbot($bot->botID);
		if (!$tempBot->botID) $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.bot.missing');
		
		$notify = UzbotNotify::getNotifyByID($bot->notifyID);
		if (!$notify->notifyID) $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.notify.missing');
		
		$type = UzbotType::getTypeByID($bot->typeID);
		if (!$type->typeID) $comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.type.missing');
		
		// check module
		if (!empty($notify->neededModule) && !constant($notify->neededModule)) {
			$comment[] = $defaultLanguage->get('wcf.acp.uzbot.log.module.disabled');
			$status = 1;	// warn
		}
		
		if (count($comment)) {
			if ($bot->enableLog) {
				UzbotLogEditor::create([
						'bot' => $bot,
						'status' => $status,
						'additionalData' => implode(', ', $comment)
				]);
			}
			return;
		}
		
		// step through all sub-jobs and send
		$notification = new $notify->notifyFunction;
		$notifyCount = 0;
		$notifyIDs = [];
		
		try {
			foreach ($this->data as $job) {
				// set language
				$language = LanguageFactory::getInstance()->getLanguage($job['languageID']);
				if (!$language->languageID) $language = $defaultLanguage;
				
				// check receiver and send
				if ($notify->hasReceiver) {
					if(!$job['receiverID']) continue;
					
					$receiver = UserProfileRuntimeCache::getInstance()->getObject($job['receiverID']);
					if ($receiver->userID) {
						$notification->send($job['bot'], $job['content'], $job['subject'], $job['teaser'], $language, $receiver, $job['tags']);
						$notifyCount ++;
						$notifyIDs[] = $receiver->userID;
					}
				}
				else {
					$receiver = null;
					$notification->send($job['bot'], $job['content'], $job['subject'], $job['teaser'], $language, $receiver, $job['tags']);
					$notifyCount ++;
				}
			}
			
			if ($bot->enableLog && $notifyCount) {
				UzbotLogEditor::create([
						'bot' => $bot,
						'count' => $notifyCount,
						'status' => 0,
						'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.notify.count.id', ['count' => $notifyCount, 'id' => implode(', ', $notifyIDs)])
				]);
			}
		}
		catch (PermanentFailure $e) {
			\wcf\functions\exception\logThrowable($e);
		}
	}
}
