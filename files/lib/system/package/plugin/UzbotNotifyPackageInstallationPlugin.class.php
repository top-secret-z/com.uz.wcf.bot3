<?php
namespace wcf\system\package\plugin;
use wcf\data\uzbot\notification\UzbotNotifyEditor;
use wcf\system\WCF;

/**
 * Installs, updates and deletes additional Bot notifications.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyPackageInstallationPlugin extends AbstractXMLPackageInstallationPlugin {
	/**
	 * @inheritDoc
	 */
	public $className = UzbotNotifyEditor::class;
	
	/**
	 * @inheritDoc
	 */
	public $tableName = 'uzbot_notify';
	
	/**
	 * @inheritDoc
	 */
	public $tagName = 'uzbotNotify';
	
	/**
	 * @inheritDoc
	 */
	protected function handleDelete(array $items) {
		$sql = "DELETE FROM	wcf".WCF_N."_".$this->tableName."
				WHERE		notifyTitle = ?
							AND packageID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		foreach ($items as $item) {
			$statement->execute([
					$item['attributes']['name'],
					$this->installation->getPackageID()
			]);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	protected function prepareImport(array $data) {
		return [
				'notifyTitle' => $data['attributes']['name'],
				'notifyID' => $data['elements']['notifyID'],
				'hasContent' => $data['elements']['hasContent'],
				'hasLabels' => $data['elements']['hasLabels'],
				'hasReceiver' => $data['elements']['hasReceiver'],
				'hasSender' => $data['elements']['hasSender'],
				'hasSubject' => $data['elements']['hasSubject'],
				'hasTags' => $data['elements']['hasTags'],
				'hasTeaser' => $data['elements']['hasTeaser'],
				'neededModule' => $data['elements']['neededModule'],
				'notifyFunction' => $data['elements']['notifyFunction'],
				'sortOrder' => $data['elements']['sortOrder']
		];
	}
	
	/**
	 * @inheritDoc
	 */
	protected function findExistingItem(array $data) {
		$sql = "SELECT	*
				FROM	wcf".WCF_N."_".$this->tableName."
				WHERE	notifyTitle = ?
						AND packageID = ?";
		$parameters = [
				$data['notifyTitle'],
				$this->installation->getPackageID()
		];
		
		return [
				'sql' => $sql,
				'parameters' => $parameters
		];
	}
}
