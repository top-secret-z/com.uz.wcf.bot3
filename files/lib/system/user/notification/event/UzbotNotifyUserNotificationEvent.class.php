<?php
namespace wcf\system\user\notification\event;
use wcf\data\user\User;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Notification event for Bot.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyUserNotificationEvent extends AbstractUserNotificationEvent {
	/**
	 * @inheritDoc
	 */
	protected $stackable = false;
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return $this->getLanguage()->get('wcf.user.notification.uzbot.title');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getMessage() {
		return $this->getLanguage()->getDynamicVariable('wcf.user.notification.uzbot.message', [
				'author' => $this->author,
				'message' => $this->additionalData['message']
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function getEmailMessage($notificationType = 'instant') {
		throw new \LogicException('Unreachable');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink() {
		return LinkHandler::getInstance()->getLink('NotificationList');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getEventHash() {
		return sha1($this->eventID . '-' . $this->additionalData['message'] . $this->additionalData['receiverID'] . $this->additionalData['botID']);
	}
	
	/**
	 * @inheritDoc
	 */
	public function supportsEmailNotification() {
		return false;
	}
}
