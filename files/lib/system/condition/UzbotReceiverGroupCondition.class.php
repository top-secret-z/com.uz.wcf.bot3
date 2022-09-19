<?php
namespace wcf\system\condition;
use wcf\data\condition\Condition;
use wcf\data\user\group\UserGroup;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\DatabaseObjectList;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Condition implementation for all of the user groups a user has to be a member
 * of and the user groups a user may not be a member of.
 * 
 *  * modified by Udo Zaydowicz for com.uz.wcf.bot3 - UzbotReceiverGroupCondition
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Condition
 */
class UzbotReceiverGroupCondition extends AbstractMultipleFieldsCondition implements IContentCondition, IObjectListCondition, IUserCondition {
	use TObjectListUserCondition;
	
	/**
	 * @inheritDoc
	 */
	protected $descriptions = [
		'receiverBotGroupIDs' => 'wcf.user.condition.groupIDs.description',
		'notReceiverBotGroupIDs' => 'wcf.user.condition.notGroupIDs.description'
	];
	
	/**
	 * ids of the selected user receiverGroups the user has to be member of
	 */
	protected $receiverBotGroupIDs = [];
	
	/**
	 * @inheritDoc
	 */
	protected $labels = [
		'receiverBotGroupIDs' => 'wcf.user.condition.groupIDs',
		'notReceiverBotGroupIDs' => 'wcf.user.condition.notGroupIDs'
	];
	
	/**
	 * ids of the selected user receiverGroups the user may not be member of
	 */
	protected $notReceiverBotGroupIDs = [];
	
	/**
	 * selectable user groups
	 */
	protected $userGroups;
	
	/**
	 * @inheritDoc
	 */
	public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData) {
		if (!($objectList instanceof UserList)) {
			throw new \InvalidArgumentException("Object list is no instance of '".UserList::class."', instance of '".get_class($objectList)."' given.");
		}
		
		if (isset($conditionData['receiverBotGroupIDs'])) {
			$objectList->getConditionBuilder()->add('user_table.userID IN (SELECT userID FROM wcf'.WCF_N.'_user_to_group WHERE groupID IN (?) GROUP BY userID HAVING COUNT(userID) = ?)', [$conditionData['receiverBotGroupIDs'], count($conditionData['receiverBotGroupIDs'])]);
		}
		if (isset($conditionData['notReceiverBotGroupIDs'])) {
			$objectList->getConditionBuilder()->add('user_table.userID NOT IN (SELECT userID FROM wcf'.WCF_N.'_user_to_group WHERE groupID IN (?))', [$conditionData['notReceiverBotGroupIDs']]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkUser(Condition $condition, User $user) {
		$receiverBotGroupIDs = $user->getGroupIDs();
		if (!empty($condition->conditionData['receiverBotGroupIDs']) && count(array_diff($condition->conditionData['receiverBotGroupIDs'], $receiverBotGroupIDs))) {
			return false;
		}
		
		if (!empty($condition->conditionData['notReceiverBotGroupIDs']) && count(array_intersect($condition->conditionData['notReceiverBotGroupIDs'], $receiverBotGroupIDs))) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getData() {
		$data = [];
		
		if (!empty($this->receiverBotGroupIDs)) {
			$data['receiverBotGroupIDs'] = $this->receiverBotGroupIDs;
		}
		if (!empty($this->notReceiverBotGroupIDs)) {
			$data['notReceiverBotGroupIDs'] = $this->notReceiverBotGroupIDs;
		}
		
		if (!empty($data)) {
			return $data;
		}
		
		return null;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getHTML() {
		return <<<HTML
<dl{$this->getErrorClass('receiverBotGroupIDs')}>
	<dt>{$this->getLabel('receiverBotGroupIDs')}</dt>
	<dd>
		{$this->getOptionElements('receiverBotGroupIDs')}
		{$this->getDescriptionElement('receiverBotGroupIDs')}
		{$this->getErrorMessageElement('receiverBotGroupIDs')}
	</dd>
</dl>
<dl{$this->getErrorClass('notReceiverBotGroupIDs')}>
	<dt>{$this->getLabel('notReceiverBotGroupIDs')}</dt>
	<dd>
		{$this->getOptionElements('notReceiverBotGroupIDs')}
		{$this->getDescriptionElement('notReceiverBotGroupIDs')}
		{$this->getErrorMessageElement('notReceiverBotGroupIDs')}
	</dd>
</dl>
HTML;
	}
	
	/**
	 * Returns the option elements for the user group selection.
	 * 
	 * @param	string		$identifier
	 * @return	string
	 */
	protected function getOptionElements($identifier) {
		$userGroups = $this->getUserGroups();
		
		$returnValue = "";
		foreach ($userGroups as $userGroup) {
			/** @noinspection PhpVariableVariableInspection */
			$returnValue .= "<label><input type=\"checkbox\" name=\"".$identifier."[]\" value=\"".$userGroup->groupID."\"".(in_array($userGroup->groupID, $this->$identifier) ? ' checked' : "")."> ".$userGroup->getName()."</label>";
		}
		
		return $returnValue;
	}
	
	/**
	 * Returns the selectable user groups.
	 * 
	 * @return	UserGroup[]
	 */
	protected function getUserGroups() {
		if ($this->userGroups == null) {
			$invalidGroupTypes = [
				UserGroup::EVERYONE,
				UserGroup::USERS
			];
			if (!$this->includeguests) {
				$invalidGroupTypes[] = UserGroup::GUESTS;
			}
			
			$this->userGroups = UserGroup::getAccessibleGroups([], $invalidGroupTypes);
			
			uasort($this->userGroups, function(UserGroup $groupA, UserGroup $groupB) {
				return strcmp($groupA->getName(), $groupB->getName());
			});
		}
		
		return $this->userGroups;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		if (isset($_POST['receiverBotGroupIDs'])) $this->receiverBotGroupIDs = ArrayUtil::toIntegerArray($_POST['receiverBotGroupIDs']);
		if (isset($_POST['notReceiverBotGroupIDs'])) $this->notReceiverBotGroupIDs = ArrayUtil::toIntegerArray($_POST['notReceiverBotGroupIDs']);
	}
	
	/**
	 * @inheritDoc
	 */
	public function reset() {
		$this->receiverBotGroupIDs = [];
		$this->notReceiverBotGroupIDs = [];
	}
	
	/**
	 * @inheritDoc
	 */
	public function setData(Condition $condition) {
		if ($condition->receiverBotGroupIDs !== null) {
			$this->receiverBotGroupIDs = $condition->receiverBotGroupIDs;
		}
		if ($condition->notReceiverBotGroupIDs !== null) {
			$this->notReceiverBotGroupIDs = $condition->notReceiverBotGroupIDs;
		}
	}
	
	/**
	 * Sets the selectable user groups.
	 * 
	 * @param	UserGroup[]	$userGroups
	 */
	public function setUserGroups(array $userGroups) {
		$this->userGroups = $userGroups;
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		$userGroups = $this->getUserGroups();
		foreach ($this->receiverBotGroupIDs as $groupID) {
			if (!isset($userGroups[$groupID])) {
				$this->errorMessages['receiverBotGroupIDs'] = 'wcf.global.form.error.noValidSelection';
				
				throw new UserInputException('receiverBotGroupIDs', 'noValidSelection');
			}
		}
		foreach ($this->notReceiverBotGroupIDs as $groupID) {
			if (!isset($userGroups[$groupID])) {
				$this->errorMessages['notReceiverBotGroupIDs'] = 'wcf.global.form.error.noValidSelection';
				
				throw new UserInputException('notReceiverBotGroupIDs', 'noValidSelection');
			}
		}
		
		if (count(array_intersect($this->notReceiverBotGroupIDs, $this->receiverBotGroupIDs))) {
			$this->errorMessages['notReceiverBotGroupIDs'] = 'wcf.user.condition.notGroupIDs.error.groupIDsIntersection';
			
			throw new UserInputException('notReceiverBotGroupIDs', 'groupIDsIntersection');
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function showContent(Condition $condition) {
		return $this->checkUser($condition, WCF::getUser());
	}
}
