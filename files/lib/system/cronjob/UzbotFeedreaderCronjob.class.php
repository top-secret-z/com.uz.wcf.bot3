<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\uzbot\UzbotEditor;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\exception\SystemException;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\HTTPRequest;
use wcf\util\XML;

/**
 * Feedreader cronjob for Bot (every 15 minutes)
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotFeedreaderCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// Read all active, valid activity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_feedreader']);
		if (!count($bots)) return;
		
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// Step through all bots and get feed items
		foreach ($bots as $bot) {
			// check for last retrieval and set if not test mode
			if (!$bot->testMode) {
				if ($bot->feedreaderLast + $bot->feedreaderFrequency > TIME_NOW) continue;
				
				$editor = new UzbotEditor($bot);
				$editor->update(['feedreaderLast' => TIME_NOW]);
			}
			
			// check for and prepare notification
			if (!$bot->notifyID) continue;
			$notify = $bot->checkNotify(true, true);
			if ($notify === null) continue;
			
			// try to connect to feed and get type
			try {
				$request = new HTTPRequest($bot->feedreaderUrl);
				$request->execute();
				$result = $request->getReply();
				$content = $result['body'];
				
				// try to xml-parse content and get type
				$xml = new XML();
				$xml->loadXML($bot->feedreaderUrl, $content);
				$xpath = $xml->xpath();
				$type = $xpath->query('/*')->item(0);
			}
			catch (SystemException $e) {
				if ($bot->enableLog) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'status' => 1,
							'additionalData' => 'wcf.acp.uzbot.error.feedUrl'
					]);
				}
				continue;
			}
			
			if ($type !== null) $type = $type->nodeName;
			if ($type === null || ($type != 'feed' && $type != 'rss')) {
				if ($bot->enableLog) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'status' => 1,
							'additionalData' => 'wcf.acp.uzbot.error.feedType'
					]);
				}
				continue;
			}
			
			// get filters
			$positiveFilterWords = [];
			$negativeFilterWords = [];
			if ($bot->feedreaderInclude) $positiveFilterWords = array_unique(ArrayUtil::trim(explode(',', mb_strtolower($bot->feedreaderInclude))));
			if ($bot->feedreaderExclude) $negativeFilterWords = array_unique(ArrayUtil::trim(explode(',', mb_strtolower($bot->feedreaderExclude))));
			
			// get hash data, change from in_array to isset
			$hashData = [];
			$sql = "SELECT	hash
					FROM	wcf".WCF_N."_uzbot_feedreader_hash
					WHERE	botID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute([$bot->botID]);
			while ($row = $statement->fetchArray()) {
				$hashData[$row['hash']] = 0;
			}
			
			// set feeditemData
			$feeditemData = [];
			$hashes = [];
			
			// RSS
			if ($type == 'rss') {
				// get general feed data
				$feedData = [];
				$channel = $xpath->query('//channel')->item(0);
				
				// channel data
				// title, (link,) description must exist
				$feedData['feed-title'] = $feedData['feed-link'] = $feedData['feed-description'] = ''; 
				if ($channel->getElementsByTagName('title')->length != 0) $feedData['feed-title'] = $channel->getElementsByTagName('title')->item(0)->nodeValue;
				if ($channel->getElementsByTagName('link')->length != 0) $feedData['feed-link'] = $channel->getElementsByTagName('link')->item(0)->nodeValue;
				if ($channel->getElementsByTagName('description')->length != 0) $feedData['feed-description'] = $channel->getElementsByTagName('description')->item(0)->nodeValue;
				
				// accept missing link
				if (empty($feedData['feed-description']) || empty($feedData['feed-title'])) {
					if ($bot->enableLog) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'status' => 1,
								'additionalData' => 'wcf.acp.uzbot.error.feedIllegal.rss'
						]);
					}
				}
				
				// get items; title or description must exist
				$items = $xpath->query('//channel/item');
				$count = 0;
				foreach ($items as $item) {
					// check for max items
					if ($bot->feedreaderMaxItems && $count >= $bot->feedreaderMaxItems) break;
					$count ++;
					
					// preset
					$data = [];
					$nodes = $xpath->query('child::*', $item);
					
					// read all nodes
					foreach ($nodes as $node) {
						// read categories
						if ($node->nodeName == 'category') {
							if (!isset($data['categories'])) {
								$data['categories'] = [];
							}
							$data['categories'][] = $node->nodeValue;
						}
						else {
							$data[$node->nodeName] = $node->nodeValue;
						}
					}
					
					// check for time
					$time = 0;
					if (isset($data['pubDate'])) {
						$time = strtotime($data['pubDate']);
						if ($bot->feedreaderMaxAge) {
							if ($time < TIME_NOW - $bot->feedreaderMaxAge * 86400) continue;
						}
					}
					else $data['pubDate'] = '';
					
					// must have some content, at least title or description; others may be set to title / description;
					if (empty($data['title']) && empty($data['description'])) {
						if ($bot->enableLog) {
							UzbotLogEditor::create([
									'bot' => $bot,
									'status' => 1,
									'additionalData' => 'wcf.acp.uzbot.error.feedItemIllegal.rss'
							]);
						}
						continue;
					}
					
					if (empty($data['title'])) $data['title'] = $data['description'];
					if (empty($data['description'])) $data['description'] = $data['title'];
					if (empty($data['content:encoded'])) $data['content:encoded'] = $data['description'];
					
					// check for hash . Use guid, fallback to title + description
					// due to correction both guid and title/description must be checked to avoid reading old items again after update :-(
					if (!empty($data['guid'])) {
						$hash = sha1($data['guid']);
						$hash2 = sha1($data['title'].$data['description']);
						if (isset($hashData[$hash2]) || isset($hashData[$hash])) continue;
					}
					else {
						$hash = sha1($data['title'].$data['description']);
						if (isset($hashData[$hash])) continue;
					}
					
					// check for search words in title, description and content
					$text = mb_strtolower($data['title'] . $data['description'] . $data['content:encoded']);
					
					if (!empty($positiveFilterWords)) {
						$found = false;
						foreach ($positiveFilterWords as $word) {
							if (mb_strpos($text, $word) !== false) {
								$found = true;
								break;
							}
						}
						if (!$found) continue;
					}
					if (!empty($negativeFilterWords)) {
						$found = false;
						foreach ($negativeFilterWords as $word) {
							if (mb_strpos($text, $word) !== false) {
								$found = true;
								break;
							}
						}
						if ($found) continue;
					}
					
					// finally there is an item ;-)
					$feeditemData[] = [
							'feed-description' => $feedData['feed-description'],
							'feed-link' => !empty($feedData['feed-link']) ? $feedData['feed-link'] : '',
							'feed-title' => $feedData['feed-title'],
							'feeditem-content' => $data['content:encoded'],
							'feeditem-description' => $data['description'],
							'feeditem-link' => (!empty($data['link']) ? $data['link'] : ''),
							'feeditem-title' => $data['title'],
							'feeditem-time' => $data['pubDate'],
							'feeditem-systemtime' => $time,
							'feeditem-systemdate' => $time,
							'feeditem-categories' => !empty($data['categories']) ? $data['categories'] : [],
							'count' => 1
					];
					
					// update hashes
					$hashes[] = $hash;
					$hashData[$hash] = 0;
				}
			}
			
			// Atom
			if ($type == 'feed') {
				// get general feed data
				$feedData = [];
				$feed = $xpath->query('//ns:feed')->item(0);
				
				$id = $feedData['feed-title'] = $feedData['feed-link'] = $feedData['feed-description'] = '';
				
				// feed must contain title, id (and updated)
				if ($feed->getElementsByTagName('id')->length != 0) $id = $feed->getElementsByTagName('id')->item(0)->nodeValue;
				if ($feed->getElementsByTagName('title')->length != 0) $feedData['feed-title'] = $feed->getElementsByTagName('title')->item(0)->nodeValue;
				if (empty($id) || empty($feedData['feed-title'])) {
					if ($bot->enableLog) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'status' => 1,
								'additionalData' => 'wcf.acp.uzbot.error.feedIllegal.atom'
						]);
					}
					continue;
				}
				
				if ($feed->getElementsByTagName('link')->length != 0) {
					$feedData['feed-link'] = $feed->getElementsByTagName('link')->item(0)->getAttribute('href');
				}
				
				if ($feed->getElementsByTagName('subtitle')->length != 0) {
					$feedData['feed-description'] = $feed->getElementsByTagName('subtitle')->item(0)->nodeValue;
				}
				else $feedData['feed-description'] = $feedData['feed-title'];
				
				// get items
				$items = $xpath->query('////ns:entry');
				$count = 0;
				foreach ($items as $item) {
					// check for max items
					if ($bot->feedreaderMaxItems && $count >= $bot->feedreaderMaxItems) break;
					$count ++;
					
					// preset
					$data = [];
					$nodes = $xpath->query('child::*', $item);
					
					// read all nodes
					foreach ($nodes as $node) {
						// read categories
						if ($node->nodeName == 'category') {
							if (!isset($data['categories'])) {
								$data['categories'] = [];
							}
							if ($node->attributes->getNamedItem('term')) {
								$data['categories'][] = $node->attributes->getNamedItem('term')->nodeValue;
							}
						}
						else {
							// link (adopted from WL-Reader)
							if ($node->nodeName == 'link') {
								if (!isset($data[$node->nodeName]) && $node->attributes->getNamedItem('href')) {
									$rel = $node->attributes->getNamedItem('rel');
									if (($rel && $rel->nodeValue == 'alternate') || $rel === null) {
										$data[$node->nodeName] = $node->attributes->getNamedItem('href')->nodeValue;
									}
								}
							}
							else $data[$node->nodeName] = $node->nodeValue;
						}
					}
					
					// needs id, title, link (and updated) / content not required
					if (empty($data['id']) || empty($data['title']) || empty($data['link'])) {
						if ($bot->enableLog) {
							UzbotLogEditor::create([
									'bot' => $bot,
									'status' => 1,
									'additionalData' => 'wcf.acp.uzbot.error.feedItemIllegal.atom'
							]);
						}
						continue;
					}
					
					// set time / check for time, use update if available
					$time = 0;
					if (isset($data['published']) && isset($data['updated'])) {
						$time = strtotime($data['updated']);
						$data['published'] = $data['updated'];
					}
					else if (isset($data['published'])) {
						$time = strtotime($data['published']);
					}
					else if (isset($data['updated'])) {
						$time = strtotime($data['updated']);
						$data['published'] = $data['updated'];
					}
					else $data['published'] = '';
					
					if ($bot->feedreaderMaxAge && $time) {
						if ($time < TIME_NOW - $bot->feedreaderMaxAge * 86400) continue;
					}
					
					// check for hash. Must have id
					$hash = sha1($data['id']);
					if (isset($hashData[$hash])) continue;
					
					// get remaining data, partly recommended / optional
					if (empty($data['summary'])) $data['summary'] = $data['title'];
					if (empty($data['content'])) $data['content'] = $data['summary'];
					
					// check for search words in title, description and content
					$text = mb_strtolower($data['title'] . $data['summary'] . $data['content']);
					
					if (!empty($positiveFilterWords)) {
						$found = false;
						foreach ($positiveFilterWords as $word) {
							if (mb_strpos($text, $word) !== false) {
								$found = true;
								break;
							}
						}
						if (!$found) continue;
					}
					if (!empty($negativeFilterWords)) {
						$found = false;
						foreach ($negativeFilterWords as $word) {
							if (mb_strpos($text, $word) !== false) {
								$found = true;
								break;
							}
						}
						if ($found) continue;
					}
					
					// finally there is an item ;-)
					$feeditemData[] = [
							'feed-description' => $feedData['feed-description'],
							'feed-link' => $feedData['feed-link'],
							'feed-title' => $feedData['feed-title'],
							'feeditem-content' => $data['content'],
							'feeditem-description' => $data['summary'],
							'feeditem-link' => $data['link'],
							'feeditem-title' => $data['title'],
							'feeditem-time' => $data['published'],
							'feeditem-systemtime' => $time,
							'feeditem-systemdate' => $time,
							'feeditem-categories' => !empty($data['categories']) ? $data['categories'] : [],
							'count' => 1
					];
					
					// update hashes
					$hashes[] = $hash;
					$hashData[$hash] = 0;
				}
			}
			
			// save data, send notifications etc.
			$counter = 0;
			if (count($feeditemData)) {
				// sort by time to notify oldest items first
				usort($feeditemData, function($a, $b) {
					if (strtotime($a['feeditem-time']) > strtotime($b['feeditem-time'])) return 1;
					if (strtotime($a['feeditem-time']) < strtotime($b['feeditem-time'])) return -1;
					return 0;
				});
				
				// test mode
				if (!$bot->testMode) {
					// save hashes
					WCF::getDB()->beginTransaction();
					$sql = "INSERT INTO	wcf".WCF_N."_uzbot_feedreader_hash
							(botID, hash)
							VALUES	(?, ?)";
					$statement = WCF::getDB()->prepareStatement($sql);
					foreach ($hashes as $hash) {
						$statement->execute([$bot->botID, $hash]);
					}
					WCF::getDB()->commitTransaction();
					
					// log result
					if ($bot->enableLog) {
						$counter = count($feeditemData);
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => $counter,
								'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.feed', ['count' => $counter])
						]);
					}
				}
				else {
					$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
							'objects' => $count,
							'users' => 0,
							'userIDs' => ''
					]);
					if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => $count,
							'testMode' => 1,
							'additionalData' => serialize(['', '', $result])
					]);
				}
				
				// send to scheduler
				foreach ($feeditemData as $item) {
					$counter ++;
					
					// own publication time
					$bot->publicationTime = 0;
					if ($bot->feedreaderUseTime) {
						$bot->publicationTime = $item['feeditem-systemtime'];
					}
					
					// tags for categories
					if ($bot->feedreaderUseTags) {
						$bot->feedreaderTags = $item['feeditem-categories'];
					}
					
					$data = [
							'bot' => $bot,
							'placeholders' => $item,
							'affectedUserIDs' => [],
							'countToUserID' => []
					];
					
					$job = new NotifyScheduleBackgroundJob($data);
					BackgroundQueueHandler::getInstance()->performJob($job);
				}
			}
			else {
				// log result
				if (!$bot->testMode) {
					if ($bot->enableLog) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => $counter,
								'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.feed', ['count' => $counter])
						]);
					}
				}
				else {
					$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
							'objects' => $count,
							'users' => 0,
							'userIDs' => ''
					]);
					if (mb_strlen($result) > 64000) $result = mb_substr($result, 0, 64000) . ' ...';
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => $count,
							'testMode' => 1,
							'additionalData' => serialize(['', '', $result])
					]);
				}
			}
		}
		
		UzbotEditor::resetCache();
	}
}
