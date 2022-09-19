<?php
namespace wcf\system\background\uzbot;
use wcf\system\background\job\AbstractBackgroundJob;

/**
 * Schedules notifications for a bot.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class NotifyScheduleBackgroundJob extends AbstractBackgroundJob {
	/**
	 * data to send
	 */
	protected $data;
	
	/**
	 * Creates the job.
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}
	
	/**
	 * Notifies will be sent with an increasing timeout between the tries.
	 */
	public function retryAfter() {
		switch ($this->getFailures()) {
			case 1:
				return 5 * 60;
			case 2:
				return 10 * 60;
			case 3:
				return 20 * 60;
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function perform() {
		$name = '\wcf\system\background\uzbot\NotifyScheduler';
		$name = new $name;
		
		try {
			$name->schedule($this->data);
		}
		catch (PermanentFailure $e) {
			\wcf\functions\exception\logThrowable($e);
		}
	}
}
