<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\like\Like;
use wcf\data\package\Package;
use wcf\data\uzbot\stats\UzbotStats;
use wcf\data\uzbot\stats\UzbotStatsEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\WCF;

/**
 * Stats cronjob for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotStatsWcfCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		if (!MODULE_UZBOT) return;
		
		// always create stats
		
		// read data
		$statsOld = new UzBotStats(1);
		$stats = new UzBotStats(1);
		
		// Make new stats
		// article
		// comments moved to wcf1_article_content in WSC 5.5.0
		if (Package::compareVersion(WCF_VERSION, '5.5.0 Alpha 1', '>=')) {
			$sql = "SELECT	COALESCE(SUM(comments), 0) AS totalComments
					FROM 	wcf".WCF_N."_article_content";
		}
		else {
			$sql = "SELECT	COALESCE(SUM(comments), 0) AS totalComments
					FROM 	wcf".WCF_N."_article";
		}
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();
		$stats->articleComments = $row['totalComments'];
		
		$sql = "SELECT	COUNT(*) AS total, 
				COALESCE(SUM(views), 0) AS totalViews,
				COALESCE(SUM(cumulativeLikes), 0) AS totalLikes
				FROM 	wcf".WCF_N."_article";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();
		$stats->articleTotal = $row['total'];
		$stats->articleLikes = $row['totalLikes'];
		$stats->articleViews = $row['totalViews'];
		
		$sql = "SELECT	COUNT(*) AS total
				FROM 	wcf".WCF_N."_article
				WHERE	publicationStatus = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([1]);
		$row = $statement->fetchArray();
		$stats->articlePublished = $row['total'];
		$stats->articleUnpublished = $row['total'] - $stats->articlePublished;
		
		// Attachment
		$sql = "SELECT	COUNT(attachmentID) as total,
						COALESCE(SUM(filesize), 0) AS size,
						COALESCE(SUM(thumbnailSize), 0) AS sizeThumb,
						COALESCE(SUM(tinyThumbnailSize), 0) AS sizeTiny,
						COALESCE(SUM(downloads), 0) AS downloads
				FROM 	wcf".WCF_N."_attachment";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();
		$stats->attachment = $row['total'];
		$stats->attachmentDownload = $row['downloads'];
		$stats->attachmentSize = $row['size'] + $row['sizeThumb'] + $row['sizeTiny'];
		
		// Comment
		$sql = "SELECT	COUNT(*) AS total
				FROM	wcf".WCF_N."_comment";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$stats->comment = $statement->fetchColumn();
		
		$sql = "SELECT	COUNT(*) AS total
				FROM	wcf".WCF_N."_comment_response";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$stats->commentReply = $statement->fetchColumn();
		
		// Conversation
		$sql = "SELECT 	COUNT(*) AS total
				FROM 	wcf".WCF_N."_conversation";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$stats->conversation = $statement->fetchColumn();
		
		$sql = "SELECT	COUNT(*) AS total
				FROM wcf".WCF_N."_conversation_message";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$stats->conversationMsg = $statement->fetchColumn();
		
		// Like/Dislike
		$sql = "SELECT	COUNT(*) as total
				FROM 	wcf".WCF_N."_like
				WHERE	likeValue = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([Like::LIKE]);
		$stats->likes = $statement->fetchColumn();
		
		$sql = "SELECT	COUNT(*) as total
				FROM 	wcf".WCF_N."_like
				WHERE	likeValue = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([Like::DISLIKE]);
		$stats->dislikes = $statement->fetchColumn();
		
		// Follower
		$sql = "SELECT	COUNT(*) as total
				FROM 	wcf".WCF_N."_user_follow";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$stats->follower = $statement->fetchColumn();
		
		// User
		$sql = "SELECT	COUNT(*) AS total, SUM(banned) AS banned
				FROM 	wcf".WCF_N."_user";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();
		$stats->userTotal = $row['total'];
		$stats->userBanned = $row['banned'];
		
		$sql = "SELECT	COUNT(*) AS disabled
				FROM 	wcf".WCF_N."_user
				WHERE	activationCode > ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([0]);
		$stats->userDisabled = $statement->fetchColumn();
		
		$sql = "SELECT	COUNT(*) AS deleted
				FROM 	wcf".WCF_N."_user
				WHERE	quitStarted > ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([0]);
		$stats->userDeleted = $statement->fetchColumn();
		
		// don't update stats here
		
		// Read all active, valid activity bots, abort if none
		$bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'system_statistics']);
		if (!count($bots)) return;
		
		$result = [
				'articleTotal' => $stats->articleTotal,
				'articleTotalOld' => $statsOld->articleTotal,
				'articleComments' => $stats->articleComments,
				'articleCommentsOld' => $statsOld->articleComments,
				'articleLikes' => $stats->articleLikes,
				'articleLikesOld' => $statsOld->articleLikes,
				'articleViews' => $stats->articleViews,
				'articleViewsOld' => $statsOld->articleViews,
				'articleUnpublished' => $stats->articleUnpublished,
				'articleUnpublishedOld' => $statsOld->articleUnpublished,
				'attachment' => $stats->attachment,
				'attachmentOld' => $statsOld->attachment,
				'attachmentDownload' => $stats->attachmentDownload,
				'attachmentDownloadOld' => $statsOld->attachmentDownload,
				'attachmentSize' => $stats->attachmentSize,
				'attachmentSizeOld' => $statsOld->attachmentSize,
				'comment' => $stats->comment,
				'commentOld' => $statsOld->comment,
				'commentReply' => $stats->commentReply,
				'commentReplyOld' => $statsOld->commentReply,
				'conversation' => $stats->conversation,
				'conversationOld' => $statsOld->conversation,
				'conversationMsg' => $stats->conversationMsg,
				'conversationMsgOld' => $statsOld->conversationMsg,
				'likes' => $stats->likes,
				'likesOld' => $statsOld->likes,
				'dislikes' => $stats->dislikes,
				'dislikesOld' => $statsOld->dislikes,
				'follower' => $stats->follower,
				'followerOld' => $statsOld->follower,
				'userTotal' => $stats->userTotal,
				'userTotalOld' => $statsOld->userTotal,
				'userBanned' => $stats->userBanned,
				'userBannedOld' => $statsOld->userBanned,
				'userDisabled' => $stats->userDisabled,
				'userDisabledOld' => $statsOld->userDisabled,
				'userDeleted' => $stats->userDeleted,
				'userDeletedOld' => $statsOld->userDeleted,
		];
		
		$placeholders['stats'] = $result;
		$placeholders['stats-lang'] = 'wcf.uzbot.stats.wcf';
		$placeholders['date-from'] = $statsOld->time;
		$placeholders['time-from'] = $statsOld->time;
		$placeholders['date-to'] = TIME_NOW;
		$placeholders['time-to'] = TIME_NOW;
		
		// Step through all bots and get updates
		foreach ($bots as $bot) {
			// update stats unless test mode
			if (!$bot->testMode) {
				$editor = new UzbotStatsEditor($stats);
				$editor->update([
						'articleTotal' => $stats->articleTotal,
						'articleComments' => $stats->articleComments,
						'articleLikes' => $stats->articleLikes,
						'articleViews' => $stats->articleViews,
						'articleUnpublished' => $stats->articleUnpublished,
						'attachment' => $stats->attachment,
						'attachmentDownload' => $stats->attachmentDownload,
						'attachmentSize' => $stats->attachmentSize,
						'comment' => $stats->comment,
						'commentReply' => $stats->commentReply,
						'conversation' => $stats->conversation,
						'conversationMsg' => $stats->conversationMsg,
						'likes' => $stats->likes,
						'dislikes' => $stats->dislikes,
						'follower' => $stats->follower,
						'userTotal' => $stats->userTotal,
						'userBanned' => $stats->userBanned,
						'userDisabled' => $stats->userDisabled,
						'userDeleted' => $stats->userDeleted,
						'time' => TIME_NOW
				]);
			}
			
			// send to scheduler
			$notify = $bot->checkNotify(true, true);
			if ($notify === null) continue;
			
			$data = [
					'bot' => $bot,
					'placeholders' => $placeholders,
					'affectedUserIDs' => [],
					'countToUserID' => []
			];
			
			$job = new NotifyScheduleBackgroundJob($data);
			BackgroundQueueHandler::getInstance()->performJob($job);
		}
	}
}
