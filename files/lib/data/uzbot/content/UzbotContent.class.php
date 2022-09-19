<?php
namespace wcf\data\uzbot\content;
use wcf\data\uzbot\Uzbot;
use wcf\data\DatabaseObject;
use wcf\data\language\Language;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Represents a Bot content.
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotContent extends DatabaseObject {
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableName = 'uzbot_content';
	
	/**
	 * @inheritDoc
	 */
	protected static $databaseTableIndexName = 'contentID';
	
	/**
	 * uzbot object
	 */
	protected $uzbot;
	
	/**
	 * Returns the uzbot's formatted content.
	 */
	public function getFormattedContent() {
		$processor = new HtmlOutputProcessor();
		$processor->process($this->content, 'com.uz.wcf.bot.content', $this->contentID);
		
		return $processor->getHtml();
	}
	
	/**
	 * Returns the language of this bot content or `null` if no language has been specified.
	 */
	public function getLanguage() {
		if ($this->languageID) {
			return LanguageFactory::getInstance()->getLanguage($this->languageID);
		}
		
		return null;
	}
	
	/**
	 * Returns a certain bot content or `null` if it does not exist.
	 */
	public static function getBotContent($botID, $languageID) {
		if ($languageID !== null) {
			$sql = "SELECT	*
					FROM	wcf".WCF_N."_uzbot_content
					WHERE	botID = ? AND languageID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([$botID, $languageID]);
		}
		else {
			$sql = "SELECT	*
					FROM	wcf".WCF_N."_uzbot_content
					WHERE	botID = ? AND languageID IS NULL";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([$botID]);
		}
		
		if (($row = $statement->fetchSingleRow()) !== false) {
			return new UzbotContent(null, $row);
		}
		
		return null;
	}
}
