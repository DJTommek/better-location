<?php declare(strict_types=1);

namespace App\Web;

use App\TelegramCustomWrapper\Events\Command\DebugCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;

trait ChatErrorTrait
{
	public string $debugCommand;
	public string $authorName;
	public string $authorLink;
	public bool $chatErrorRequireAdmin;

	public function prepareError(bool $requireAdmin): void
	{
		$this->debugCommand = DebugCommand::getTgCmd(true);
		$this->authorName = 'DJTommek';
		$this->authorLink = TelegramHelper::userLink($this->authorName);
		$this->chatErrorRequireAdmin = $requireAdmin;
	}
}

