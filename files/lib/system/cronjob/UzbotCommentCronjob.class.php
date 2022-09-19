<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\comment\CommentAction;
use wcf\data\comment\CommentList;
use wcf\data\user\UserList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Conversation Cleanup for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotCommentCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// Read all active, valid activity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_comment']);
		if (empty($bots)) return;
		
		// get language for log
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// Step through all bots and get matching comments / users
		foreach ($bots as $bot) {
			// preset data
			$commentIDs = $affectedUserIDs = $countToUserID = [];
			$count = 0;
			
			// get users iaw conditions
			if (!$bot->commentNoUser) {
				$userList = new UserList();
				$userList->getConditionBuilder()->add("user_table.userID IN (SELECT DISTINCT userID FROM wcf".WCF_N."_comment)");
				
				$conditions = $bot->getUserConditions();
				foreach ($conditions as $condition) {
					$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
				}
				
				$botConditions = $bot->getUserBotConditions();
				foreach ($botConditions as $condition) {
					$condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
				}
				
				$userList->readObjects();
				$temp = $userList->getObjects();
				$userIDs = [];
				if (count($temp)) {
					foreach ($temp as $user) {
						$userIDs[] = $user->userID;
					}
				}
			}
			else $userIDs[] = 0;	// fake value for count
			
			// find matching comments
			if (count($userIDs)) {
				// get comment types
				$typeIDs = unserialize($bot->commentTypeIDs);
				
				$commentList = new CommentList();
				$commentList->getConditionBuilder()->add('comment.time < ?', [TIME_NOW - $bot->commentDays * 86400]);
				$commentList->getConditionBuilder()->add('comment.objectTypeID IN (?)', [$typeIDs]);
				if (!$bot->commentNoUser) {
					// skip unless required
					if (!empty($conditions) || !empty($botConditions)) {
						$commentList->getConditionBuilder()->add('comment.userID IN (?)', [$userIDs]);
					}
				}
				else {
					$commentList->getConditionBuilder()->add('comment.userID IS NULL');
				}
				
				// comments without reply
				if ($bot->commentNoAnswers) {
					$commentList->getConditionBuilder()->add('comment.responses = ?', [0]);
				}
				else {
					// exclude newer replies
					$commentIDs = [];
					$sql = "SELECT DISTINCT	commentID
							FROM	wcf".WCF_N."_comment_response
							WHERE	time > ?";
					$statement = WCF::getDB()->prepareStatement($sql);
					$statement->execute([TIME_NOW - $bot->commentDays * 86400]);
					while ($row = $statement->fetchArray()) {
						$commentIDs[] = $row['commentID'];
					}
					
					if (count($commentIDs)) {
						$commentList->getConditionBuilder()->add('comment.commentID NOT IN (?)', [$commentIDs]);
					}
				}
				
				// limit
				if (!$bot->testMode) {
					$commentList->sqlLimit = UZBOT_DATA_LIMIT_COMMENT;
				}
				else {
					$commentList->sqlLimit = 1000;
				}
				
				$commentList->readObjects();
				$comments = $commentList->getObjects();
				
				$count = count($comments);
				
				if ($count) {
					foreach ($comments as $comment) {
						if ($comment->userID) {
							$affectedUserIDs[] = $comment->userID;
							if (isset($countToUserID[$comment->userID])) $countToUserID[$comment->userID] ++;
							else $countToUserID[$comment->userID] = 1;
						}
						
						if ($comment->responses) {
							$responseIDs = $comment->getResponseIDs();
							
							if (count($responseIDs)) {
								$conditions = new PreparedStatementConditionBuilder();
								$conditions->add("responseID IN (?)", [$responseIDs]);
								$sql = "SELECT	userID
										FROM	wcf".WCF_N."_comment_response
										".$conditions;
								$statement = WCF::getDB()->prepareStatement($sql);
								$statement->execute($conditions->getParameters());
								while ($row = $statement->fetchArray()) {
									if ($row['userID']) {
										$affectedUserIDs[] = $row['userID'];
										if (isset($countToUserID[$row['userID']])) $countToUserID[$row['userID']] ++;
										else $countToUserID[$row['userID']] = 1;
									}
								}
							}
						}
					}
					
					$affectedUserIDs = array_unique($affectedUserIDs);
					
					// delete comments, unless testMode
					if (!$bot->testMode) {
						$action = new CommentAction($comments, 'delete');
						$action->executeAction();
					}
				}
			}
			
			// log action
			if ($bot->enableLog) {
				if (!$bot->testMode) {
					UzbotLogEditor::create([
							'bot' => $bot,
							'count' => $count,
							'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.comment', [
									'comments' => $count,
									'users' => count($affectedUserIDs),
									'userIDs' => implode(', ', $affectedUserIDs)
							])
					]);
				}
				else {
					$result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
							'objects' => $count,
							'users' => count($affectedUserIDs),
							'userIDs' => implode(', ', $affectedUserIDs)
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
			
			// check for and prepare notification, must have deleted conversations
			if (!$bot->notifyID || !$count) continue;
			$notify = $bot->checkNotify(true, true);
			if ($notify === null) continue;
			
			$placeholders = [];
			$placeholders['count'] = $count;
			
			// test mode
			$testUserIDs = $testToUserIDs = [];
			if (count($affectedUserIDs)) {
				if ($bot->condenseEnable) {
					$testUserIDs = $affectedUserIDs;
					$testToUserIDs = $countToUserID;
				}
				else {
					$userID = reset($affectedUserIDs);
					$testUserIDs[] = $userID;
					$testToUserIDs[$userID] = $countToUserID[$userID];
				}
			}
			
			// send to scheduler
			$data = [
					'bot' => $bot,
					'placeholders' => $placeholders,
					'affectedUserIDs' => !$bot->testMode ? $affectedUserIDs : $testUserIDs,
					'countToUserID' => !$bot->testMode ? $countToUserID : $testToUserIDs
			];
			
			$job = new NotifyScheduleBackgroundJob($data);
			BackgroundQueueHandler::getInstance()->performJob($job);
		}
	}
}
