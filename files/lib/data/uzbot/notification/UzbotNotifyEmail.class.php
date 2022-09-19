<?php 
namespace wcf\data\uzbot\notification;
use wcf\data\uzbot\Uzbot;
use wcf\data\uzbot\log\UzbotLogEditor;
use wcf\system\email\mime\AttachmentMimePart;
use wcf\system\email\mime\MimePartFacade;
use wcf\system\email\mime\RecipientAwareTextMimePart;
use wcf\system\email\Email;
use wcf\system\email\Mailbox;
use wcf\system\exception\SystemException;
use wcf\system\language\LanguageFactory;
use wcf\util\ArrayUtil;
use wcf\util\MessageUtil;
use wcf\util\StringUtil;

/**
 * Creates email for Bot
 * 
 * @author		2014-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.bot3
 */
class UzbotNotifyEmail {
	public function send(Uzbot $bot, $content, $subject, $teaser, $language, $receiver, $tags = null) {
		// preset some data
		$defaultLanguage = LanguageFactory::getInstance()->getLanguage(LanguageFactory::getInstance()->getDefaultLanguageID());
		
		// prepare text
		$content = MessageUtil::stripCrap($content);
		$subject = MessageUtil::stripCrap(StringUtil::stripHTML($subject));
		
		// test mode
		if ($bot->testMode) {
			$teaser = '';
			if (mb_strlen($content) > 63500) $content = mb_substr($content, 0, 63500) . ' ...';
			$result = serialize([$subject, $teaser, $content]);
			
			UzbotLogEditor::create([
					'bot' => $bot,
					'count' => 1,
					'testMode' => 1,
					'additionalData' => $result
			]);
			return;
		}
		
		// get additional receivers
		$emailCC = $emailBCC = $emails = [];
		if (!empty($bot->emailCC)) $emailCC = ArrayUtil::trim(explode(",", $bot->emailCC));
		if (!empty($bot->emailBCC)) $emailBCC = ArrayUtil::trim(explode(",", $bot->emailBCC));
		
		// create emails
		try {
			$messageData = [
					'message' => $content,
					'username' => $bot->sendername
			];
			
			$email = new Email();
			$email->addRecipient(new Mailbox($receiver->email, null, $language));
			$emails[] = $receiver->email;
			
			if (count($emailCC)) {
				foreach ($emailCC as $cc) {
					if (!in_array($cc, $emails)) {
						$email->addRecipient(new Mailbox($cc, null, $language), 'cc');
						$emails[] = $cc;
					}
				}
			}
			if (count($emailBCC)) {
				foreach ($emailBCC as $bcc) {
					if (!in_array($bcc, $emails)) {
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
				if (!is_file($bot->emailAttachmentFile) || !is_readable($bot->emailAttachmentFile)) {
					$bot->emailAttachmentFile = '';
					
					if ($bot->enableLog) {
						$error = "Cannot attach file '".$path."'. It either does not exist or is not readable.";
						
						UzbotLogEditor::create([
								'bot' => $bot,
								'status' => 1,
								'count' => 1,
								'additionalData' => $error
						]);
					}
				}
			}
			if (!empty($bot->emailAttachmentFile)) {
				$html = new RecipientAwareTextMimePart('text/html', 'uzbot_email', 'wcf', $messageData);
				$emailAttachment = new AttachmentMimePart($bot->emailAttachmentFile);
				$email->setBody(new MimePartFacade([$html], [$emailAttachment]));
			}
			else {
				$email->setBody(new RecipientAwareTextMimePart('text/html', 'uzbot_email', 'wcf', $messageData));
			}
			
			// email privacy setting
			if (!$bot->emailPrivacy) {
				$email->send();
			}
			else {
				$adminCanMail = $receiver->adminCanMail;
				if ($adminCanMail === null || $adminCanMail) {
					$email->send();
				}
				else {
					if ($bot->enableLog) {
						UzbotLogEditor::create([
								'bot' => $bot,
								'count' => 1,
								'additionalData' => $defaultLanguage->getDynamicVariable('wcf.acp.uzbot.log.user.email', ['userID' => $receiver->userID])
						]);
					}
				}
			}
		}
		catch (SystemException $e) {
			// users may get lost; check sender again to abort
			if (!$bot->checkSender(true, true)) return false;
			
			// report any other error und continue
			if ($bot->enableLog) {
				$error = $defaultLanguage->get('wcf.acp.uzbot.log.notify.error') . ' ' . $e->getMessage();
				
				UzbotLogEditor::create([
						'bot' => $bot,
						'status' => 1,
						'count' => 1,
						'additionalData' => $error
				]);
			}
		}
	}
}
