<?php 
namespace wcf\data\uzbot\notification;
use wcf\data\article\ArticleAction;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\label\object\ArticleLabelObjectHandler;
use wcf\system\label\object\UzbotNotificationLabelObjectHandler;
use wcf\util\MessageUtil;
use wcf\util\StringUtil;

/**
 * Creates articles for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyArticle {
	/**
	 * sends a single language article
	 */
	public function send(Uzbot $bot, $text, $subject, $teaser, $language, $receiver, $tags, $imageID, $teaserImageID) {
		// prepare texts
		$htmlInputProcessors = $content = [];
		
		$text = MessageUtil::stripCrap($text);
		$subject = MessageUtil::stripCrap(StringUtil::stripHTML($subject));
		if (mb_strlen($subject) > 255) $subject = mb_substr($subject, 0, 250) . '...';
		$teaser = StringUtil::decodeHTML(MessageUtil::stripCrap(StringUtil::stripHTML($teaser)));
		
		// set publication time
		$publicationTime = TIME_NOW;
		if (isset($bot->publicationTime) && $bot->publicationTime) {
			$publicationTime = $bot->publicationTime;
		}
		
		if (!$bot->testMode) {
			$htmlInputProcessors[0] = new HtmlInputProcessor();
			$htmlInputProcessors[0]->process($text, 'com.woltlab.wcf.article.content', 0);
			
			$assignedLabels = UzbotNotificationLabelObjectHandler::getInstance()->getAssignedLabels([$bot->botID], false);
			
			// tags to include feedreader
			if (!MODULE_TAGGING) {
				$tags = [];
			}
			else {
				if (isset($bot->feedreaderUseTags) && $bot->feedreaderUseTags) {
					if (isset($bot->feedreaderTags) && !empty($bot->feedreaderTags)) {
						$tags = array_unique(array_merge($tags, $bot->feedreaderTags));
					}
				}
			}
			
			$content[0] = [
					'title' => $subject,
					'tags' => !empty($tags) ? $tags : [],
					'teaser' => $teaser,
					'content' => $text,
					'htmlInputProcessor' => $htmlInputProcessors[0],
					'imageID' => $imageID,
					'teaserImageID' => $teaserImageID
			];
			
			$data = [
					'time' => $publicationTime,
					'categoryID' => $bot->articleCategoryID,
					'publicationStatus' => $bot->articlePublicationStatus,
					'publicationDate' => 0,
					'enableComments' => $bot->articleEnableComments,
					'userID' => $bot->senderID,
					'username' => $bot->sendername,
					'isMultilingual' => 0,
					'hasLabels' => !empty($assignedLabels) ? 1 : 0,
					'isUzbot' => 1
			];
			
			$objectAction = new ArticleAction([], 'create', [
					'data' => $data,
					'content' => $content
			]);
			$resultValues = $objectAction->executeAction();
			
			// set labels
			if (!empty($assignedLabels)) {
				$labelIDs = [];
				foreach ($assignedLabels as $labels) {
					foreach ($labels as $label) {
						$labelIDs[] = $label->labelID;
					}
				}
				ArticleLabelObjectHandler::getInstance()->setLabels($labelIDs, $resultValues['returnValues']->articleID);
			}
		}
		else {
			if (mb_strlen($text) > 63500) $text = mb_substr($text, 0, 63500) . ' ...';
			$result = serialize([$subject, $teaser, $text]);
			
			UzbotLogEditor::create([
					'bot' => $bot,
					'count' => 1,
					'testMode' => 1,
					'additionalData' => $result
			]);
		}
	}
	
	/**
	 * sends a multilingual article
	 */
	public function sendMulti(Uzbot $bot, $contents, $subjects, $teasers, $languageIDs, $receiver, $tags, $imageIDs, $teaserImageIDs) {
		// prepare texts
		$content = [];
		$htmlInputProcessors = [];
		
		foreach ($languageIDs as $languageID) {
			$contents[$languageID] = MessageUtil::stripCrap($contents[$languageID]);
			$subjects[$languageID] = MessageUtil::stripCrap(StringUtil::stripHTML($subjects[$languageID]));
			if (mb_strlen($subjects[$languageID]) > 255) $subject = mb_substr($subjects[$languageID], 0, 250) . '...';
			$teasers[$languageID] = StringUtil::decodeHTML(MessageUtil::stripCrap(StringUtil::stripHTML($teasers[$languageID])));
			
			if (!$bot->testMode) {
				$htmlInputProcessors[$languageID] = new HtmlInputProcessor();
				$htmlInputProcessors[$languageID]->process($contents[$languageID], 'com.woltlab.wcf.article.content', 0);
				
				// tags to include feedreader
				if (!MODULE_TAGGING) {
					$tags[$languageID] = [];
				}
				else {
					if (isset($bot->feedreaderUseTags) && $bot->feedreaderUseTags) {
						if (isset($bot->feedreaderTags) && !empty($bot->feedreaderTags)) {
							$tags[$languageID] = array_unique(array_merge($tags[$languageID], $bot->feedreaderTags));
						}
					}
				}
				
				$content[$languageID] = [
						'title' => $subjects[$languageID],
						'tags' => $tags[$languageID],
						'teaser' => $teasers[$languageID],
						'content' => $contents[$languageID],
						'htmlInputProcessor' => isset($htmlInputProcessors[$languageID]) ? $htmlInputProcessors[$languageID] : null,
						'imageID' => $imageIDs[$languageID],
						'teaserImageID' => $teaserImageIDs[$languageID]
				];
			}
			
			else {
				if (mb_strlen($contents[$languageID]) > 63500) $contents[$languageID] = mb_substr($contents[$languageID], 0, 63500) . ' ...';
				$result = serialize([$subjects[$languageID], $teasers[$languageID], $contents[$languageID]]);
				
				UzbotLogEditor::create([
						'bot' => $bot,
						'count' => 1,
						'testMode' => 1,
						'additionalData' => $result
				]);
			}
		}
		
		// set publication time
		$publicationTime = TIME_NOW;
		if (isset($bot->publicationTime) && $bot->publicationTime) {
			$publicationTime = $bot->publicationTime;
		}
		
		if (!$bot->testMode) {
			$assignedLabels = UzbotNotificationLabelObjectHandler::getInstance()->getAssignedLabels([$bot->botID], false);
			
			$data = [
					'time' => $publicationTime,
					'categoryID' => $bot->articleCategoryID,
					'publicationStatus' => $bot->articlePublicationStatus,
					'publicationDate' => 0,
					'enableComments' => $bot->articleEnableComments,
					'userID' => $bot->senderID,
					'username' => $bot->sendername,
					'isMultilingual' => 1,
					'hasLabels' => !empty($assignedLabels) ? 1 : 0,
					'isUzbot' => 1
			];
			
			$objectAction = new ArticleAction([], 'create', [
					'data' => $data,
					'content' => $content
			]);
			$resultValues = $objectAction->executeAction();
			
			// set labels
			if (!empty($assignedLabels)) {
				$labelIDs = [];
				foreach ($assignedLabels as $labels) {
					foreach ($labels as $label) {
						$labelIDs[] = $label->labelID;
					}
				}
				ArticleLabelObjectHandler::getInstance()->setLabels($labelIDs, $resultValues['returnValues']->articleID);
			}
		}
	}
}
