<?php
namespace wcf\data\uzbot\stats;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Executes Bot Stats related actions.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotStatsAction extends AbstractDatabaseObjectAction{
	/**
	 * @inheritDoc
	 */
	protected $className = UzbotStatsEditor::class;
}
