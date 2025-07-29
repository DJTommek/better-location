<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\ServicesManager;
use App\Chat;
use App\Icons;
use App\Repository\UserEntity;
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

	/** @var array<ChoiceItem> */
	public array $chatTextChoices;
	/** @var array<ChoiceItem> */
	public array $chatLinkChoices;
	/** @var array<ChoiceItem> */
	public array $chatButtonChoices;
	/** @var list<UserEntity>|null Null if not supported in this chat */
	public ?array $ignoreFilterSenders;

	public function prepareOk(
		Telegram\Types\Chat $chatResponse,
		ServicesManager $servicesManager,
	) {
		$this->lat = $this->exampleLocation->getLat();
		$this->lon = $this->exampleLocation->getLon();
		$this->chatResponse = $chatResponse;
		$this->services = $servicesManager;

		$chatMessageSettings = $this->chat->getMessageSettings();

		$this->chatTextChoices = $this->generateChoices(
			$servicesManager->getServices([ServicesManager::TAG_GENERATE_TEXT]),
			$chatMessageSettings->getTextServices(),
			fn($service) => sprintf('%s <small class="text-muted">%s</small>', $service::getName(), $service::getShareText($this->lat, $this->lon)),
		);

		$this->chatLinkChoices = $this->generateChoices(
			$servicesManager->getServices([ServicesManager::TAG_GENERATE_LINK_SHARE]),
			$chatMessageSettings->getLinkServices(),
			fn($service) => $service::getName(),
		);

		$this->chatButtonChoices = $this->generateChoices(
			$servicesManager->getServices([ServicesManager::TAG_GENERATE_LINK_DRIVE]),
			$chatMessageSettings->getButtonServices(),
			fn($service) => Icons::CAR . ' ' . $service::getName(),
		);
	}

	/**
	 * @param array<class-string<AbstractService>> $allowedServices
	 * @param array<class-string<AbstractService>> $storedServices
	 * @param callable(class-string<AbstractService>): string $label
	 * @return array<ChoiceItem>
	 */
	private function generateChoices(array $allowedServices, array $storedServices, callable $label): array
	{
		$allTextServicesSorted = [
			...$storedServices,
			...array_diff($allowedServices, $storedServices),
		];

		$choices = [];
		foreach ($allTextServicesSorted as $service) {
			$choice = new ChoiceItem();
			$choice->value = $service::ID;
			$choice->label = $label($service);
			$choice->selected = in_array($service, $storedServices, true);
			$choices[] = $choice;
		}
		return $choices;
	}
}

