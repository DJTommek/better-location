<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Methods\AnswerCallbackQuery;

abstract class Button extends \App\TelegramCustomWrapper\Events\Events
{
	/** @return bool False if clicked on button in shared in message created from inline (in "via @BotName") */
	public function hasTgMessage(): bool
	{
		return TelegramHelper::hasButtonMessage($this->update);
	}

	public function getTgMessage(): Telegram\Types\Message
	{
		if ($this->hasTgMessage()) {
			return $this->update->callback_query->message;
		} else {
			throw new \RuntimeException(sprintf('Type %s doesn\'t support getMessage().', static::class));
		}
	}

	/**
	 * Can't use from in getMessage, because that's message where was clicked on button which is message from bot.
	 */
	public function getTgFrom(): Telegram\Types\User
	{
		return $this->update->callback_query->from;
	}

	/**
	 * @throws \Exception
	 */
	public function replyButton(string $text, ?Telegram\Types\Inline\Keyboard\Markup $markup = null, array $options = []): ?TelegramTypes
	{
		$msg = new \unreal4u\TelegramAPI\Telegram\Methods\EditMessageText();
		$msg->text = $text;
		$msg->chat_id = $this->getTgChatId();
		$msg->parse_mode = 'HTML';
		if ($markup) {
			$msg->reply_markup = $markup;
		}
		if (isset($options['disable_web_page_preview'])) {
			$msg->disable_web_page_preview = $options['disable_web_page_preview'];
		} else {
			$msg->disable_web_page_preview = true;
		}
		$msg->message_id = $this->getTgMessageId();
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
