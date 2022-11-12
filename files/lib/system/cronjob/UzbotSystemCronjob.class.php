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
namespace wcf\system\cronjob;

use wcf\data\cronjob\Cronjob;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\UzbotUtils;
use wcf\system\email\Email;
use wcf\system\email\Mailbox;
use wcf\system\email\mime\AttachmentMimePart;
use wcf\system\email\mime\MimePartFacade;
use wcf\system\email\mime\RecipientAwareTextMimePart;
use wcf\system\exception\SystemException;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * System cronjob for Bot
 */
class UzbotSystemCronjob extends AbstractCronjob
{
    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        // get mail
        $mails = [];
        $sql = "SELECT        *
                FROM        wcf" . WCF_N . "_uzbot_system
                ORDER BY    id ASC";
        $statement = WCF::getDB()->prepareStatement($sql, 25);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $mails[] = $row;
        }

        if (empty($mails)) {
            return;
        }

        // bot checks and texts
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
        $placeholders = [];
        $placeholders['count'] = 0;
        $placeholders['deleted'] = '';
        $placeholders['deleted-usernames'] = '';

        $botContents = [];

        foreach ($mails as $mail) {
            // delete mail first
            $this->delete($mail['botID'], $mail['userID']);

            // get bot, recheck
            $bot = new Uzbot($mail['botID']);
            if (!$bot->botID) {
                continue;
            }

            // texts
            $botContents = $bot->checkLanguages(false, false);
            if (empty($botContents)) {
                continue;
            }

            $content = $subject = $languages = [];
            foreach ($botContents as $key => $botContent) {
                // language
                if ($key == 0) {
                    $languages[$key] = $defaultLanguage;
                } else {
                    $languages[$key] = LanguageFactory::getInstance()->getLanguage($key);
                }
                if (!$languages[$key]->languageID) {
                    $languages[$key] = $defaultLanguage;
                }

                // texts
                $content[$key] = UzbotUtils::convertPlaceholders($botContent->content, $languages[$key], $placeholders);
                $subject[$key] = UzbotUtils::convertPlaceholders($botContent->subject, $languages[$key], $placeholders);
            }

            // set language, only one notification, not both languages. 0 is default
            $languageID = $bot->notifyLanguageID;
            if ($mail['languageID'] === null) {
                $mail['languageID'] = 0;
            }
            if ($languageID == -1) {
                $languageID = 0;
            }
            if ($languageID == 0) {
                $languageID = $mail['languageID'];
            }

            try {
                if (!isset($content[$languageID]) || !isset($subject[$languageID])) {
                    $languageID = 0;
                }
                $title = \str_replace('[receiver]', $mail['username'], $subject[$languageID]);
                $title = \str_replace('[user-count]', $mail['counter'], $title);
                $text = \str_replace('[receiver]', $mail['username'], $content[$languageID]);
                $text = \str_replace('[user-count]', $mail['counter'], $text);
                $language = $languages[$languageID];
            } catch (SystemException $e) {
                continue;
            }

            // send
            try {
                $messageData = [
                    'message' => $text,
                    'username' => $bot->sendername,
                ];

                $email = new Email();
                $email->addRecipient(new Mailbox($mail['email'], null, $language));
                $email->setSubject($title);

                if (!empty($bot->emailAttachmentFile)) {
                    if (!\is_file($bot->emailAttachmentFile) || !\is_readable($bot->emailAttachmentFile)) {
                        $bot->emailAttachmentFile = '';
                    }
                }
                if (!empty($bot->emailAttachmentFile)) {
                    $html = new RecipientAwareTextMimePart('text/html', 'uzbot_email', 'wcf', $messageData);
                    $emailAttachment = new AttachmentMimePart($bot->emailAttachmentFile);
                    $email->setBody(new MimePartFacade([$html], [$emailAttachment]));
                } else {
                    $email->setBody(new RecipientAwareTextMimePart('text/html', 'uzbot_email', 'wcf', $messageData));
                }

                $email->send();
            } catch (SystemException $e) {
                continue;
            }
        }
    }

    /**
     * delete mail
     */
    public function delete($botID, $userID)
    {
        $sql = "DELETE FROM    wcf" . WCF_N . "_uzbot_system
                WHERE        botID = ? AND userID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$botID, $userID]);
    }
}
