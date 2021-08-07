<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\WazeService;
use App\Chat;
use App\Config;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\Events\Command\DebugCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Web\LayoutTemplate;
use unreal4u\TelegramAPI\Telegram;

class ChatTemplate extends LayoutTemplate
{

	// in case of ok - start
	public $telegramChatId;
	/** @var Telegram\Types\Chat */
	public $chatResponse;
	/** @var Chat */
	public $chat;
	/** @var BetterLocationMessageSettings */
	public $messageSettings;

	/** @var float */
	public $lat;
	/** @var float */
	public $lon;
	/** @var BetterLocation */
	public $exampleInput = 'https://www.waze.com/ul?ll=50.087451%2C14.420671';
	/** @var BetterLocation */
	public $exampleLocation;
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
	// in case of error - end

	private function prepare()
	{
		$this->botName = Config::TELEGRAM_BOT_NAME;
		$this->botLink = TelegramHelper::userLink(Config::TELEGRAM_BOT_NAME);
	}

	public function prepareOk(Telegram\Types\Chat $chatResponse)
	{
		$this->prepare();
		$this->exampleLocation = WazeService::processStatic($this->exampleInput)->getFirst();
		$this->lat = $this->exampleLocation->getLat();
		$this->lon = $this->exampleLocation->getLon();
		$this->chatResponse = $chatResponse;
	}

	public function prepareError()
	{
		$this->prepare();
		$this->debugCommand = DebugCommand::getCmd(true);
		$this->authorName = 'DJTommek';
		$this->authorLink = TelegramHelper::userLink($this->authorName);
	}
}

