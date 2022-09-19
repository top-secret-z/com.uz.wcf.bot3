<?php 
namespace wcf\data\uzbot\notification;
use wcf\data\DatabaseObject;
use wcf\data\uzbot\Uzbot;
use wcf\system\WCF;

/**
 * Represents a Bot notification
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotify extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'uzbot_notify';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'id';
	
	/**
	 * @inheritDoc
	 */
	public function getTitle() {
		return WCF::getLanguage()->get('wcf.acp.uzbot.notify.' . $this->notifyTitle);
	}
	
	/**
	 * returns Notify with given notifyID
	 */
	public static function getNotifyByID($notifyID) {
		$sql = "SELECT	*
				FROM 	wcf".WCF_N."_uzbot_notify
				WHERE	notifyID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$notifyID]);
		$row = $statement->fetchArray();
		if (!$row) $row = [];
		return new UzbotNotify(null, $row);
	}
	
	/**
	 * returns NotifyID matching the given notifyTitle or 0.
	 */
	public static function getNotifyIDFromTitel($notifyTitle) {
		$sql = "SELECT	notifyID
				FROM 	wcf".WCF_N."_uzbot_notify
				WHERE	notifyTitle = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$notifyTitle]);
		if ($row = $statement->fetchArray()) {
			return $row['notifyID'];
		}
		return 0;
	}
}
