<?php 
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\Regex;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Checks for new WCF errors
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotErrorCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// Read all active, valid activity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_error']);
		if (!count($bots)) return;
		
		// get log file and last modification time; don't care about status cache
		$logFile = WCF_DIR . 'log/' . gmdate('Y-m-d', TIME_NOW) . '.txt';
		$lastModification = 0;
		if (file_exists($logFile)) $lastModification = filemtime($logFile);
		
		foreach ($bots as $bot) {
			$count = 0;
			$placeholders = [];
			
			if ($lastModification != 0 && $lastModification != $bot->lastError) {
				
				// copy from ExceptionLogViewPage
				$contents = file_get_contents($logFile);
				$contents = StringUtil::unifyNewlines($contents);
				$exceptions = [];
				$split = new Regex('(?:^|\n<<<<\n\n)(?:<<<<<<<<([a-f0-9]{40})<<<<\n|$)');
				$contents = $split->split($contents, Regex::SPLIT_NON_EMPTY_ONLY | Regex::CAPTURE_SPLIT_DELIMITER);
				try {
					$exceptions = call_user_func_array('array_merge', array_map(
							function($v) {
								return [$v[0] => $v[1]];
							},
							array_chunk($contents, 2)
					));
				}
				catch (\Exception $e) {
					continue;
				}
				
				// create text, last 5 errors
				$text = $textFull = $textUnformatted = '';
				foreach ($exceptions as $id => $exception) {
					$count ++;
					
					$textUnformatted .= $exception . '\n\n';
					
					$temp = explode("\n", $exception);
					try {
						if ($bot->lastError < strtotime($temp[0])) {
							$textFull .= '<br><br>' . $id . '<br>'. implode('<br>', $temp);
							$text .= '<br><br>' . $id . '<br>' . $temp[0] . '<br>' . $temp[1] . '<br>' . $temp[12];
						}
					}
					catch (\Exception $e) {
						continue;
					}
				}
				
				$placeholders['count'] = $count;
				$placeholders['errors-full'] = $textFull;
				$placeholders['errors-unformatted'] = $textUnformatted;
				$placeholders['errors'] = $text;
				
				if (!$bot->testMode) {
					$editor = new UzBotEditor($bot);
					$editor->update([
							'lastError' => $lastModification
					]);
				}
			}
			
			// log action
			if ($bot->enableLog) {
				if (!$bot->testMode) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => $count,
							'additionalData' => ''
					]);
				}
				else {
					$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
					$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
							'objects' => $count,
							'users' => 0,
							'userIDs' => ''
					]);
					if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => $count,
							'testMode' => 1,
							'additionalData' => serialize(['', '', $result])
					]);
				}
			}
			
			// send to scheduler
			if ($count) {
				$data = [
						'bot' => $bot,
						'placeholders' => $placeholders,
						'affectedUserIDs' => [],
						'countToUserID' => []
				];
				
				$job = new NotifyScheduleBackgroundJob($data);
				BackgroundQueueHandler::getInstance()->performJob($job);
			}
		}
		
		UzbotEditor::resetCache();
	}
}
