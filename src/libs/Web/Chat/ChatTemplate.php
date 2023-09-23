<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\ServicesManager;
use App\Chat;
use App\Config;
use App\Factory;
use App\TelegramCustomWrapper\Events\Command\DebugCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Web\LayoutTemplate;
use unreal4u\TelegramAPI\Telegram;

class ChatTemplate extends LayoutTemplate
{

	// in case of ok - start
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
	// in case of ok - end
	// in case of error - start
	/** @var string */
	public $debugCommand;
	/** @var string */
	public $botLink;
	/** @var string */
	public $botName;
	/** @var string */
	public $authorName;
	/** @var string */
	public $authorLink;
	public ServicesManager $services;
	public string $formPluginerUrl = '';

	// in case of error - end

	private function prepare()
	{
		$this->botName = Config::TELEGRAM_BOT_NAME;
		$this->botLink = TelegramHelper::userLink(Config::TELEGRAM_BOT_NAME);
	}

	public function prepareOk(Telegram\Types\Chat $chatResponse)
	{
		$this->prepare();
		$this->lat = $this->exampleLocation->getLat();
		$this->lon = $this->exampleLocation->getLon();
		$this->chatResponse = $chatResponse;
		$this->services = Factory::servicesManager();
	}

	public function prepareError()
	{
		$this->prepare();
		$this->debugCommand = DebugCommand::getTgCmd(true);
		$this->authorName = 'DJTommek';
		$this->authorLink = TelegramHelper::userLink($this->authorName);
	}
}

