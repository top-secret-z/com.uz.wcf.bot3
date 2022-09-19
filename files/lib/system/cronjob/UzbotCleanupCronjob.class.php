<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\WCF;

/**
 * Daily Cleanup for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotCleanupCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// delete old log entries
		$sql = "DELETE FROM wcf".WCF_N."_uzbot_log
				WHERE	time < ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([TIME_NOW - UZBOT_LOG_DELETE * 86400]);
	}
}
