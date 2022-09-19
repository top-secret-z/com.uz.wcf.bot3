<?php
namespace wcf\data\uzbot\type;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of Bot types
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTypeList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = UzbotType::class;
	
	/**
	 * sql order by statement
	 */
	public $sqlOrderBy = 'sortOrder ASC';
}
