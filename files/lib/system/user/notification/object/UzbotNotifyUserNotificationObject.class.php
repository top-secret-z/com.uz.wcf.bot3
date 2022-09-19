<?php
namespace wcf\system\user\notification\object;
use wcf\data\uzbot\Uzbot;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\WCF;

/**
 * Represents a Bot notification object.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyUserNotificationObject extends DatabaseObjectDecorator implements IStackableUserNotificationObject {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Uzbot::class;
	
	/**
	 * returns the title
	 */
	public function getTitle() {
		return '';
	}
	
	/**
	 * returns the URL
	 */
	public function getURL() {
		return '';
	}
	
	/**
	 * returns the userID
	 */
	public function getAuthorID() {
		return $this->getDecoratedObject()->senderID;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getRelatedObjectID() {
		return $this->getDecoratedObject()->botID;
	}
}
