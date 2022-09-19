<?php
namespace wcf\data\uzbot;
use wcf\data\DatabaseObjectList;
use wcf\system\cache\builder\CategoryCacheBuilder;
use wcf\system\WCF;

/**
 * Represents a list of Bots.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = Uzbot::class;
	
	/**
	 * Returns a list of available categories.
	 */
	public function getAvailableCategories() {
		$categories = CategoryCacheBuilder::getInstance()->getData([], 'categories');
		
		$categoryIDs = [];
		$sql = "SELECT	DISTINCT categoryID
				FROM	wcf".WCF_N."_uzbot";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			if ($row['categoryID']) {
				$categoryIDs[$row['categoryID']] = $categories[$row['categoryID']]->getTitle();
			}
		}
		ksort($categoryIDs);
		
		return $categoryIDs;
	}
	
	/**
	 * Returns a list of available notifies.
	 */
	public function getAvailableNotifyDes() {
		$notifies = [];
		$sql = "SELECT	DISTINCT notifyDes
				FROM	wcf".WCF_N."_uzbot";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			if ($row['notifyDes']) {
				$notifies[$row['notifyDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.notify.' . $row['notifyDes']);
			}
		}
		ksort($notifies);
		
		return $notifies;
	}
	
	/**
	 * Returns a list of available types.
	 */
	public function getAvailableTypeDes() {
		$types = [];
		$sql = "SELECT	DISTINCT typeDes
				FROM	wcf".WCF_N."_uzbot";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			if ($row['typeDes']) {
				$types[$row['typeDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.type.' . $row['typeDes']);
			}
		}
		ksort($types);
		
		return $types;
	}
}
