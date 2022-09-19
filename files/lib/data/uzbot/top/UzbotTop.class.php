<?php 
namespace wcf\data\uzbot\top;
use wcf\data\DatabaseObject;
use wcf\data\like\Like;
use wcf\system\WCF;

/**
 * Represents a Bot Top entry
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTop extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'uzbot_top';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'topID';
	
	/**
	 * Likes
	 */
	public static function refreshLike() {
		$userID = null;
		$sql = "SELECT		objectUserID as userID, COUNT(*) AS count
				FROM		wcf".WCF_N."_like
				WHERE		likeValue = ?
				GROUP BY	objectUserID
				ORDER BY 	count DESC";
		$statement = WCF::getDB()->prepareStatement($sql, 1);
		$statement->execute([Like::LIKE]);
		if ($row = $statement->fetchArray()) {
			$userID = $row['userID'];
		}
		
		if ($userID) {
			$editor = new UzbotTopEditor(new UzbotTop(1));
			$editor->update(['liked' => $userID]);
		}
		
		return $userID;
	}
}
