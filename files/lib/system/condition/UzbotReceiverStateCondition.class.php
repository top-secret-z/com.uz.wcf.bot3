<?php
namespace wcf\system\condition;
use wcf\data\condition\Condition;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\DatabaseObjectList;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Condition implementation for the state (banned, enabled) of a user.
 * 
 *  * modified by Udo Zaydowicz for com.uz.wcf.bot3 - UzbotReceiverStateCondition
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Condition
 */
class UzbotReceiverStateCondition extends AbstractSingleFieldCondition implements IContentCondition, IObjectListCondition, IUserCondition {
	use TObjectListUserCondition;
	
	/**
	 * @inheritDoc
	 */
	protected $label = 'wcf.user.condition.state';
	
	/**
	 * true if the the receiver has to be banned
	 * @var	integer
	 */
	protected $receiverIsBanned = 0;
	
	/**
	 * true if the receiver has to be disabled
	 * @var	integer
	 */
	protected $receiverIsDisabled = 0;
	
	/**
	 * true if the receiver has to be enabled
	 * @var	integer
	 */
	protected $receiverIsEnabled = 0;
	
	/**
	 * true if the the receiver may not be banned
	 * @var	integer
	 */
	protected $receiverIsNotBanned = 0;
	
	/**
	 * @inheritDoc
	 */
	public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData) {
		if (!($objectList instanceof UserList)) {
			throw new \InvalidArgumentException("Object list is no instance of '".UserList::class."', instance of '".get_class($objectList)."' given.");
		}
		
		if (isset($conditionData['receiverIsBanned'])) {
			$objectList->getConditionBuilder()->add('user_table.banned = ?', [$conditionData['receiverIsBanned']]);
		}
		
		if (isset($conditionData['receiverIsEnabled'])) {
			if ($conditionData['receiverIsEnabled']) {
				$objectList->getConditionBuilder()->add('user_table.activationCode = ?', [0]);
			}
			else {
				$objectList->getConditionBuilder()->add('user_table.activationCode <> ?', [0]);
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkUser(Condition $condition, User $user) {
		/** @noinspection PhpUndefinedFieldInspection */
		$receiverIsBanned = $condition->receiverIsBanned;
		if ($receiverIsBanned !== null && $receiver->banned != $receiverIsBanned) {
			return false;
		}
		
		/** @noinspection PhpUndefinedFieldInspection */
		$receiverIsEnabled = $condition->receiverIsEnabled;
		if ($receiverIsEnabled !== null) {
			if ($receiverIsEnabled && $receiver->activationCode) {
				return false;
			}
			else if (!$receiverIsEnabled && !$receiver->activationCode) {
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
		
		if ($this->receiverIsBanned) {
			$data['receiverIsBanned'] = 1;
		}
		else if ($this->receiverIsNotBanned) {
			$data['receiverIsBanned'] = 0;
		}
		if ($this->receiverIsEnabled) {
			$data['receiverIsEnabled'] = 1;
		}
		else if ($this->receiverIsDisabled) {
			$data['receiverIsEnabled'] = 0;
		}
		
		if (!empty($data)) {
			return $data;
		}
		
		return null;
	}
	
	/**
	 * Returns the "checked" attribute for an input element.
	 * 
	 * @param	string		$propertyName
	 * @return	string
	 */
	protected function getCheckedAttribute($propertyName) {
		/** @noinspection PhpVariableVariableInspection */
		if ($this->$propertyName) {
			return ' checked';
		}
		
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getFieldElement() {
		$receiverIsNotBanned = WCF::getLanguage()->get('wcf.user.condition.state.isNotBanned');
		$receiverIsBanned = WCF::getLanguage()->get('wcf.user.condition.state.isBanned');
		$receiverIsDisabled = WCF::getLanguage()->get('wcf.user.condition.state.isDisabled');
		$receiverIsEnabled = WCF::getLanguage()->get('wcf.user.condition.state.isEnabled');
		
		return <<<HTML
<label><input type="checkbox" name="receiverIsBanned" value="1"{$this->getCheckedAttribute('receiverIsBanned')}> {$receiverIsBanned}</label>
<label><input type="checkbox" name="receiverIsNotBanned" value="1"{$this->getCheckedAttribute('receiverIsNotBanned')}> {$receiverIsNotBanned}</label>
<label><input type="checkbox" name="receiverIsEnabled" value="1"{$this->getCheckedAttribute('receiverIsEnabled')}> {$receiverIsEnabled}</label>
<label><input type="checkbox" name="receiverIsDisabled" value="1"{$this->getCheckedAttribute('receiverIsDisabled')}> {$receiverIsDisabled}</label>
HTML;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		if (isset($_POST['receiverIsBanned'])) $this->receiverIsBanned = 1;
		if (isset($_POST['receiverIsDisabled'])) $this->receiverIsDisabled = 1;
		if (isset($_POST['receiverIsEnabled'])) $this->receiverIsEnabled = 1;
		if (isset($_POST['receiverIsNotBanned'])) $this->receiverIsNotBanned = 1;
	}
	
	/**
	 * @inheritDoc
	 */
	public function reset() {
		$this->receiverIsBanned = 0;
		$this->receiverIsDisabled = 0;
		$this->receiverIsEnabled = 0;
		$this->receiverIsNotBanned = 0;
	}
	
	/**
	 * @inheritDoc
	 */
	public function setData(Condition $condition) {
		/** @noinspection PhpUndefinedFieldInspection */
		$receiverIsBanned = $condition->receiverIsBanned;
		if ($condition->receiverIsBanned !== null) {
			$this->receiverIsBanned = $receiverIsBanned;
			$this->receiverIsNotBanned = !$receiverIsBanned;
		}
		
		/** @noinspection PhpUndefinedFieldInspection */
		$receiverIsEnabled = $condition->receiverIsEnabled;
		if ($condition->receiverIsEnabled !== null) {
			$this->receiverIsEnabled = $receiverIsEnabled;
			$this->receiverIsDisabled = !$receiverIsEnabled;
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		if ($this->receiverIsBanned && $this->receiverIsNotBanned) {
			$this->errorMessage = 'wcf.user.condition.state.isBanned.error.conflict';
			
			throw new UserInputException('receiverIsBanned', 'conflict');
		}
		
		if ($this->receiverIsDisabled && $this->receiverIsEnabled) {
			$this->errorMessage = 'wcf.user.condition.state.isEnabled.error.conflict';
			
			throw new UserInputException('receiverIsEnabled', 'conflict');
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
