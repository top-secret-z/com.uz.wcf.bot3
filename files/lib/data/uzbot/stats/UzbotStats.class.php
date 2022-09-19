<?php 
namespace wcf\data\uzbot\stats;
use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents Stats object for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotStats extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'uzbot_stats';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'id';
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return WCF::getLanguage()->get($this->title);
	}
}
