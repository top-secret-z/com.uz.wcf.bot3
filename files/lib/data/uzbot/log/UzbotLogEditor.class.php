<?php
namespace wcf\data\uzbot\log;
use wcf\data\DatabaseObjectEditor;
use wcf\system\language\LanguageFactory;

/**
 * Provides functions to edit Bot Log entries.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotLogEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = UzbotLog::class;
	
	/**
	 * @inheritDoc
	 */
	public static function create(array $data = []) {
		// get default language
		$language = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		$bot = $data['bot'];
		
		// optional packages might still deliver implode data for test mode
		$additional = 'wcf.acp.uzbot.error.none';
		if (isset($data['additionalData'])) {
			$additional = $data['additionalData'];
			
			// 2 are used in old versions
			if (substr_count($additional, '(|)') >= 2) {
				$additional = serialize(explode('(|)', $additional));
			}
		}
		
		$parameters = [
				'time' => TIME_NOW,
				'botID' => $bot->botID,
				'botTitle' => $language->get($bot->botTitle),
				'typeID' => $bot->typeID,
				'typeDes' => $language->get($bot->typeDes),
				'notifyDes' => $language->get($bot->notifyDes),
				'status' => isset($data['status']) ? $data['status'] : 0,
				'testMode' => isset($data['testMode']) ? $data['testMode'] : 0,
				'count' => isset($data['count']) ? $data['count'] : 0,
				'additionalData' => $additional
		];
		
		parent::create($parameters);
	}
}
