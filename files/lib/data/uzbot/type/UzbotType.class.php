<?php 
namespace wcf\data\uzbot\type;
use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents a Bot type.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotType extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'uzbot_type';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'id';
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return WCF::getLanguage()->get('wcf.acp.uzbot.type.' . $this->typeTitle);
	}
	
	/**
	 * return UzbotType with given typeID
	 */
	public static function getTypeByID($typeID) {
		$sql = "SELECT	*
				FROM 	wcf".WCF_N."_uzbot_type
				WHERE	typeID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$typeID]);
		$row = $statement->fetchArray();
		if (!$row) $row = [];
		return new UzbotType(null, $row);
	}
}
