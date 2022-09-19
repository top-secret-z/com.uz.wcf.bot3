<?php
namespace wcf\data\uzbot\content;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of Bot contents.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotContentList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	public $className = UzbotContent::class;
}
