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
 */
namespace wcf\system\event\listener;

use wcf\data\like\Like;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\reaction\type\ReactionType;
use wcf\data\user\User;
use wcf\data\user\UserList;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\top\UzbotTop;
use wcf\data\uzbot\top\UzbotTopEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\exception\SystemException;
use wcf\system\language\LanguageFactory;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Listen to reactions for Bot
 */
class UzbotReactionListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        // check module
        if (!MODULE_UZBOT || !MODULE_LIKE) {
            return;
        }

        // limit to create
        if ($eventObj->getActionName() != 'create') {
            return;
        }

        // get data
        $reactionTypeID = 0;
        $returnValues = $eventObj->getReturnValues();
        $like = $returnValues['returnValues'];
        $reactionTypeID = $like->reactionTypeID;

        // get object / user data
        $objectLink = $objectTitle = $objectText = '';
        $objectUserID = $likerUserID = 0;

        $objectType = ObjectTypeCache::getInstance()->getObjectType($like->objectTypeID);
        if (!$objectType) {
            return;
        }

        try {
            $object = ReactionHandler::getInstance()->getLikeableObject($objectType->objectType, $like->objectID);
            $objectUserID = $object->userID;
            $objectLink = $object->getURL();
            $objectText = $object->getMessage();
            $objectTitle = $object->getTitle();
        } catch (SystemException $e) {
            // accept, just continue
        }

        if (WCF::getUser()->userID) {
            $likerUserID = WCF::getUser()->userID;
        }

        // Read all active, valid activity bots, abort if none
        $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'user_likes']);
        if (!\count($bots)) {
            return;
        }

        // get / set data
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        // get user / like data
        $value = Like::LIKE;
        $countTotal = $countUser = $topCount = $topUserID = 0;

        // count total
        $sql = "SELECT COUNT(*) AS count
                FROM    wcf" . WCF_N . "_like
                WHERE    likeValue = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$value]);
        $countTotal = $statement->fetchColumn();

        // count of user being liked
        if ($objectUserID) {
            $sql = "SELECT COUNT(*) AS count
                    FROM    wcf" . WCF_N . "_like
                    WHERE    objectUserID = ? AND likeValue = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$objectUserID, $value]);
            $countUser = $statement->fetchColumn();
        }

        // count top
        $sql = "SELECT        objectUserID as userID, COUNT(*) AS count
                FROM        wcf" . WCF_N . "_like
                WHERE        likeValue = ?
                GROUP BY    objectUserID
                ORDER BY     count DESC";
        $statement = WCF::getDB()->prepareStatement($sql, 1);
        $statement->execute([$value]);
        if ($row = $statement->fetchArray()) {
            $countTop = $row['count'];
            $topUserID = $row['userID'];
        }

        // get present top, correct / update if required
        $top = new UzbotTop(1);
        if (!$top->liked) {
            $top->liked = UzbotTop::refreshLike();
        }
        if ($top->liked != $topUserID) {
            $editor = new UzbotTopEditor($top);
            $editor->update(['liked' => $objectUserID]);
        }

        // step through bots
        foreach ($bots as $bot) {
            // check conditions
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.userID = ?', [$objectUserID]);
            $conditions = $bot->getUserConditions();
            foreach ($conditions as $condition) {
                $condition->getObjectType()->getProcessor()->addUserCondition($condition, $userList);
            }
            $userList->readObjects();
            if (!\count($userList->getObjects())) {
                continue;
            }

            // preset data, analyse action
            $counts = \explode(',', $bot->userCount);
            $hit = false;
            $placeholders = $affectedUserIDs = $countToUserID = [];

            switch ($bot->likeAction) {
                case 'likeTotal':
                    if (\in_array($countTotal, $counts)) {
                        $hit = true;
                    }
                    break;

                case 'likeX':
                    if (\in_array($countUser, $counts)) {
                        $hit = true;
                    }
                    break;

                case 'likeTop':
                    if ($topUserID != $top->liked) {
                        $hit = true;
                    }
                    break;
            }

            // send notifiction on hit
            if ($hit) {
                // check for and prepare notification
                $notify = $bot->checkNotify(true, true);
                if ($notify === null) {
                    continue;
                }

                // liked user is affected, there must be a user
                if (!$objectUserID) {
                    continue;
                }

                $reactionType = new ReactionType($reactionTypeID);
                $temp = $reactionType->renderIcon();
                $path = WCF::getPath() . 'images/reaction/' . $reactionType->iconFile;
                $placeholders['reaction'] = '<img src="' . $path . '" alt="" width="20" height="20">';

                $placeholders['user-count'] = $countUser;
                $placeholders['count-user'] = $countUser;
                $affectedUserIDs[] = $objectUserID;
                $countToUserID[$objectUserID] = $countUser;

                // set other placeholders
                $placeholders['count'] = $countTotal;
                $placeholders['object-link'] = $objectLink;
                $placeholders['object-text'] = $objectText;
                $placeholders['object-title'] = $objectTitle;

                // object = liked
                if ($objectUserID) {
                    $user = new User($objectUserID);
                    $placeholders['object-userid'] = $objectUserID;
                    $placeholders['object-username'] = $user->username;
                    $placeholders['object-userlink'] = $user->getLink();
                    $placeholders['object-userlink2'] = StringUtil::getAnchorTag($user->getLink(), $user->username);
                } else {
                    $placeholders['object-userid'] = 0;
                    $placeholders['object-username'] = 'wcf.user.guest';
                    $placeholders['object-userlink'] = 'wcf.user.guest';
                    $placeholders['translate'] = ['object-username', 'object-userlink'];
                }

                // liker = WCF::getUser
                if ($likerUserID) {
                    $placeholders['liker-name'] = WCF::getUser()->username;
                    $placeholders['liker-id'] = $likerUserID;
                    $placeholders['liker-link'] = $userLink = WCF::getUser()->getLink();
                    $placeholders['liker-link2'] = StringUtil::getAnchorTag(WCF::getUser()->getLink(), WCF::getUser()->username);
                } else {
                    $placeholders['liker-name'] = 'wcf.user.guest';
                    $placeholders['liker-id'] = 0;
                    $placeholders['liker-link'] = 'wcf.user.guest';
                    $placeholders['translate'] = ['liker-name', 'liker-link'];
                }

                // log action
                if ($bot->enableLog) {
                    if (!$bot->testMode) {
                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'count' => 1,
                            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.affected', [
                                'total' => 1,
                                'userIDs' => \implode(', ', $affectedUserIDs),
                            ]),
                        ]);
                    } else {
                        $result = $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.test', [
                            'objects' => 1,
                            'users' => \count($affectedUserIDs),
                            'userIDs' => \implode(', ', $affectedUserIDs),
                        ]);
                        if (\mb_strlen($result) > 64000) {
                            $result = \mb_substr($result, 0, 64000) . ' ...';
                        }
                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'count' => 1,
                            'testMode' => 1,
                            'additionalData' => \serialize(['', '', $result]),
                        ]);
                    }
                }

                // send to scheduler
                $data = [
                    'bot' => $bot,
                    'placeholders' => $placeholders,
                    'affectedUserIDs' => $affectedUserIDs,
                    'countToUserID' => $countToUserID,
                ];

                $job = new NotifyScheduleBackgroundJob($data);
                BackgroundQueueHandler::getInstance()->performJob($job);
            }
        }
    }
}
