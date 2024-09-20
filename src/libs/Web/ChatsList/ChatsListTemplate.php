<?php declare(strict_types=1);

namespace App\Web\ChatsList;

use App\Repository\ChatEntity;
use App\Web\LayoutTemplate;
use unreal4u\TelegramAPI\Telegram;

class ChatsListTemplate extends LayoutTemplate
{
	/**
	 * @var list<ChatEntity>
	 */
	public array $chats;
}
