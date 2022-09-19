<?php 
namespace wcf\data\uzbot\notification;
use wcf\data\comment\Comment;
use wcf\data\comment\CommentEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\User;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\comment\manager\UserProfileCommentManager;
use wcf\system\exception\SystemException;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\LanguageFactory;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\user\notification\object\CommentUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;

/**
 * Creates wall comments for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyComment {
	public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null) {
		// preset some data
		$objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName('com.woltlab.wcf.comment.commentableContent', 'com.woltlab.wcf.user.profileComment');
		$objectType = ObjectTypeCache::getInstance()->getObjectType($objectTypeID);
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
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
			return true;
		}
		
		$htmlInputProcessor = new HtmlInputProcessor();
		$htmlInputProcessor->process($content, 'com.woltlab.wcf.comment', 0);
		
		$commentData = [
				'objectTypeID' => $objectTypeID,
				'objectID' => $receiver->userID,
				'time' => TIME_NOW,
				'userID' => $bot->senderID,
				'username' => $bot->sendername,
				'message' => $htmlInputProcessor->getHtml(),
				'isDisabled' => 0,
				'enableHtml' => 1,
				'responses' => 0,
				'responseIDs' => serialize([]),
				'isUzbot' => 1
		];
		
		try {
			$createdComment = CommentEditor::create($commentData);
			
			UserProfileCommentManager::getInstance()->updateCounter($receiver->userID, 1);
			
			// fire activity event
			if ($bot->commentActivity) {
				$objectType = ObjectTypeCache::getInstance()->getObjectType($objectTypeID);
				if (UserActivityEventHandler::getInstance()->getObjectTypeID($objectType->objectType.'.recentActivityEvent')) {
					UserActivityEventHandler::getInstance()->fireEvent($objectType->objectType.'.recentActivityEvent', $createdComment->commentID, null, $bot->senderID);
				}
			}
			
			// fire notification event
			if (UserNotificationHandler::getInstance()->getObjectTypeID($objectType->objectType.'.notification')) {
				$notificationObject = new CommentUserNotificationObject($createdComment);
				$notificationObjectType = UserNotificationHandler::getInstance()->getObjectTypeProcessor($objectType->objectType.'.notification');
					
				$userID = $notificationObjectType->getOwnerID($createdComment->commentID);
				if ($userID != $bot->senderID) {
					UserNotificationHandler::getInstance()->fireEvent('comment', $objectType->objectType . '.notification', $notificationObject, [$userID], ['objectUserID' => $userID]);
				}
			}
		}
		catch (SystemException $e) {
			// users may get lost; check sender again to abort
			if (!$bot->checkSender(true, true)) return false;
			
			// report any other error und continue
			if ($bot->enableLog) {
				$error = $defaultLanguage->get('wcf.acp.uzbot.log.notify.error') . ' ' . $e->getMessage();
				
				UzbotLogEditor::create([
						'bot' => $bot,
						'status' => 1,
						'count' => 1,
						'additionalData' => $error
				]);
			}
		}
		
		return true;
	}
	
	/**
	 * sends comment via background queue
	 */
	public function deliver(array $data) {
		$commentData = $data['commentData'];
		
		$createdComment = CommentEditor::create($commentData);
		
		UserProfileCommentManager::getInstance()->updateCounter($commentData['objectID'], 1);
		
		// Fire activity event
		if ($data['activity']) {
			$objectType = ObjectTypeCache::getInstance()->getObjectType($commentData['objectTypeID']);
			if (UserActivityEventHandler::getInstance()->getObjectTypeID($objectType->objectType.'.recentActivityEvent')) {
				UserActivityEventHandler::getInstance()->fireEvent($objectType->objectType.'.recentActivityEvent', $createdComment->commentID, null, $commentData['userID']);
			}
		}
		
		// fire notification event
		if (UserNotificationHandler::getInstance()->getObjectTypeID($objectType->objectType.'.notification')) {
			$notificationObject = new CommentUserNotificationObject($createdComment);
			$notificationObjectType = UserNotificationHandler::getInstance()->getObjectTypeProcessor($objectType->objectType.'.notification');
			
			$userID = $notificationObjectType->getOwnerID($createdComment->commentID);
			if ($userID != $commentData['userID']) {
				UserNotificationHandler::getInstance()->fireEvent('comment', $objectType->objectType . '.notification', $notificationObject, [$userID], ['objectUserID' => $userID]);
			}
		}
	}
}
