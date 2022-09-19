<?php
namespace wcf\data\uzbot\type;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit Bot types.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTypeEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	public static $baseClass = UzbotType::class;
}
