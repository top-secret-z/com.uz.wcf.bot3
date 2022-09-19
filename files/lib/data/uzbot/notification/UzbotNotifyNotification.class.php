<?php 
namespace wcf\data\uzbot\notification;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\notification\object\UzbotNotifyUserNotificationObject;
use wcf\util\MessageUtil;

/**
 * Creates system notification for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyNotification {
	public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null) {
		// prepare text
		$content = MessageUtil::stripCrap($content);
		
		// test mode
		if ($bot->testMode) {
			$subject = $teaser = '';
			if (mb_strlen($content) > 63500) $content = mb_substr($content, 0, 63500) . ' ...';
			$result = serialize([$subject, $teaser, $content]);
			
			UzbotLogEditor::create([
					'bot' => $bot,
					'count' => 1,
					'testMode' => 1,
					'additionalData' => $result
			]);
			return;
		}
		
		UserNotificationHandler::getInstance()->fireEvent(
				'notify',
				'com.uz.wcf.bot3',
				new UzbotNotifyUserNotificationObject(new Uzbot($bot->botID)), [$receiver->userID], 
				[
						'message' => $content,
						'receiverID' => $receiver->userID,
						'botID' => $bot->botID
				]
		);
	}
}
