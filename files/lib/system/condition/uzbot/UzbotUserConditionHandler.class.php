<?php
namespace wcf\system\condition\uzbot;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\uzbot\Uzbot;
use wcf\system\SingletonFactory;

/**
 * Handles general user conditions.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserConditionHandler extends SingletonFactory {
	/**
	 * list of grouped user group / inactive assignment condition object types
	 */
	protected $groupedObjectTypes = [];

	/**
	 * Returns the list of grouped user group / inactive assignment condition object types.
	 */
	public function getGroupedObjectTypes() {
		return $this->groupedObjectTypes;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function init() {
		$objectTypes = ObjectTypeCache::getInstance()->getObjectTypes('com.uz.wcf.bot.condition.user');
		foreach ($objectTypes as $objectType) {
			if (!$objectType->conditiongroup) continue;
			
			if (!isset($this->groupedObjectTypes[$objectType->conditiongroup])) {
				$this->groupedObjectTypes[$objectType->conditiongroup] = [];
			}
			
			$this->groupedObjectTypes[$objectType->conditiongroup][$objectType->objectTypeID] = $objectType;
		}
	}
}
