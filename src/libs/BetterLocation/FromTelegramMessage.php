<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Requestor;
use App\Utils\Strict;
use App\Utils\StringUtils;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

class FromTelegramMessage
{
	public function __construct(
		private readonly ServicesManager $servicesManager,
		private readonly Requestor $requestor,
	) {
	}

	/**
	 * @param MessageEntity[] $entities
	 */
	public function getCollection(string $message, array $entities): BetterLocationCollection
	{
		$betterLocationsCollection = new BetterLocationCollection();

		foreach ($entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'], true) === false) {
				continue;
			}

			$url = TelegramHelper::getEntityContent($message, $entity);

			if (Strict::isUrl($url) === false) {
				continue;
			}

			$url = $this->handleShortUrl($url);

			$serviceCollection = $this->servicesManager->iterate($url);
			if ($serviceCollection->filterTooClose) {
				$serviceCollection->filterTooClose(Config::DISTANCE_IGNORE);
			}
			$betterLocationsCollection->add($serviceCollection);
		}

		$messageWithoutUrls = TelegramHelper::getMessageWithoutUrls($message, $entities);
		$messageWithoutUrls = StringUtils::translit($messageWithoutUrls);
		$betterLocationsCollection->add($this->servicesManager->iterateText($messageWithoutUrls));
		$betterLocationsCollection->deduplicate();
		return $betterLocationsCollection;
	}

	private function handleShortUrl(string $url): string
	{
		if (!Url::isShortUrl($url)) {
			return $url;
		}
		return $this->requestor->loadFinalRedirectUrl($url);
	}
}
