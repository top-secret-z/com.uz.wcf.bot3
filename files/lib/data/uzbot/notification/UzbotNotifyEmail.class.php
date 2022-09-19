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
namespace wcf\data\uzbot\notification;

use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\data\uzbot\Uzbot;
use wcf\system\email\Email;
use wcf\system\email\Mailbox;
use wcf\system\email\mime\AttachmentMimePart;
use wcf\system\email\mime\MimePartFacade;
use wcf\system\email\mime\RecipientAwareTextMimePart;
use wcf\system\exception\SystemException;
use wcf\system\language\LanguageFactory;
use wcf\util\ArrayUtil;
use wcf\util\MessageUtil;
use wcf\util\StringUtil;

/**
 * Creates email for Bot
 */
class UzbotNotifyEmail
{
    public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null)
    {
        // preset some data
        $defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());

        // prepare text
        $content = MessageUtil::stripCrap($content);
        $subject = MessageUtil::stripCrap(StringUtil::stripHTML($subject));

        // test mode
        if ($bot->testMode) {
            $teaser = '';
            if (\mb_strlen($content) > 63500) {
                $content = \mb_substr($content, 0, 63500) . ' ...';
            }
            $result = \serialize([$subject, $teaser, $content]);

            UzbotLogEditor::create([
                'bot' => $bot,
                'count' => 1,
                'testMode' => 1,
                'additionalData' => $result,
            ]);

            return;
        }

        // get additional receivers
        $emailCC = $emailBCC = $emails = [];
        if (!empty($bot->emailCC)) {
            $emailCC = ArrayUtil::trim(\explode(",", $bot->emailCC));
        }
        if (!empty($bot->emailBCC)) {
            $emailBCC = ArrayUtil::trim(\explode(",", $bot->emailBCC));
        }

        // create emails
        try {
            $messageData = [
                'message' => $content,
                'username' => $bot->sendername,
            ];

            $email = new Email();
            $email->addRecipient(new Mailbox($receiver->email, null, $language));
            $emails[] = $receiver->email;

            if (\count($emailCC)) {
                foreach ($emailCC as $cc) {
                    if (!\in_array($cc, $emails)) {
                        $email->addRecipient(new Mailbox($cc, null, $language), 'cc');
                        $emails[] = $cc;
                    }
                }
            }
            if (\count($emailBCC)) {
                foreach ($emailBCC as $bcc) {
                    if (!\in_array($bcc, $emails)) {
                        $email->addRecipient(new Mailbox($bcc, null, $language), 'bcc');
                        $emails[] = $bcc;
                    }
                }
            }

            // html only
            $email->setSubject($subject);

            // attachment?
            // F:\xampp\htdocs\wsc31\images\default-logo.png

            if (!empty($bot->emailAttachmentFile)) {
                if (!\is_file($bot->emailAttachmentFile) || !\is_readable($bot->emailAttachmentFile)) {
                    $bot->emailAttachmentFile = '';

                    if ($bot->enableLog) {
                        $error = "Cannot attach file '" . $path . "'. It either does not exist or is not readable.";

                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'status' => 1,
                            'count' => 1,
                            'additionalData' => $error,
                        ]);
                    }
                }
            }
            if (!empty($bot->emailAttachmentFile)) {
                $html = new RecipientAwareTextMimePart('text/html', 'uzbot_email', 'wcf', $messageData);
                $emailAttachment = new AttachmentMimePart($bot->emailAttachmentFile);
                $email->setBody(new MimePartFacade([$html], [$emailAttachment]));
            } else {
                $email->setBody(new RecipientAwareTextMimePart('text/html', 'uzbot_email', 'wcf', $messageData));
            }

            // email privacy setting
            if (!$bot->emailPrivacy) {
                $email->send();
            } else {
                $adminCanMail = $receiver->adminCanMail;
                if ($adminCanMail === null || $adminCanMail) {
                    $email->send();
                } else {
                    if ($bot->enableLog) {
                        UzbotLogEditor::create([
                            'bot' => $bot,
                            'count' => 1,
                            'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.email', ['userID' => $receiver->userID]),
                        ]);
                    }
                }
            }
        } catch (SystemException $e) {
            // users may get lost; check sender again to abort
            if (!$bot->checkSender(true, true)) {
                return false;
            }

            // report any other error und continue
            if ($bot->enableLog) {
                $error = $defaultLanguage->get('wcf.acp.uzbot.log.notify.error') . ' ' . $e->getMessage();

                UzbotLogEditor::create([
                    'bot' => $bot,
                    'status' => 1,
                    'count' => 1,
                    'additionalData' => $error,
                ]);
            }
        }
    }
}
