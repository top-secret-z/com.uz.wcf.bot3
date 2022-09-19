<?php
namespace wcf\system\cache\builder;
use wcf\data\uzbot\UzbotList;

/**
 * Caches the active Bots.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotValidBotCacheBuilder extends AbstractCacheBuilder {
	/**
	 * @inheritDoc
	 */
	protected $maxLifetime = 600;
	
	/**
	 * @inheritDoc
	 */
	public function rebuild(array $parameters) {
		$bots = new UzbotList();
		if (isset($parameters['typeDes']) && !empty($parameters['typeDes'])) {
			$bots->getConditionBuilder()->add('typeDes = ?', [$parameters['typeDes']]);
		}
		$bots->getConditionBuilder()->add('isDisabled = ?', [0]);
		$bots->readObjects();
		
		return $bots->getObjects();
	}
}
