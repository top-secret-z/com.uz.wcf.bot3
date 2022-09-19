<?php
namespace wcf\acp\action;
use wcf\action\AbstractAction;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Exports users' inactivity fields.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotExportUserAction extends AbstractAction {
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = ['admin.uzbot.canManageUzbot'];
	
	/**
	 * @inheritDoc
	 */
	public function execute() {
		parent::execute();
		
		// get user data
		$users = [];
		$sql = "SELECT		userID, uzbotReminded, uzbotReminders, uzbotDisabled, uzbotBanned
				FROM		wcf".WCF_N."_user
				ORDER BY 	userID ASC";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			$users[] = [
					'userID' => $row['userID'],
					'uzbotReminded' => $row['uzbotReminded'],
					'uzbotReminders' => $row['uzbotReminders'],
					'uzbotDisabled' => $row['uzbotDisabled'],
					'uzbotBanned' => $row['uzbotBanned']
			];
		}
		
		// send content type
		header('Content-Type: text/xml; charset=UTF-8');
		header('Content-Disposition: attachment; filename="UzbotUserExport.xml"');
		
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<users>\n";
		
		foreach ($users as $user) {
			echo "\t<user>\n";
			echo "\t\t<userID><![CDATA[".StringUtil::escapeCDATA($user['userID'])."]]></userID>\n";
			echo "\t\t<reminded><![CDATA[".StringUtil::escapeCDATA($user['uzbotReminded'])."]]></reminded>\n";
			echo "\t\t<reminders><![CDATA[".StringUtil::escapeCDATA($user['uzbotReminders'])."]]></reminders>\n";
			echo "\t\t<disabled><![CDATA[".StringUtil::escapeCDATA($user['uzbotDisabled'])."]]></disabled>\n";
			echo "\t\t<banned><![CDATA[".StringUtil::escapeCDATA($user['uzbotBanned'])."]]></banned>\n";
			echo "\t</user>\n";
		}
		
		echo "</users>";
		
		$this->executed();
		exit;
	}
}
