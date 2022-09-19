<?php
namespace wcf\system\condition;
use wcf\data\condition\Condition;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\DatabaseObjectList;
use wcf\system\exception\UserInputException;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Condition implementation for the languages of a user.
 * 
 * modified by Udo Zaydowicz for com.uz.wcf.bot3 - UzbotReceiverLanguageCondition
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Condition
 */
class UzbotReceiverLanguageCondition extends AbstractSingleFieldCondition implements IContentCondition, IObjectListCondition, IUserCondition {
	use TObjectListUserCondition;
	
	/**
	 * @inheritDoc
	 */
	protected $label = 'wcf.user.condition.languages';
	
	/**
	 * ids of the selected languages
	 * @var	integer[]
	 */
	protected $receiverLanguageIDs = [];
	
	/**
	 * @inheritDoc
	 */
	public function addObjectListCondition(DatabaseObjectList $objectList, array $conditionData) {
		if (!($objectList instanceof UserList)) {
			throw new \InvalidArgumentException("Object list is no instance of '".UserList::class."', instance of '".get_class($objectList)."' given.");
		}
		
		$objectList->getConditionBuilder()->add('user_table.languageID IN (?)', [$conditionData['receiverLanguageIDs']]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkUser(Condition $condition, User $user) {
		if (!empty($condition->conditionData['receiverLanguageIDs']) && !in_array($user->languageID, $condition->receiverLanguageIDs)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getData() {
		if (!empty($this->receiverLanguageIDs)) {
			return [
				'receiverLanguageIDs' => $this->receiverLanguageIDs
			];
		}
		
		return null;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getFieldElement() {
		$returnValue = "";
		foreach (LanguageFactory::getInstance()->getLanguages() as $language) {
			$returnValue .= "<label><input type=\"checkbox\" name=\"receiverLanguageIDs[]\" value=\"".$language->languageID."\"".(in_array($language->languageID, $this->receiverLanguageIDs) ? ' checked' : "")."> ".$language->languageName."</label>";
		}
		
		return $returnValue;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		if (isset($_POST['receiverLanguageIDs']) && is_array($_POST['receiverLanguageIDs'])) $this->receiverLanguageIDs = ArrayUtil::toIntegerArray($_POST['receiverLanguageIDs']);
	}
	
	/**
	 * @inheritDoc
	 */
	public function reset() {
		$this->receiverLanguageIDs = [];
	}
	
	/**
	 * @inheritDoc
	 */
	public function setData(Condition $condition) {
		if (!empty($condition->conditionData['receiverLanguageIDs'])) {
			$this->receiverLanguageIDs = $condition->conditionData['receiverLanguageIDs'];
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		foreach ($this->receiverLanguageIDs as $languageID) {
			if (LanguageFactory::getInstance()->getLanguage($languageID) === null) {
				$this->errorMessage = 'wcf.global.form.error.noValidSelection';
				
				throw new UserInputException('receiverLanguageIDs', 'noValidSelection');
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function showContent(Condition $condition) {
		if (WCF::getUser()->userID) {
			return $this->checkUser($condition, WCF::getUser());
		}
		
		if (!empty($condition->conditionData['receiverLanguageIDs']) && !in_array(WCF::getLanguage()->languageID, $condition->receiverLanguageIDs)) {
			return false;
		}
		
		return true;
	}
}
