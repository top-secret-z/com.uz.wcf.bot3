<?php
namespace wcf\system\category;
use wcf\system\category\AbstractCategoryType;
use wcf\system\WCF;

/**
 * Category type for Community Bot.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotCategoryType extends AbstractCategoryType {
	/**
	 * @inheritDoc
	 */
	protected $forceDescription = false;
	
	/**
	 * @inheritDoc
	 */
	protected $langVarPrefix = 'uzbot.category';
	
	/**
	 * @inheritDoc
	 */
	protected $maximumNestingLevel = 0;
	
	/**
	 * @inheritDoc
	 */
	public function canAddCategory() {
		return $this->canEditCategory();
	}
	
	/**
	 * @inheritDoc
	 */
	public function canDeleteCategory() {
		return $this->canEditCategory();
	}
	
	/**
	 * @inheritDoc
	 */
	public function canEditCategory() {
		return WCF::getSession()->getPermission('admin.uzbot.canManageUzbot');
	}
}
