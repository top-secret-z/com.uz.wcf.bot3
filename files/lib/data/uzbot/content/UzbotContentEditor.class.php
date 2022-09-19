<?php
namespace wcf\data\uzbot\content;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit Bot content.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotContentEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = UzbotContent::class;
}
