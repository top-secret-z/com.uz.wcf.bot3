<?php
namespace wcf\data\uzbot;
use wcf\data\language\Language;
use wcf\data\user\group\UserGroup;
use wcf\data\user\group\UserGroupList;
use wcf\data\uzbot\Uzbot;
use wcf\system\exception\SystemException;
use wcf\system\WCF;
use wcf\util\DateUtil;
use wcf\util\StringUtil;

/**
 * Utility functions for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUtils {
	/**
	 * convert placeholder
	 * receiver is set in notification
	 */
	public static function convertPlaceholders($text, $language, array $placeholders = [], $userProfile = null, $replace = false) {
		
		// translate placeholders
		if (isset($placeholders['translate'])) {
			foreach ($placeholders['translate'] as $item) {
				if ($item == 'warning-expires') {
					if ($placeholders['warning-expires'] == 0) {
						$placeholders['warning-expires'] = $language->get('wcf.uzbot.system.never');
					}
					else {
						$placeholders['warning-expires'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['warning-expires']), DateUtil::DATE_FORMAT, $language) . ', ' . DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['warning-expires']), DateUtil::TIME_FORMAT, $language);
					}
				}
				$placeholders[$item] = $language->get($placeholders[$item]);
			}
		}
		
		// condense-list
		if (isset($placeholders['condense-list'])) {
			if (strpos($text, '[condense-list]') !== false) {
				// simply replace <p> first
				$count = 0;
				$text = str_replace('<p>[condense-list]</p>', '[condense-list]', $text, $count);
				
				// if no count, everything is fine, no further checking / replacing
				if (!$count) {
					// extract inner html to modify li items
					$htmlTags = $attributes = [];
					
					$doc = new \DomDocument();
					$doc->loadHTML('<body>' . $text . '</body>');
					$node = $doc->getElementsByTagName('body')->item(0);
					
					$found = 0;
					for ($i = 0, $length = $node->childNodes->length; $i < $length; $i++) {
						$child = $node->childNodes->item($i);
						if (strpos($child->nodeValue, '[condense-list]') !== false) {
							$found = 1;
							break;
						}
					}
					
					if ($found) {
						$innerHTML = '';
						if ($child->hasChildNodes()) {
							foreach ($child->childNodes as $child1) {
								$innerHTML .= $child->ownerDocument->saveXML( $child1 );
							}
						}
						if (!empty($innerHTML)) {
							$htmlTags = explode('[condense-list]', $innerHTML);
							$text = str_replace($htmlTags[0] . '[condense-list]' . $htmlTags[1], '[condense-list]', $text);
							$text = str_replace('<p>[condense-list]</p>', '[condense-list]', $text);
							
							$items = [];
							$lis = explode('</li> ', $placeholders['condense-list']);
							
							foreach ($lis as $li) {
								if (empty($li) || $li == '</ul>') continue;
								$items[] = str_replace('<li>', '<li>' . $htmlTags[0], $li) . $htmlTags[1];
							}
							$placeholders['condense-list'] = implode('</li>', $items) . '</li></ul>';
						}
					}
				}
			}
		}
		
		// period placeholders
		if (isset($placeholders['date-from'])) $placeholders['date-from'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['date-from']), DateUtil::DATE_FORMAT, $language);
		if (isset($placeholders['time-from'])) $placeholders['time-from'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['time-from']), DateUtil::TIME_FORMAT, $language);
		if (isset($placeholders['date-to'])) $placeholders['date-to'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['date-to']), DateUtil::DATE_FORMAT, $language);
		if (isset($placeholders['time-to'])) $placeholders['time-to'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['time-to']), DateUtil::TIME_FORMAT, $language);
		
		// add standard and user placeholders
		if (!isset($placeholders['date'])) $placeholders['date'] = DateUtil::format(DateUtil::getDateTimeByTimestamp(TIME_NOW), DateUtil::DATE_FORMAT, $language);
		if (!isset($placeholders['time'])) $placeholders['time'] = DateUtil::format(DateUtil::getDateTimeByTimestamp(TIME_NOW), DateUtil::TIME_FORMAT, $language);
		if (!isset($placeholders['page'])) $placeholders['page'] = $language->get(PAGE_TITLE);
		
		if ($replace) {
			// add user specific placeholders, if user is set preset with guest
			if (isset($userProfile)) {
				$placeholders['user-age'] = $userProfile->getAge() ? $userProfile->getAge() : 'x';
				$placeholders['user-email'] = $userProfile->email;
				
				$temp = [];
				$groupIDs = $userProfile->getGroupIDs();
				$groupList= new UserGroupList();
				$groupList->getConditionBuilder()->add("groupID IN (?)", [$groupIDs]);
				$groupList->readObjects();
				foreach ($groupList->getObjects() as $group) {
					$temp[] = $language->get($group->groupName);
				}
				$placeholders['user-groups'] = implode(', ', $temp);
				$placeholders['user-id'] = $userProfile->userID;
				$placeholders['user-name'] = $userProfile->username;
				$placeholders['user-profile'] = StringUtil::getAnchorTag($userProfile->getLink(), $userProfile->username);
				$placeholders['@user-profile'] = '[user=' . $userProfile->userID . ']' . $userProfile->username . '[/user]';
				
				// ranking
				$placeholders['user-rank'] = 0;
				if (isset($placeholders['ranks'])) {
					if (isset($placeholders['ranks'][$userProfile->userID])) {
						$placeholders['user-rank'] = $placeholders['ranks'][$userProfile->userID];
					}
				}
			}
			else {
				// set to guest to at least do something
				$placeholders['user-email'] = $placeholders['user-groups'] = $placeholders['user-id'] = $language->get('wcf.user.guest');
				$placeholders['user-name'] = $placeholders['user-profile'] = $placeholders['@user-profile'] = $language->get('wcf.user.guest');
				$placeholders['user-age'] = 'x';
			}
		}
		
		// add bot-specific placeholders
		// translate group name
		if (isset($placeholders['usergroup'])) $placeholders['usergroup'] = $language->get($placeholders['usergroup']);
		
		// translate guest in report placeholder
		if (isset($placeholders['report-reporter']) && $placeholders['report-reporter'] == 'wcf.user.guest') {
			$placeholders['report-reporter'] = $language->get($placeholders['report-reporter']);
		}
		if (isset($placeholders['report-user']) && $placeholders['report-user'] == 'wcf.user.guest') {
			$placeholders['report-user'] = $language->get($placeholders['report-user']);
		}
		
		// translate feedreader system date / time
		if (isset($placeholders['feeditem-systemdate'])) $placeholders['feeditem-systemdate'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['feeditem-systemdate']), DateUtil::DATE_FORMAT, $language);
		if (isset($placeholders['feeditem-systemtime'])) $placeholders['feeditem-systemtime'] = DateUtil::format(DateUtil::getDateTimeByTimestamp($placeholders['feeditem-systemtime']), DateUtil::TIME_FORMAT, $language);
		
		// format feedreader categories
		if (isset($placeholders['feeditem-categories'])) {
			if (empty($placeholders['feeditem-categories'])) {
				$placeholders['feeditem-categories'] = '';
			}
			else {
				$placeholders['feeditem-categories'] = implode(', ', $placeholders['feeditem-categories']);
			}
		}
		
		// translate packagenames
		if (isset($placeholders['updates'])) {
			$names = explode(', ', $placeholders['updates']);
			foreach ($names as $key => $name) {
				$names[$key] = $language->get($name);
			}
			$placeholders['updates'] = implode(', ', $names);
		}
		
		// user-setting
		if (isset($placeholders['user-setting'])) {
			$settings = $placeholders['user-setting'];
			$texts = [];
			
			// get oldUsername
			$oldUsername = '';
			if (isset($settings['oldUsername'])) {
				$oldUsername = $settings['oldUsername'];
			}
			
			foreach ($settings as $key => $setting) {
				if ($key == 'oldUsername') continue;
				if ($key == 'username') {
					if (empty($oldUsername)) {
						$texts[] = $language->getDynamicVariable('wcf.uzbot.setting.user.' . $key, ['value' => $setting]);
					}
					else {
						$texts[] = $language->getDynamicVariable('wcf.uzbot.setting.user.username.old', [
								'new' => $setting,
								'old' => $oldUsername
						]);
					}
				}
				else {
					$texts[] = $language->getDynamicVariable('wcf.uzbot.setting.user.' . $key, ['value' => $setting]);
				}
			}
			$placeholders['user-setting'] = implode(' | ', $texts);
		}
		
		// group-change
		if (isset($placeholders['group-change'])) {
			$placeholders['group-add'] = $placeholders['group-remove'] = '';
			$temp = $texts = [];
			$addNames = $placeholders['group-change']['add'];
			$removeNames = $placeholders['group-change']['remove'];
			
			foreach ($addNames as $name) {
				$temp[] = $language->get($name);
			}
			if (count($temp)) {
				$texts[] = $language->getDynamicVariable('wcf.uzbot.group.change.add', ['value' => implode(', ', $temp)]);
				$placeholders['group-add'] = implode(', ', $temp);
			}
			
			$temp = [];
			foreach ($removeNames as $name) {
				$temp[] = $language->get($name);
			}
			if (count($temp)) {
				$texts[] = $language->getDynamicVariable('wcf.uzbot.group.change.remove', ['value' => implode(', ', $temp)]);
				$placeholders['group-remove'] = implode(', ', $temp);
			}
			
			$placeholders['group-change'] = implode(' | ', $texts);
		}
		
		// stats
		if (isset($placeholders['stats']) && isset($placeholders['stats-lang'])) {
			$temp = $language->getDynamicVariable($placeholders['stats-lang'], $placeholders['stats']);
			$text = str_replace('[stats]', $temp, $text);
		}
		
		// translate options
		if (isset($placeholders['options'])) {
			if (empty($placeholders['options'])) {
				$placeholders['options'] = $language->get('wcf.acp.uzbot.option.none');
			}
			else {
				$temp = [];
				foreach ($placeholders['options'] as $key => $value) {
					$temp[] = $language->getDynamicVariable('wcf.acp.uzbot.option', ['key' => $language->get($key), 'value' => $value]);
				}
				$placeholders['options'] = implode($language->get('wcf.acp.uzbot.option.separator'), $temp);
			}
		}
		
		// fill placeholders
		foreach ($placeholders as $key => $placeholder) {
			// skip countToUser unless userProfile, translate
			if ($key == 'user-count' && !isset($userProfile)) continue;
			if ($key == 'translate') continue;
			if ($key == 'ranks') continue;
			if ($key == 'stats' || $key == 'stats-lang') continue;
			
			$key = '[' . $key . ']';
			$text = str_replace($key, $placeholder, $text);
		}
		return $text;
	}
	
	/**
	 * Calculate execution times
	 */
	public static function calcExecution($cirData) {
		// set start time / date parameters
		$timezone = new \DateTimeZone($cirData['cirTimezone']);
		$date = new \DateTime($cirData['cirStartTime']);
		$date->setTimezone($timezone);
		
		// no repetition
		if ($cirData['cirRepeatType'] == 'none') {
			return [$date->getTimestamp()];
		}
		
		// has repetitions
		$hour = $date->format('G');
		$minute = intval($date->format('i'));
		
		$execs = [];
		$count = 0;
		$time = 0;
		
		switch ($cirData['cirRepeatType']) {
			case 'hourly':
				$time = $date->getTimestamp();
				$execs[] = $time;
				$count ++;
				
				while($count <= $cirData['cirRepeatCount']) {
					$time += 60 * 60;
					$execs[] = $time;
					$count ++;
				}
				break;
					
			case 'halfDaily':
				$time = $date->getTimestamp();
				$execs[] = $time;
				$count ++;
				
				while($count <= $cirData['cirRepeatCount']) {
					$time += 12 * 60 * 60;
					$execs[] = $time;
					$count ++;
				}
				break;
					
			case 'daily':
				$time = $date->getTimestamp();
				$execs[] = $time;
				$count ++;
				
				while($count <= $cirData['cirRepeatCount']) {
					$time += 24 * 60 * 60;
					$execs[] = $time;
					$count ++;
				}
				break;
				
			case 'weekly':
				// take first as defined by startTime, others matching day
				// 'w' -> sunday = 0
				$execs[] = $date->getTimestamp();
				$count ++;
				$interval = new \DateInterval('P1D');
				while($count <= $cirData['cirRepeatCount']) {
					$date->add($interval);
					if ($date->format('w') == $cirData['cirWeekly_day']) {
						$execs[] = $date->getTimestamp();
						$count ++;
					}
				}
				break;
				
			case 'monthlyDoM':
				// take first as defined by startTime, others matching day
				$execs[] = $date->getTimestamp();
				$count ++;
				
				// actual date compoments
				$month = $date->format('n');
				$year = $date->format('Y');
				
				while($count <= $cirData['cirRepeatCount']) {
					// create dates and check
					$valid = checkdate($month, $cirData['cirMonthlyDoM_day'], $year);
					if ($valid) {
						$temp = new \DateTime();
						$temp->setTimezone($timezone);
						$temp->setDate($year, $month, $cirData['cirMonthlyDoM_day']);
						$temp->setTime($hour, $minute);
						
						if ($temp->getTimestamp() > $date->getTimestamp()) {
							$execs[] = $temp->getTimestamp();
							$count ++;
						}
					}
					
					// step through months / years
					$month ++;
					if ($month > 12) {
						$year ++;
						$month = 1;
					}
				}
				break;
				
			case 'monthlyDoW':
				// take first as defined by startTime, others matching day
				$execs[] = $date->getTimestamp();
				$count ++;
				
				// actual date compoments
				$month = $date->format('n');
				$year = $date->format('Y');
				$interval = new \DateInterval('P1D');
				
				while($count <= $cirData['cirRepeatCount']) {
					// start on first of month, step through days
					$temp = new \DateTime();
					$temp->setTimezone($timezone);
					$temp->setDate($year, $month, 1);
					$temp->setTime($hour, $minute);
					$xth = $match = 0;
					
					while(1) {
						// match day
						if ($temp->format('w') == $cirData['cirMonthlyDoW_day']) {
							$xth ++;
							$match = $temp->format('j');
							if ($xth == $cirData['cirMonthlyDoW_index']) break;
						}
						// add day until next month, reset to actual match
						$temp->add($interval);
						
						if ($temp->format('j') == 1) {
							$temp->setDate($year, $month, $match);
							break;
						}
					}
					
					// store if times match
					if ($temp->getTimestamp() > $date->getTimestamp()) {
						$execs[] = $temp->getTimestamp();
						$count ++;
					}
					
					// step through months / years
					$month ++;
					if ($month > 12) {
						$year ++;
						$month = 1;
					}
				}
				break;
				
			case 'quarterly':
				// actual date compoments
				$day = $date->format('d');
				$month = $date->format('n');
				$year = $date->format('Y');
				$newMonth = 0;
				$newYear = $year;
				
				// find next quarter
				switch ($month) {
					case 1:
						if ($day > 1) $newMonth = 4;
						else $newMonth = 1;
						break;
					case 2:
					case 3:
						$newMonth = 4;
						break;
					case 4:
						if ($day > 1) $newMonth = 7;
						else $newMonth = 4;
						break;
					case 5:
					case 6:
						$newMonth = 7;
						break;
					case 7:
						if ($day > 1) $newMonth = 10;
						else $newMonth = 7;
						break;
					case 8:
					case 9:
						$newMonth = 10;
						break;
					case 10:
						if ($day > 1) {
							$newMonth = 1;
							$newYear = $year + 1;
						}
						else $newMonth = 10;
						break;
					case 11:
					case 12:
						$newMonth = 1;
						$newYear = $year + 1;
						break;
				}
				
				while ($count <= $cirData['cirRepeatCount']) {
					// create dates and check
					$valid = checkdate($newMonth, 1, $newYear);
					if ($valid) {
						$temp = new \DateTime();
						$temp->setTimezone($timezone);
						$temp->setDate($newYear, $newMonth, 1);
						$temp->setTime($hour, $minute);
						
						if ($temp->getTimestamp() > $date->getTimestamp()) {
							$execs[] = $temp->getTimestamp();
							$count ++;
						}
					}
					
					// step through months / years
					switch ($newMonth) {
						case 1:
							$newMonth = 4;
							break;
						case 4:
							$newMonth = 7;
							break;
						case 7:
							$newMonth = 10;
							break;
						case 10:
							$newMonth = 1;
							$newYear ++;
							break;
					}
				}
				break;
				
			case 'halfyearly':
				// actual date compoments
				$day = $date->format('d');
				$month = $date->format('n');
				$year = $date->format('Y');
				$newMonth = 0;
				$newYear = $year;
				
				// find next half year
				switch ($month) {
					case 1:
						if ($day > 1) $newMonth = 7;
						else $newMonth = 1;
						break;
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
						$newMonth = 7;
						break;
					case 7:
						if ($day > 1) {
							$newMonth = 1;
							$newYear = $year + 1;
						}
						else $newMonth = 7;
						break;
					case 8:
					case 9:
					case 10:
					case 11:
					case 12:
						$newMonth = 1;
						$newYear = $year + 1;
						break;
				}
				
				while ($count <= $cirData['cirRepeatCount']) {
					// create dates and check
					$valid = checkdate($newMonth, 1, $newYear);
					if ($valid) {
						$temp = new \DateTime();
						$temp->setTimezone($timezone);
						$temp->setDate($newYear, $newMonth, 1);
						$temp->setTime($hour, $minute);
						
						if ($temp->getTimestamp() > $date->getTimestamp()) {
							$execs[] = $temp->getTimestamp();
							$count ++;
						}
					}
					
					// step through months / years
					switch ($newMonth) {
						case 1:
							$newMonth = 7;
							break;
						case 7:
							$newMonth = 1;
							$newYear ++;
							break;
					}
				}
				break;
				
			case 'yearlyDoM':
				// take first as defined by startTime, others matching day
				$execs[] = $date->getTimestamp();
				$count ++;
				
				$year = $date->format('Y');
				
				while($count <= $cirData['cirRepeatCount']) {
					$temp = new \DateTime();
					$temp->setTimezone($timezone);
					$temp->setDate($year, $cirData['cirYearlyDoM_month'], $cirData['cirYearlyDoM_day']);
					$temp->setTime($hour, $minute);
						
					if ($temp->getTimestamp() > $date->getTimestamp()) {
						$execs[] = $temp->getTimestamp();
						$count ++;
					}
					
					$year ++;
				}
				break;
				
			case 'yearlyDoW':
				// take first as defined by startTime, others matching day
				$execs[] = $date->getTimestamp();
				$count ++;
				
				// actual date compoments
				$month = $date->format('n');
				$year = $date->format('Y');
				$interval = new \DateInterval('P1D');
				
				while($count <= $cirData['cirRepeatCount']) {
					// start on first of month, step through days
					$temp = new \DateTime();
					$temp->setTimezone($timezone);
					$temp->setDate($year, $cirData['cirYearlyDoW_month'], 1);
					$temp->setTime($hour, $minute);
					$xth = $match = 0;
					
					while(1) {
						// match day
						if ($temp->format('w') == $cirData['cirYearlyDoW_day']) {
							$xth ++;
							$match = $temp->format('j');
							if ($xth == $cirData['cirYearlyDoW_index']) break;
						}
						// add day until next month, reset to actual match
						$temp->add($interval);
						
						if ($temp->format('j') == 1) {
							$temp->setDate($year, $month, $match);
							break;
						}
					}
					
					// store if times fit
					if ($temp->getTimestamp() > $date->getTimestamp()) {
						$execs[] = $temp->getTimestamp();
						$count ++;
					}
					
					// step through years
					$year ++;
				}
				break;
		}
		
		return $execs;
	}
}
