<?php


namespace TelegramCustomWrapper\Events\Inline;

use \TelegramCustomWrapper\SendMessage;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;

abstract class Inline extends \TelegramCustomWrapper\Events\Events
{

	/**
	 * @param string $text
	 * @param null $replyMarkup
	 * @param bool $editMessage
	 * @throws \Exception
	 */
	public function replyButton(string $text, $replyMarkup = null, $editMessage = true) {
		$msg = new SendMessage($this->getChatId(), $text, null, $replyMarkup, $editMessage ? $this->update->callback_query->message->message_id : null);
		$this->run($msg->msg);
	}

	/**
	 * @param string $text
	 * @param bool $alert
	 * @throws \Exception
	 */
	public function flash(string $text, bool $alert = false) {
		$flash = new AnswerCallbackQuery();
		$flash->text = $text;
		$flash->show_alert = $alert;
		$flash->callback_query_id = $this->update->callback_query->id;
		$this->run($flash);
	}

}