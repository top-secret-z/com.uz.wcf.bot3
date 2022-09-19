<?php
namespace wcf\data\uzbot\log;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Executes Bot Log related actions.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotLogAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDoc
	 */
	protected $className = UzbotLogEditor::class;
	
	/**
	 * @inheritDoc
	 */
	protected $permissionsDelete = ['admin.uzbot.canManageUzbot'];
	protected $permissionsUpdate = ['admin.uzbot.canManageUzbot'];
	
	/**
	 * @inheritDoc
	 */
	protected $requireACP = ['delete', 'update'];
	
	/**
	 * Validates the clearAll action.
	 */
	public function validateClearAll() {
		// do nothing
	}
	
	/**
	 * Executes the deleteAll action.
	 */
	public function clearAll() {
		$sql = "DELETE FROM	wcf".WCF_N."_uzbot_log";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
	}
}
