<?php
use wcf\data\category\CategoryEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\uzbot\top\UzbotTopAction;
use wcf\system\WCF;

/**
 * Install script for Community Bot 3
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
// add default category
$sql = "SELECT	objectTypeID
		FROM	wcf".WCF_N."_object_type
		WHERE	definitionID = ? AND objectType = ?";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([
		ObjectTypeCache::getInstance()->getDefinitionByName('com.woltlab.wcf.category')->definitionID,
		'com.uz.wcf.bot.category'
]);

CategoryEditor::create([
		'objectTypeID' => $statement->fetchColumn(),
		'title' => 'Default Category',
		'time' => TIME_NOW
]);

// top values - useriDs
$attachment = $comment = $followed = $liked = $disliked = null;

$sql = "SELECT 		userID, COUNT(*) AS count
		FROM		wcf".WCF_N."_attachment
		WHERE		userID > ?
		GROUP BY	userID
		ORDER BY 	count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0]);
$row = $statement->fetchArray();
if (!empty($row)) $attachment = $row['userID'];

$sql = "SELECT 		userID, COUNT(*) AS count
		FROM		wcf".WCF_N."_comment
		WHERE		userID > ?
		GROUP BY	userID
		ORDER BY 	count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0]);
$row = $statement->fetchArray();
if (!empty($row)) $comment = $row['userID'];

$sql = "SELECT 		followUserID as userID, COUNT(*) AS count
		FROM		wcf".WCF_N."_user_follow
		WHERE		followUserID > ?
		GROUP BY	followUserID
		ORDER BY 	count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0]);
$row = $statement->fetchArray();
if (!empty($row)) $followed = $row['userID'];

$sql = "SELECT 		objectUserID as userID, COUNT(*) AS count
		FROM		wcf".WCF_N."_like
		WHERE		objectUserID > ? AND likeValue = ?
		GROUP BY	objectUserID
		ORDER BY 	count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0, 1]);
$row = $statement->fetchArray();
if (!empty($row)) $liked = $row['userID'];

$sql = "SELECT 		objectUserID as userID, COUNT(*) AS count
		FROM		wcf".WCF_N."_like
		WHERE		objectUserID > ? AND likeValue = ?
		GROUP BY	objectUserID
		ORDER BY 	count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute(array(0, -1));
$row = $statement->fetchArray();
if (!empty($row)) $disliked = $row['userID'];

$action = new UzbotTopAction([], 'create', array(
		'data' => array(
				'attachment' => $attachment,
				'comment' => $comment,
				'followed' => $followed,
				'liked' => $liked,
				'disliked' => $disliked
		)));
$action->executeAction();
