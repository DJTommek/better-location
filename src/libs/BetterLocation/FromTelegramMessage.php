<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Requestor;
use App\Utils\Strict;
use App\Utils\StringUtils;
use App\Utils\Utils;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

class FromTelegramMessage
{
	public function __construct(
		private readonly ServicesManager $servicesManager,
		private readonly Requestor $requestor,
		private readonly ClientInterface $httpClient,
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

			if ($serviceCollection->isEmpty()) { // process HTTP headers only if no location was found via iteration
				$betterLocationsCollection->add($this->processHttpHeaders($url));
			}
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

	private function processHttpHeaders(string $url): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		try {
			$contentType = null;
			try {
				$request = new Request('GET', $url);
				$response = $this->httpClient->sendRequest($request);
				$contentType = $response->getHeaderLine('content-type');
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
