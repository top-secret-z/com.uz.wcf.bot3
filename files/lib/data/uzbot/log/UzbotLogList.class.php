<?php
namespace wcf\data\uzbot\log;
use wcf\data\DatabaseObjectList;
use wcf\system\WCF;

/**
 * Represents a list of Bots log entries.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotLogList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = UzbotLog::class;
	
	/**
	 * @inheritDoc
	 */
	public $sqlOrderBy = 'time DESC';
	
	/**
	 * Returns a list of used bots.
	 */
	public function getBotNames() {
		$botTitles = [];
		$sql = "SELECT	DISTINCT botTitle
				FROM	wcf".WCF_N."_uzbot_log";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($this->getConditionBuilder()->getParameters());
		while ($row = $statement->fetchArray()) {
			$botTitles[$row['botTitle']] = $row['botTitle'];
		}
		
		ksort($botTitles);
		return $botTitles;
	}
	
	/**
	 * Returns a list of used actions.
	 */
	public function getBotActions() {
		$botActions = [];
		$sql = "SELECT	DISTINCT typeDes
				FROM	wcf".WCF_N."_uzbot_log";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($this->getConditionBuilder()->getParameters());
		while ($row = $statement->fetchArray()) {
			$botActions[$row['typeDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.type.' . $row['typeDes']);
		}
		
		ksort($botActions);
		return $botActions;
	}
	
	/**
	 * Returns a list of used status.
	 */
	public function getBotStatus() {
		$botStatus = [];
		$status = ['ok', 'warning', 'error'];
		
		$sql = "SELECT	DISTINCT status
				FROM	wcf".WCF_N."_uzbot_log";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($this->getConditionBuilder()->getParameters());
		while ($row = $statement->fetchArray()) {
			$text = WCF::getLanguage()->get('wcf.acp.uzbot.log.' . $status[$row['status']]);
			$botStatus[$text] = $text;
		}
		
		ksort($botStatus);
		return $botStatus;
	}
	
	/**
	 * Returns a list of used notifies.
	 */
	public function getBotNotifies() {
		$botNotifies = [];
		$sql = "SELECT	DISTINCT notifyDes
				FROM	wcf".WCF_N."_uzbot_log";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($this->getConditionBuilder()->getParameters());
		while ($row = $statement->fetchArray()) {
			$botNotifies[$row['notifyDes']] = WCF::getLanguage()->get('wcf.acp.uzbot.notify.type.' . $row['notifyDes']);
		}
		
		ksort($botNotifies);
		return $botNotifies;
	}
}
