<?php
namespace wcf\data\uzbot\top;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit Bot Top entries.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTopEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = UzbotTop::class;
}
