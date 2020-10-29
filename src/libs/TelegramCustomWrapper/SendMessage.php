<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

class SendMessage
{
	public $msg;

	public function __construct(int $chatId, string $text, $replyMessageId = null, $replyMarkup = null, $messageId = null)
	{
		if (is_null($messageId)) {
			$this->msg = new \unreal4u\TelegramAPI\Telegram\Methods\SendMessage();
		} else {
			$this->msg = new \unreal4u\TelegramAPI\Telegram\Methods\EditMessageText();
			$this->msg->message_id = $messageId;
		}
		$this->msg->text = $text;
		$this->msg->chat_id = $chatId;
		$this->setReplyToMessageId($replyMessageId);
		$this->setReplyMarkup($replyMarkup);
		$this->setParseMode('HTML');
		$this->disableWebPagePreview(false);
	}

	public function setParseMode($parseMode)
	{
		$this->msg->parse_mode = $parseMode;
	}

	public function setReplyMarkup($replyMarkup)
	{
		$this->msg->reply_markup = $replyMarkup;
	}

	public function disableWebPagePreview(bool $disableWebPagePreview)
	{
		$this->msg->disable_web_page_preview = $disableWebPagePreview;
	}

	public function setReplyToMessageId($replyMessageId)
	{
		$this->msg->reply_to_message_id = $replyMessageId;
	}
}
