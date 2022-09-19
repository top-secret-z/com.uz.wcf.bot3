<?php
namespace wcf\acp\page;

/**
 * Shows the Community Bot category list page.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotCategoryListPage extends AbstractCategoryListPage {
	/**
	 * @inheritDoc
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.uzbot.category.list';
	
	/**
	 * @inheritDoc
	 */
	public $objectTypeName = 'com.uz.wcf.bot.category';
	
	/**
	 * @inheritDoc
	 */
	public $neededModules = ['MODULE_UZBOT'];
}
