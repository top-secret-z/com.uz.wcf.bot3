<?php
namespace wcf\data\uzbot\notification;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of Bot notifications
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = UzbotNotify::class;
	
	/**
	 * sql order by statement
	 */
	public $sqlOrderBy = 'sortOrder ASC';
}
