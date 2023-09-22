<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Edit;

use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\DateImmutableUtils;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;

abstract class Edit extends \App\TelegramCustomWrapper\Events\Events
{
	public function getTgMessage(): Telegram\Types\Message
	{
		return TelegramHelper::getMessage($this->update, true);
	}

	public function getTgMessageEditDate(): \DateTimeImmutable
	{
		$tgMessage = $this->getTgMessage();
		assert($tgMessage->edit_date !== null);
		assert($tgMessage->edit_date !== 0);
		return DateImmutableUtils::fromTimestamp($tgMessage->edit_date);
	}

	protected function editMessage(int $messageIdToEdit, string $text, array $options = []): ?TelegramTypes
	{
		$editMessage = new EditMessageText();
		$editMessage->text = $text;
		$editMessage->chat_id = $this->getTgChatId();
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
