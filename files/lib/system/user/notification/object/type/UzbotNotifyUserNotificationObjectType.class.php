<?php
namespace wcf\system\user\notification\object\type;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\UzbotList;
use wcf\system\user\notification\object\UzbotNotifyUserNotificationObject;

/**
 * Represents a Bot notification object type.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyUserNotificationObjectType extends AbstractUserNotificationObjectType {
	/**
	 * @inheritDoc
	 */
	protected static $decoratorClassName = UzbotNotifyUserNotificationObject::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $objectClassName = Uzbot::class;
	
	/**
	 * @inheritDoc
	 */
	protected static $objectListClassName = UzbotList::class;
}
