<?php
namespace wcf\system\condition;
use wcf\data\condition\Condition;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\DatabaseObjectList;
use wcf\system\WCF;

/**
 * Condition implementation for a relative interval for the ban time.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserBannedDateIntervalCondition extends AbstractIntegerCondition implements IContentCondition, IObjectListCondition, IUserCondition {
	use TObjectListUserCondition;
	
	/**
	 * @inheritDoc
	 */
	protected $label = 'wcf.acp.uzbot.condition.bannedDateInterval';
	
	/**
	 * @inheritDoc
	 */
	protected $minValue = 0;
	
	/**
	 * @inheritDoc
	 */
	public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData) {
		if (!($objectList instanceof UserList)) {
			throw new \InvalidArgumentException("Object list is no instance of '".UserList::class."', instance of '".get_class($objectList)."' given.");
		}
		
		if (isset($conditionData['greaterThan'])) {
			$objectList->getConditionBuilder()->add('user_table.uzbotBanned < ?', [TIME_NOW - $conditionData['greaterThan'] * 86400]);
		}
		if (isset($conditionData['lessThan'])) {
			$objectList->getConditionBuilder()->add('user_table.uzbotBanned > ?', [TIME_NOW - $conditionData['lessThan'] * 86400]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkUser(Condition $condition, User $user) {
		$greaterThan = $condition->greaterThan;
		if ($greaterThan !== null && $user->uzbotBanned >= TIME_NOW - $greaterThan * 86400) {
			return false;
		}
		
		$lessThan = $condition->lessThan;
		if ($lessThan !== null && $user->uzbotBanned <= TIME_NOW - $lessThan * 86400) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getIdentifier() {
		return 'uzbot_bannedDateInterval';
	}
	
	/**
	 * @inheritDoc
	 */
	public function showContent(Condition $condition) {
		if (!WCF::getUser()->userID) return false;
		
		return $this->checkUser($condition, WCF::getUser());
	}
}
