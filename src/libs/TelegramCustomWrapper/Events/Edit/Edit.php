<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Edit;

use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;

abstract class Edit extends \App\TelegramCustomWrapper\Events\Events
{
	public function getMessage(): Telegram\Types\Message
	{
		return $this->update->edited_message;
	}

	protected function editMessage(int $messageIdToEdit, string $text, array $options = [])
	{
		$editMessage = new EditMessageText();
		$editMessage->text = $text;
		$editMessage->chat_id = $this->getChatId();
		$editMessage->message_id = $messageIdToEdit;
		$editMessage->parse_mode = 'HTML';
		if ($options['reply_markup']) {
			$editMessage->reply_markup = $options['reply_markup'];
		}
		if ($options['disable_web_page_preview']) {
			$editMessage->disable_web_page_preview = $options['disable_web_page_preview'];
		}
		return $this->run($editMessage);
	}
}
