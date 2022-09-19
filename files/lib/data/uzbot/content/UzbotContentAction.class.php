<?php
namespace wcf\data\uzbot\content;
use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes Bot content related actions.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotContentAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDoc
	 */
	protected $className = UzbotContentEditor::class;
}
