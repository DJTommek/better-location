<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\ServicesManager;
use App\Chat;
use App\Web\ChatErrorTrait;
use App\Web\LayoutTemplate;
use unreal4u\TelegramAPI\Telegram;

class ChatTemplate extends LayoutTemplate
{
	use ChatErrorTrait;

	public int $telegramChatId;
	public Telegram\Types\Chat $chatResponse;
	public Chat $chat;

	/** @var float */
	public $lat;
	/** @var float */
	public $lon;
	public string $exampleInput;
	public BetterLocation $exampleLocation;
	public bool $canBotEditMessagesOfOthers = false;
	public ServicesManager $services;
	public string $formPluginerUrl = '';

	public function prepareOk(
		Telegram\Types\Chat $chatResponse,
		ServicesManager $servicesManager,
	) {
		$this->lat = $this->exampleLocation->getLat();
		$this->lon = $this->exampleLocation->getLon();
		$this->chatResponse = $chatResponse;
		$this->services = $servicesManager;
	}
}

