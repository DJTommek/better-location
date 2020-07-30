<?php


namespace TelegramCustomWrapper\Events\Button;

use \TelegramCustomWrapper\SendMessage;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;

abstract class Button extends \TelegramCustomWrapper\Events\Events
{
	protected function getChatId() {
		return $this->update->callback_query->message->chat->id;
	}
	protected function getMessageId() {
		return $this->update->callback_query->message->message_id;
	}

	public function replyButton(string $text, array $options = []) {
		// if not set, set default to true
		if (!isset($options['edit_message'])) {
			$options['edit_message'] = true;
		}

		$msg = new SendMessage(
			$this->getChatId(),
			$text,
			null,
			null,
			$options['edit_message'] ? $this->getMessageId() : null,
		);
		if (isset($options['reply_markup'])) {
			$msg->setReplyMarkup($options['reply_markup']);
		}
		if (isset($options['disable_web_page_preview'])) {
			$msg->disableWebPagePreview($options['disable_web_page_preview']);
		}
		return $this->run($msg->msg);
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