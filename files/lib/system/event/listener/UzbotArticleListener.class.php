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

use wcf\data\article\Article;
use wcf\data\media\ViewableMedia;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\background\BackgroundQueueHandler;
use wcf\system\background\uzbot\NotifyScheduleBackgroundJob;
use wcf\system\cache\builder\UzbotValidBotCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Listen to Article creation for Bot
 */
class UzbotArticleListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        // check modules
        if (!MODULE_UZBOT) {
            return;
        }
        if (!MODULE_ARTICLE) {
            return;
        }

        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        $user = WCF::getUser();

        $action = $eventObj->getActionName();

        // create new article
        if ($action == 'create') {
            // Read all active, valid activity bots, abort if none
            $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'article_new']);
            if (!\count($bots)) {
                return;
            }

            // get article
            $returnValues = $eventObj->getReturnValues();
            $article = $returnValues['returnValues'];
            $articleStatus = 'wcf.uzbot.article.status';
            switch ($article->publicationStatus) {
                case Article::UNPUBLISHED:
                case Article::DELAYED_PUBLICATION:
                    $articleStatus .= '.unpublished';
                    break;
                case Article::PUBLISHED:
                    $articleStatus .= '.published';
                    break;
            }

            // set some data / placeholders
            $affectedUserIDs = $countToUserID = $placeholders = [];

            if ($article->userID) {
                $affectedUserIDs[] = $article->userID;
                $countToUserID[$article->userID] = 1;
            }

            // get total number of articles
            $articlesTotal = $articlesPublished = 0;
            $sql = "SELECT    COUNT(*) AS count
                    FROM    wcf" . WCF_N . "_article";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $articlesTotal = $statement->fetchColumn();

            $sql = "SELECT    COUNT(*) AS count
                    FROM    wcf" . WCF_N . "_article
                    WHERE    publicationStatus = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([Article::PUBLISHED]);
            $articlesPublished = $statement->fetchColumn();

            // general / language independent data
            $placeholders['count'] = $articlesTotal;
            $placeholders['count-published'] = $articlesPublished;
            $placeholders['count-user'] = 1;
            $placeholders['object-category'] = $article->getCategory()->title;
            $placeholders['object-status'] = $articleStatus;
            $placeholders['editor-id'] = $user->userID;
            $placeholders['editor-link'] = $user->getLink();
            $placeholders['editor-link2'] = StringUtil::getAnchorTag($user->getLink(), $user->username);
            $placeholders['editor-name'] = $user->username;
            $placeholders['translate'] = ['object-category', 'object-status'];

            // step through contents
            $contents = $article->getArticleContents();
            foreach ($contents as $content) {
                // texts
                $placeholders['object-link'] = $content->getLink();
                $placeholders['object-teaser'] = $content->getFormattedTeaser();
                $placeholders['object-text'] = $content->content;
                $placeholders['object-title'] = $content->getTitle();

                // thumbnails
                $placeholders['object-image-tiny'] = $placeholders['object-image-small'] = $placeholders['object-image-medium'] = $placeholders['object-image-large'] = '';
                if ($content->imageID) {
                    $image = ViewableMedia::getMedia($content->imageID);
                    $sizes = $image->getThumbnailSizes();

                    $placeholders['object-image-tiny'] = $placeholders['object-image-small'] = $placeholders['object-image-medium'] = $placeholders['object-image-large'] = $this->getImageTag($image);

                    if (isset($sizes['small'])) {
                        $placeholders['object-image-small'] = $placeholders['object-image-medium'] = $placeholders['object-image-large'] = $this->getImageTag($image, 'small');
                    }
                    if (isset($sizes['medium'])) {
                        $placeholders['object-image-medium'] = $placeholders['object-image-large'] = $this->getImageTag($image, 'medium');
                    }
                    if (isset($sizes['large'])) {
                        $placeholders['object-image-large'] = $this->getImageTag($image, 'large');
                    }
                }

                $placeholders['object-teaserimage-tiny'] = $placeholders['object-teaserimage-small'] = $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = '';
                if ($content->teaserImageID) {
                    $image = ViewableMedia::getMedia($content->teaserImageID);
                    $sizes = $image->getThumbnailSizes();

                    $placeholders['object-teaserimage-tiny'] = $placeholders['object-teaserimage-small'] = $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = $this->getImageTag($image);

                    if (isset($sizes['small'])) {
                        $placeholders['object-teaserimage-small'] = $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = $this->getImageTag($image, 'small');
                    }
                    if (isset($sizes['medium'])) {
                        $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = $this->getImageTag($image, 'medium');
                    }
                    if (isset($sizes['large'])) {
                        $placeholders['object-teaserimage-large'] = $this->getImageTag($image, 'large');
                    }
                }

                foreach ($bots as $bot) {
                    // skip unpublished articles if configured
                    if ($bot->articlePublished && $article->publicationStatus != Article::PUBLISHED) {
                        continue;
                    }

                    // skip articles not matching category
                    if ($bot->articleConditionCategoryID && $article->categoryID !== $bot->articleConditionCategoryID) {
                        continue;
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

                    // check for and prepare notification
                    $notify = $bot->checkNotify(true, true);
                    if ($notify === null) {
                        continue;
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

        // change/delete existing article
        if ($action == 'update' || $action == 'delete') {
            // Read all active, valid activity bots, abort if none
            $bots = UzbotValidBotCacheBuilder::getInstance()->getData(['typeDes' => 'article_change']);
            if (!\count($bots)) {
                return;
            }

            $articleEditor = $eventObj->getObjects()[0];

            // get basic data
            $affectedUserIDs = $countToUserID = $placeholders = [];

            $articlesTotal = $articlesPublished = 0;
            $sql = "SELECT    COUNT(*) AS count
                    FROM    wcf" . WCF_N . "_article";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $articlesTotal = $statement->fetchColumn();

            $sql = "SELECT    COUNT(*) AS count
                    FROM    wcf" . WCF_N . "_article
                    WHERE    publicationStatus = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([Article::PUBLISHED]);
            $articlesPublished = $statement->fetchColumn();

            $placeholders['count'] = $articlesTotal;
            $placeholders['count-published'] = $articlesPublished;
            $placeholders['count-user'] = 1;
            $placeholders['object-action'] = 'wcf.uzbot.action.' . $action;

            $article = $articleEditor->getDecoratedObject();

            $articleStatus = 'wcf.uzbot.article.status';
            switch ($article->publicationStatus) {
                case Article::UNPUBLISHED:
                case Article::DELAYED_PUBLICATION:
                    $articleStatus .= '.unpublished';
                    break;
                case Article::PUBLISHED:
                    $articleStatus .= '.published';
                    break;
            }

            $affectedUserIDs[] = $article->userID;
            $countToUserID[$article->userID] = 1;

            // more general / language independent data
            $placeholders['object-category'] = $article->getCategory()->title;
            $placeholders['object-status'] = $action == 'update' ? $articleStatus : 'wcf.uzbot.article.deleted';
            $placeholders['editor-id'] = $user->userID;
            $placeholders['editor-link'] = $user->getLink();
            $placeholders['editor-link2'] = StringUtil::getAnchorTag($user->getLink(), $user->username);
            $placeholders['editor-name'] = $user->username;

            if ($action == 'delete') {
                $placeholders['translate'] = ['object-category', 'object-action', 'object-status', 'object-link', 'object-status'];
            } else {
                $placeholders['translate'] = ['object-category', 'object-action', 'object-status'];
            }

            // step through contents
            $contents = $article->getArticleContents();
            foreach ($contents as $content) {
                // texts
                $placeholders['object-link'] = $content->getLink();
                $placeholders['object-teaser'] = $content->getFormattedTeaser();
                $placeholders['object-text'] = $content->content;
                $placeholders['object-title'] = $content->getTitle();

                // image thumbnails
                $placeholders['object-image-tiny'] = $placeholders['object-image-small'] = $placeholders['object-image-medium'] = $placeholders['object-image-large'] = '';
                if ($content->imageID) {
                    $image = ViewableMedia::getMedia($content->imageID);
                    $sizes = $image->getThumbnailSizes();

                    $placeholders['object-image-tiny'] = $placeholders['object-image-small'] = $placeholders['object-image-medium'] = $placeholders['object-image-large'] = $this->getImageTag($image);

                    if (isset($sizes['small'])) {
                        $placeholders['object-image-small'] = $placeholders['object-image-medium'] = $placeholders['object-image-large'] = $this->getImageTag($image, 'small');
                    }
                    if (isset($sizes['medium'])) {
                        $placeholders['object-image-medium'] = $placeholders['object-image-large'] = $this->getImageTag($image, 'medium');
                    }
                    if (isset($sizes['large'])) {
                        $placeholders['object-image-large'] = $this->getImageTag($image, 'large');
                    }
                }

                $placeholders['object-teaserimage-tiny'] = $placeholders['object-teaserimage-small'] = $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = '';
                if ($content->teaserImageID) {
                    $image = ViewableMedia::getMedia($content->teaserImageID);
                    $sizes = $image->getThumbnailSizes();

                    $placeholders['object-teaserimage-tiny'] = $placeholders['object-teaserimage-small'] = $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = $this->getImageTag($image);

                    if (isset($sizes['small'])) {
                        $placeholders['object-teaserimage-small'] = $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = $this->getImageTag($image, 'small');
                    }
                    if (isset($sizes['medium'])) {
                        $placeholders['object-teaserimage-medium'] = $placeholders['object-teaserimage-large'] = $this->getImageTag($image, 'medium');
                    }
                    if (isset($sizes['large'])) {
                        $placeholders['object-teaserimage-large'] = $this->getImageTag($image, 'large');
                    }
                }

                foreach ($bots as $bot) {
                    // skip articles not matching category
                    if ($bot->articleConditionCategoryID && $article->categoryID !== $bot->articleConditionCategoryID) {
                        continue;
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

                    // check for and prepare notification
                    $notify = $bot->checkNotify(true, true);
                    if ($notify === null) {
                        continue;
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

    /**
     * get image fe link
     */
    public function getImageLink($image, $size)
    {
        return LinkHandler::getInstance()->getLink('Media', [
            'object' => $image,
            'thumbnail' => $size,
            'forceFrontend' => true,
        ]);
    }

    /**
     * Returns a tag for certain thumbnail.
     */
    public function getImageTag($image, $size = 'tiny')
    {
        return '<img src="' . StringUtil::encodeHTML($this->getImageLink($image, $size)) . '" alt="' . StringUtil::encodeHTML($image->altText) . '" ' . ($image->title ? 'title="' . StringUtil::encodeHTML($image->title) . '" ' : '') . 'style="width: ' . $image->getThumbnailWidth($size) . 'px; height: ' . $image->getThumbnailHeight($size) . 'px;">';
    }
}
