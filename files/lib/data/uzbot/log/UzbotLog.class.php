<?php 
namespace wcf\data\uzbot\log;
use wcf\data\DatabaseObject;

/**
 * Represents a Bot Log entry
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotLog extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'uzbot_log';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'logID';
	
	/**
	 * Returns unserialized additional data.
	 */
	public function getAdditionalDataUnserialized() {
		return unserialize($this->additionalData);
	}
}
