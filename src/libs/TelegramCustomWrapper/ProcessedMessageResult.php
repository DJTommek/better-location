<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Factory;
use App\Pluginer\Pluginer;
use App\Pluginer\PluginerException;
use App\Utils\Ingress;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types;

class ProcessedMessageResult
{
	/**
	 * @var list<string>
	 */
	private array $resultTexts = [];
	/** @var array<array<Types\Inline\Keyboard\Button>> */
	private array $buttons = [];
	private bool $autorefreshEnabled = false;

	private int $validLocationsCount = 0;

	/**
	 * @param ?bool $addressForce Set boolean to force enable or disable processing address, ignoring $messageSettings
	 */
	public function __construct(
		private BetterLocationCollection $collection,
		private BetterLocationMessageSettings $messageSettings,
		private ?Pluginer $pluginer = null,
		private readonly ?bool $addressForce = null,
	) {
	}

	public function setAutorefresh(bool $enabled = true): void
	{
		$this->autorefreshEnabled = $enabled;
	}

	public function process(bool $printAllErrors = false): self
	{
		if ($this->pluginer !== null && $this->collection->isEmpty() === false) {
			try {
				$this->pluginer->process($this->collection);
			} catch (PluginerException $exception) {
				Debugger::log(sprintf('Error while processing pluginer: "%s"', $exception->getMessage()), Debugger::WARNING);
				// @TODO warn chat admin(s)
			} catch (\Exception $exception) {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}

		if (
			$this->addressForce !== false
			&& ($this->addressForce === true || $this->messageSettings->showAddress())
		) {
			$this->collection->fillAddresses();
		}

		foreach ($this->collection->getLocations() as $betterLocation) {
			if (
				Config::ingressTryPortalLoad()
				&& $betterLocation->hasDescription(Ingress::BETTER_LOCATION_KEY_PORTAL) === false
			) {
				$ingressClient = Factory::ingressLanchedRu();
				Ingress::setPortalDataDescription($ingressClient, $betterLocation);
			}

			$oneLocationResultText = $betterLocation->generateMessage($this->messageSettings);
			$this->buttons[] = $betterLocation->generateDriveButtons($this->messageSettings);
			$this->validLocationsCount++;
			$this->resultTexts[] = $oneLocationResultText;
		}

		assert($this->collection->count() === count($this->resultTexts));
		assert(count($this->resultTexts) === count($this->buttons));
		assert(count($this->buttons) === $this->validLocationsCount);

		return $this;
	}

	/**
	 * @return array<array<Types\Inline\Keyboard\Button>>
	 */
	public function getButtons(?int $maxRows = null, bool $includeRefreshRow = true): array
	{
		// @TODO getButtons() is always used with $maxRows = 1 so code was simplified to return only first row of
		//       buttons, keeping parameter for backward compatibility.
		assert($maxRows === null || $maxRows === 1, 'Add support for returning multiple rows.');

		if ($this->getCollection()->isEmpty()) {
			return [];
		}
		return $this->getOneLocationButtonRow(0, $includeRefreshRow);
	}

	/** @return array<array<Types\Inline\Keyboard\Button>> */
	public function getOneLocationButtonRow(int $locationIndex, bool $includeRefreshRow = true): array
	{
		$location = $this->collection->offsetGet($locationIndex);
		if ($location === null) {
			throw new \OutOfBoundsException(sprintf('Location with key %d does not exists', $locationIndex));
		}
		assert(array_key_exists($locationIndex, $this->buttons));

		$result = [
			$this->buttons[$locationIndex],
		];

		if ($includeRefreshRow && $location->isRefreshable()) {
			$result[] = BetterLocation::generateRefreshButtons($this->autorefreshEnabled);
		}
		return $result;
	}

	public function getMarkup(?int $maxRows = null, bool $includeRefreshRow = true): Types\Inline\Keyboard\Markup
	{
		$markup = new Types\Inline\Keyboard\Markup();
		$markup->inline_keyboard = $this->getButtons($maxRows, $includeRefreshRow);
		return $markup;
	}

	private function getBulkShareLinkText(): string
	{
		$bulkLinks = [];
		foreach ($this->messageSettings->getBulkLinkServices() as $bulkLinkService) {
			$bulkLinks[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				$bulkLinkService::getShareCollectionLink($this->collection),
				$bulkLinkService::getName(true),
			);
		}

		return sprintf(
			'%d locations: %s' . PHP_EOL . PHP_EOL,
			$this->collection->count(),
			implode(' | ', $bulkLinks),
		);
	}

	public function getText(
		bool $includeStaticMapUrl = true,
		int $maxTextLength = Config::TELEGRAM_BETTER_LOCATION_MESSAGE_LIMIT,
		int $maxLocationsCount = Config::TELEGRAM_MAXIMUM_LOCATIONS,
	): string {
		$result = '';

		if ($includeStaticMapUrl === true && $this->collection->isEmpty() === false) {
			$staticMapUrl = $this->collection->getStaticMapUrl();
			if ($staticMapUrl !== null) {
				$result .= TelegramHelper::invisibleLink($staticMapUrl);
			}
		}

		// If multiple locations are available, generate share bulk links
		if ($this->collection->count() > 1) {
			$result .= $this->getBulkShareLinkText();
		}

		foreach ($this->resultTexts as $i => $resultText) {
			$result .= $resultText;

			if (
				strlen($result) >= $maxTextLength // Text is already too long
				|| ($i + 1) >= $maxLocationsCount // Already too many locations
			) {
				$result .= sprintf(
					'Showing only first %d of %d detected locations. All at once can be opened with links on top of the message.',
					$i + 1,
					count($this->resultTexts),
				);

				break;
			}

		}

		return $result;
	}

	public function getOneLocationText(int $locationIndex, bool $includeStaticMapUrl = true): string
	{
		$location = $this->collection->offsetGet($locationIndex);
		if ($location === null) {
			throw new \OutOfBoundsException(sprintf('Location with key %d does not exists', $locationIndex));
		}
		assert(array_key_exists($locationIndex, $this->resultTexts));

		$result = '';

		if ($includeStaticMapUrl === true) {
			$staticMapUrl = $location->getStaticMapUrl();
			if ($staticMapUrl !== null) {
				$result .= TelegramHelper::invisibleLink($staticMapUrl);
			}
		}

		$result .= $this->resultTexts[$locationIndex];

		return $result;
	}

	public function validLocationsCount(): int
	{
		return $this->validLocationsCount;
	}

	public function getCollection(): BetterLocationCollection
	{
		return $this->collection;
	}
}
