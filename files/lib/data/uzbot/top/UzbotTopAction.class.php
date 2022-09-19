<?php
namespace wcf\data\uzbot\top;
use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes Bot Top related actions.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTopAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDoc
	 */
	protected $className = UzbotTopEditor::class;
}
