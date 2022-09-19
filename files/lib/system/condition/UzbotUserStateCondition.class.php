<?php
namespace wcf\system\condition;
use wcf\data\condition\Condition;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\DatabaseObjectList;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Condition implementation for the state (banned, enabled, reminded by bot) of a user.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotUserStateCondition extends AbstractSingleFieldCondition implements IContentCondition, IObjectListCondition, IUserCondition {
	use TObjectListUserCondition;
	
	/**
	 * @inheritDoc
	 */
	protected $label = 'wcf.user.condition.state';
	
	/**
	 * @inheritDoc
	 */
	protected $description = 'wcf.acp.uzbot.condition.state.description';
	
	/**
	 * true if the the user has to be banned, disabled ...
	 */
	protected $userIsBotBanned = 0;
	protected $userIsNotBotBanned = 0;
	protected $userIsBotDisabled = 0;
	protected $userIsBotEnabled = 0;
	protected $userIsBotReminded = 0;
	protected $userIsNotBotReminded = 0;
	
	/**
	 * @inheritDoc
	 */
	public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData) {
		if (!($objectList instanceof UserList)) {
			throw new \InvalidArgumentException("Object list is no instance of '".UserList::class."', instance of '".get_class($objectList)."' given.");
		}
		
		if (isset($conditionData['userIsBotBanned'])) {
			if ($conditionData['userIsBotBanned']) {
				$objectList->getConditionBuilder()->add('user_table.uzbotBanned > ?', [0]);
			}
			else {
				$objectList->getConditionBuilder()->add('user_table.uzbotBanned = ?', [0]);
			}
		}
		
		if (isset($conditionData['userIsBotEnabled'])) {
			if ($conditionData['userIsBotEnabled']) {
				$objectList->getConditionBuilder()->add('user_table.uzBotDisabled = ?', [0]);
			}
			else {
				$objectList->getConditionBuilder()->add('user_table.uzBotDisabled > ?', [0]);
			}
		}
		
		if (isset($conditionData['userIsBotReminded'])) {
			if ($conditionData['userIsBotReminded']) {
				$objectList->getConditionBuilder()->add('user_table.uzbotReminded > ?', [0]);
			}
			else {
				$objectList->getConditionBuilder()->add('user_table.uzbotReminded = ?', [0]);
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkUser(Condition $condition, User $user) {
		$userIsBotBanned = $condition->userIsBotBanned;
		if ($userIsBotBanned !== null && $user->banned != $userIsBotBanned) {
			return false;
		}
		
		$userIsBotEnabled = $condition->userIsBotEnabled;
		if ($userIsBotEnabled !== null) {
			if ($userIsBotEnabled && $user->activationCode) {
				return false;
			}
			else if (!$userIsBotEnabled && !$user->activationCode) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getData() {
		$data = [];
		
		if ($this->userIsBotBanned) {
			$data['userIsBotBanned'] = 1;
		}
		else if ($this->userIsNotBotBanned) {
			$data['userIsBotBanned'] = 0;
		}
		
		if ($this->userIsBotEnabled) {
			$data['userIsBotEnabled'] = 1;
		}
		else if ($this->userIsBotDisabled) {
			$data['userIsBotEnabled'] = 0;
		}
		
		if ($this->userIsBotReminded) {
			$data['userIsBotReminded'] = 1;
		}
		else if ($this->userIsNotBotReminded) {
			$data['userIsBotReminded'] = 0;
		}
		
		if (!empty($data)) {
			return $data;
		}
		
		return null;
	}
	
	/**
	 * Returns the "checked" attribute for an input element.
	 */
	protected function getCheckedAttribute($propertyName) {
		if ($this->$propertyName) {
			return ' checked';
		}
		
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getFieldElement() {
		$userIsNotBotBanned = WCF::getLanguage()->get('wcf.user.condition.state.isNotBanned');
		$userIsBotBanned = WCF::getLanguage()->get('wcf.user.condition.state.isBanned');
		$userIsBotDisabled = WCF::getLanguage()->get('wcf.user.condition.state.isDisabled');
		$userIsBotEnabled = WCF::getLanguage()->get('wcf.user.condition.state.isEnabled');
		$userIsNotBotReminded = WCF::getLanguage()->get('wcf.user.condition.state.isNotReminded');
		$userIsBotReminded = WCF::getLanguage()->get('wcf.user.condition.state.isReminded');
		
		return <<<HTML
<label><input type="checkbox" name="userIsBotBanned" value="1"{$this->getCheckedAttribute('userIsBotBanned')}> {$userIsBotBanned}</label>
<label><input type="checkbox" name="userIsNotBotBanned" value="1"{$this->getCheckedAttribute('userIsNotBotBanned')}> {$userIsNotBotBanned}</label>
<label><input type="checkbox" name="userIsBotEnabled" value="1"{$this->getCheckedAttribute('userIsBotEnabled')}> {$userIsBotEnabled}</label>
<label><input type="checkbox" name="userIsBotDisabled" value="1"{$this->getCheckedAttribute('userIsBotDisabled')}> {$userIsBotDisabled}</label>
<label><input type="checkbox" name="userIsBotReminded" value="1"{$this->getCheckedAttribute('userIsBotReminded')}> {$userIsBotReminded}</label>
<label><input type="checkbox" name="userIsNotBotReminded" value="1"{$this->getCheckedAttribute('userIsNotBotReminded')}> {$userIsNotBotReminded}</label>
HTML;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		if (isset($_POST['userIsBotBanned'])) $this->userIsBotBanned = 1;
		if (isset($_POST['userIsNotBotBanned'])) $this->userIsNotBotBanned = 1;
		if (isset($_POST['userIsBotDisabled'])) $this->userIsBotDisabled = 1;
		if (isset($_POST['userIsBotEnabled'])) $this->userIsBotEnabled = 1;
		if (isset($_POST['userIsBotReminded'])) $this->userIsBotReminded = 1;
		if (isset($_POST['userIsNotBotReminded'])) $this->userIsNotBotReminded = 1;
	}
	
	/**
	 * @inheritDoc
	 */
	public function reset() {
		$this->userIsBotBanned = 0;
		$this->userIsNotBotBanned = 0;
		$this->userIsBotDisabled = 0;
		$this->userIsBotEnabled = 0;
		$this->userIsBotReminded = 0;
		$this->userIsNotBotReminded = 0;
	}
	
	/**
	 * @inheritDoc
	 */
	public function setData(Condition $condition) {
		$userIsBotBanned = $condition->userIsBotBanned;
		if ($condition->userIsBotBanned !== null) {
			$this->userIsBotBanned = $userIsBotBanned;
			$this->userIsNotBotBanned = !$userIsBotBanned;
		}
		
		$userIsBotEnabled = $condition->userIsBotEnabled;
		if ($condition->userIsBotEnabled !== null) {
			$this->userIsBotEnabled = $userIsBotEnabled;
			$this->userIsBotDisabled = !$userIsBotEnabled;
		}
		
		$userIsBotReminded = $condition->userIsBotReminded;
		if ($condition->userIsBotReminded !== null) {
			$this->userIsBotReminded = $userIsBotReminded;
			$this->userIsNotBotReminded = !$userIsBotReminded;
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		if ($this->userIsBotBanned && $this->userIsNotBotBanned) {
			$this->errorMessage = 'wcf.user.condition.state.isBanned.error.conflict';
			
			throw new UserInputException('userIsBotBanned', 'conflict');
		}
		
		if ($this->userIsBotDisabled && $this->userIsBotEnabled) {
			$this->errorMessage = 'wcf.user.condition.state.isEnabled.error.conflict';
			
			throw new UserInputException('userIsBotEnabled', 'conflict');
		}
		
		if ($this->userIsBotReminded && $this->userIsNotBotReminded) {
			$this->errorMessage = 'wcf.user.condition.state.isReminded.error.conflict';
				
			throw new UserInputException('userIsBotReminded', 'conflict');
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function showContent(Condition $condition) {
		if (!WCF::getUser()->userID) return false;
		
		return $this->checkUser($condition, WCF::getUser());
	}
}
