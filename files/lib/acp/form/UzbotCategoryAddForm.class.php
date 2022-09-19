<?php
namespace wcf\acp\form;

/**
 * Shows the Community Bot category add form.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotCategoryAddForm extends AbstractCategoryAddForm {
	/**
	 * @inheritDoc
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.uzbot.category.add';
	
	/**
	 * @inheritDoc
	 */
	public $objectTypeName = 'com.uz.wcf.bot.category';
	
	/**
	 * @inheritDoc
	 */
	public $neededModules = ['MODULE_UZBOT'];
}
