<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */use wcf\data\category\CategoryEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\uzbot\top\UzbotTopAction;
use wcf\system\WCF;

/**
 * Install script for Community Bot 3
 */
// add default category
$sql = "SELECT    objectTypeID
        FROM    wcf" . WCF_N . "_object_type
        WHERE    definitionID = ? AND objectType = ?";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([
    ObjectTypeCache::getInstance()->getDefinitionByName('com.woltlab.wcf.category')->definitionID,
    'com.uz.wcf.bot.category',
]);

CategoryEditor::create([
    'objectTypeID' => $statement->fetchColumn(),
    'title' => 'Default Category',
    'time' => TIME_NOW,
]);

// top values - useriDs
$attachment = $comment = $followed = $liked = $disliked = null;

$sql = "SELECT         userID, COUNT(*) AS count
        FROM        wcf" . WCF_N . "_attachment
        WHERE        userID > ?
        GROUP BY    userID
        ORDER BY     count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0]);
$row = $statement->fetchArray();
if (!empty($row)) {
    $attachment = $row['userID'];
}

$sql = "SELECT         userID, COUNT(*) AS count
        FROM        wcf" . WCF_N . "_comment
        WHERE        userID > ?
        GROUP BY    userID
        ORDER BY     count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0]);
$row = $statement->fetchArray();
if (!empty($row)) {
    $comment = $row['userID'];
}

$sql = "SELECT         followUserID as userID, COUNT(*) AS count
        FROM        wcf" . WCF_N . "_user_follow
        WHERE        followUserID > ?
        GROUP BY    followUserID
        ORDER BY     count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0]);
$row = $statement->fetchArray();
if (!empty($row)) {
    $followed = $row['userID'];
}

$sql = "SELECT         objectUserID as userID, COUNT(*) AS count
        FROM        wcf" . WCF_N . "_like
        WHERE        objectUserID > ? AND likeValue = ?
        GROUP BY    objectUserID
        ORDER BY     count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0, 1]);
$row = $statement->fetchArray();
if (!empty($row)) {
    $liked = $row['userID'];
}

$sql = "SELECT         objectUserID as userID, COUNT(*) AS count
        FROM        wcf" . WCF_N . "_like
        WHERE        objectUserID > ? AND likeValue = ?
        GROUP BY    objectUserID
        ORDER BY     count DESC";
$statement = WCF::getDB()->prepareStatement($sql, 1);
$statement->execute([0, -1]);
$row = $statement->fetchArray();
if (!empty($row)) {
    $disliked = $row['userID'];
}

$action = new UzbotTopAction([], 'create', [
    'data' => [
        'attachment' => $attachment,
        'comment' => $comment,
        'followed' => $followed,
        'liked' => $liked,
        'disliked' => $disliked,
    ], ]);
$action->executeAction();
