<?php
namespace wcf\acp\form;
use wcf\form\AbstractForm;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\XML;

/**
 * Shows the Bot user import/export form.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotImportUserForm extends AbstractForm {
	/**
	 * @inheritDoc
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.uzbot.import.user';
	
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = ['admin.uzbot.canManageUzbot'];
	
	/**
	 * upload file data
	 */
	public $uzbotImportUser = null;
	
	/**
	 * list of users
	 */
	public $users = [];
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		parent::readFormParameters();
		
		if (isset($_FILES['uzbotImportUser'])) $this->uzbotImportUser = $_FILES['uzbotImportUser'];
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		parent::validate();
		
		// upload
		if ($this->uzbotImportUser && $this->uzbotImportUser['error'] != 4) {
			if ($this->uzbotImportUser['error'] != 0) {
				throw new UserInputException('uzbotImportUser', 'uploadFailed');
			}
			
			try {
				$xml = new XML();
				$xml->load($this->uzbotImportUser['tmp_name']);
				$xpath = $xml->xpath();
				
				foreach ($xpath->query('/users/user') as $user) {
					$data = array();
					
					try {
						$data['userID'] = $xpath->query('userID', $user)->item(0)->nodeValue;
						$data['uzbotReminded'] = $xpath->query('reminded', $user)->item(0)->nodeValue;
						$data['uzbotReminders'] = $xpath->query('reminders', $user)->item(0)->nodeValue;
						$data['uzbotDisabled'] = $xpath->query('disabled', $user)->item(0)->nodeValue;
						$data['uzbotBanned'] = $xpath->query('banned', $user)->item(0)->nodeValue;
					}
					catch (SystemException $e) {
						break;
					}
					$this->users[] = $data;
				}
			}
			catch (SystemException $e) {
				@unlink($this->uzbotImportUser['tmp_name']);
				throw new UserInputException('uzbotImportUser', 'importFailed');
			}
		}
		else {
			throw new UserInputException('uzbotImportUser');
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function save() {
		parent::save();
		
		$sql = "UPDATE	wcf".WCF_N."_user
				SET		uzbotReminded = ?, uzbotReminders = ?, uzbotDisabled = ?, uzbotBanned = ?
				WHERE	userID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		
		foreach ($this->users as $user) {
			$statement->execute([$user['uzbotReminded'], $user['uzbotReminders'], $user['uzbotDisabled'], $user['uzbotBanned'], $user['userID']]);
		}
		
		// delete import file
		@unlink($this->uzbotImportUser['tmp_name']);
		
		$this->saved();
		
		// show success message
		WCF::getTPL()->assign('success', true);
	}
}
