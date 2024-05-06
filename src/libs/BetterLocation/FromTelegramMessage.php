<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\MiniCurl\MiniCurl;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Strict;
use App\Utils\StringUtils;
use App\Utils\Utils;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

class FromTelegramMessage
{
	public function __construct(
		private readonly ServicesManager $servicesManager,
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

			$url = self::handleShortUrl($url);

			$serviceCollection = $this->servicesManager->iterate($url);
			if ($serviceCollection->filterTooClose) {
				$serviceCollection->filterTooClose(Config::DISTANCE_IGNORE);
			}
			$betterLocationsCollection->add($serviceCollection);

			if ($serviceCollection->isEmpty()) { // process HTTP headers only if no location was found via iteration
				$betterLocationsCollection->add(self::processHttpHeaders($url));
			}
		}

		$messageWithoutUrls = TelegramHelper::getMessageWithoutUrls($message, $entities);
		$messageWithoutUrls = StringUtils::translit($messageWithoutUrls);
		$betterLocationsCollection->add($this->servicesManager->iterateText($messageWithoutUrls));
		$betterLocationsCollection->deduplicate();
		return $betterLocationsCollection;
	}

	private static function handleShortUrl(string $url): string
	{
		$originalUrl = $url;
		$tries = 0;
		while (is_null($url) === false && Url::isShortUrl($url)) {
			if ($tries >= 5) {
				Debugger::log(sprintf('Too many tries (%d) for translating original URL "%s"', $tries, $originalUrl));
				break;
			}
			$url = MiniCurl::loadRedirectUrl($url);
			$tries++;
		}
		if (is_null($url)) { // in case of some error, revert to original URL
			$url = $originalUrl;
		}
		return $url;
	}

	private static function processHttpHeaders(string $url): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		try {
			$contentType = null;
			try {
				$contentType = MiniCurl::loadHeader($url, 'content-type');
			} catch (\Throwable $exception) {
				Debugger::log(sprintf('Error while loading headers for URL "%s": %s', $url, $exception->getMessage()));
			}
			if ($contentType !== null && Utils::checkIfValueInHeaderMatchArray($contentType, Url::CONTENT_TYPE_IMAGE_EXIF)) {
				$fromExif = new FromExif($url);
				$fromExif->run(true);
				if ($fromExif->location !== null) {
					$collection->add($fromExif->location);
				}
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
		return $collection;
	}
}
