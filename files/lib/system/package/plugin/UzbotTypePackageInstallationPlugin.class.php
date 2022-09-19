<?php
namespace wcf\system\package\plugin;
use wcf\data\uzbot\type\UzbotTypeEditor;
use wcf\system\WCF;

/**
 * Installs, updates and deletes additional Bot types.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotTypePackageInstallationPlugin extends AbstractXMLPackageInstallationPlugin {
	/**
	 * @inheritDoc
	 */
	public $className = UzbotTypeEditor::class;
	
	/**
	 * @inheritDoc
	 */
	public $tableName = 'uzbot_type';
	
	/**
	 * @inheritDoc
	 */
	public $tagName = 'uzbotType';
	
	/**
	 * @inheritDoc
	 */
	protected function handleDelete(array $items) {
		$sql = "DELETE FROM	wcf".WCF_N."_".$this->tableName."
				WHERE		typeTitle = ?
							AND packageID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		foreach ($items as $item) {
			$statement->execute([$item['attributes']['name'], $this->installation->getPackageID()
			]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	protected function prepareImport(array $data) {
		return [
				'typeTitle' => $data['attributes']['name'],
				'typeID' => $data['elements']['typeID'],
				'application' => $data['elements']['application'],
				'canCondense' => $data['elements']['canCondense'],
				'hasAffected' => $data['elements']['hasAffected'],
				'allowGuest' => $data['elements']['allowGuest'],
				'canChangeAffected' => $data['elements']['canChangeAffected'],
				'needCount' => $data['elements']['needCount'],
				'needCountAction' => $data['elements']['needCountAction'],
				'needCountNo' => $data['elements']['needCountNo'],
				'neededModule' => $data['elements']['neededModule'],
				'needNotify' => $data['elements']['needNotify'],
				'sortOrder' => $data['elements']['sortOrder']
		];
	}
	
	/**
	 * @inheritDoc
	 */
	protected function findExistingItem(array $data) {
		$sql = "SELECT	*
				FROM	wcf".WCF_N."_".$this->tableName."
				WHERE	typeTitle = ?
						AND packageID = ?";
		$parameters = [
				$data['typeTitle'],
				$this->installation->getPackageID()
		];
		
		return [
				'sql' => $sql,
				'parameters' => $parameters
		];
	}
}
