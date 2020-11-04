<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;

abstract class Button extends \App\TelegramCustomWrapper\Events\Events
{
	protected function getChatId()
	{
		return $this->update->callback_query->message->chat->id;
	}

	protected function getMessageId()
	{
		return $this->update->callback_query->message->message_id;
	}

	/**
	 * @param array $options
	 * @return ?\unreal4u\TelegramAPI\Abstracts\TelegramTypes
	 * @throws \Exception
	 */
	public function replyButton(string $text, array $options = [])
	{
		$msg = new \unreal4u\TelegramAPI\Telegram\Methods\EditMessageText();
		$msg->text = $text;
		$msg->chat_id = $this->getChatId();
		$msg->parse_mode = 'HTML';
		if (isset($options['reply_markup'])) {
			$msg->reply_markup = $options['reply_markup'];
		}
		if (isset($options['disable_web_page_preview'])) {
			$msg->disable_web_page_preview = $options['disable_web_page_preview'];
		}
		$msg->message_id = $this->getMessageId();
		return $this->run($msg);
	}

	public function flash(string $text, bool $alert = false)
	{
		$flash = new AnswerCallbackQuery();
		$flash->text = $text;
		$flash->show_alert = $alert;
		$flash->callback_query_id = $this->update->callback_query->id;
		$this->run($flash);
	}

}
