<?php
namespace wcf\data\uzbot\stats;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit Bot Stats.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotStatsEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	public static $baseClass = UzbotStats::class;
}
