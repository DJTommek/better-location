<?php declare(strict_types=1);

namespace App\Repository;

use unreal4u\TelegramAPI\Telegram\Types\ChatMember\ChatMemberAdministrator;
use unreal4u\TelegramAPI\Telegram\Types\ChatMember\ChatMemberOwner;

class ChatMemberEntity extends Entity
{
	/**
	 * User created this group/supergroup/channel or it is private chat.
	 */
	public const ROLE_CREATOR = ChatMemberOwner::STATUS;
	public const ROLE_ADMINISTRATOR = ChatMemberAdministrator::STATUS;

	/**
	 * @param array<string, mixed> $row
	 */
	static function fromRow(array $row): ChatMemberEntity
	{
		throw new \RuntimeException(self::class . ' is not supported');
	}
}
