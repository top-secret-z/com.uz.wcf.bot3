<?php
namespace wcf\data\uzbot\notification;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit Bot notifications.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	public static $baseClass = UzbotNotify::class;
}
