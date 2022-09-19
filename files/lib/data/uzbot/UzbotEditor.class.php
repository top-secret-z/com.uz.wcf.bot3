<?php
namespace wcf\data\uzbot;
use wcf\data\DatabaseObjectEditor;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;

/**
 * Provides functions to edit Bots.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	public static $baseClass = Uzbot::class;
	
	/**
	 * @inheritDoc
	 */
	public static function resetCache() {
		UzbotValidBotCacheBuilder::getInstance()->reset();
	}
}
