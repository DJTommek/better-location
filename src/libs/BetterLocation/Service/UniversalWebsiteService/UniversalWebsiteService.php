<?php declare(strict_types=1);

namespace App\BetterLocation\Service\UniversalWebsiteService;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\Config;
use App\Http\UserAgents;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\Utils\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Universal Website service loads actual content of the page and search for various location formats.
 */
final class UniversalWebsiteService extends AbstractService
{
	const ID = 58;
	const NAME = 'Universal website';
	public const TAGS = [];

	const TYPE_SCHEMA_JSON_GEO = 'Schema JSON GEO';

	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly CacheInterface $cache,
		private readonly LdJsonProcessor $ldJsonProcessor,
	) {
	}


	public function validate(): bool
	{
		return isset($this->url);
	}

	public function process(): void
	{
		$response = $this->loadUrl();
		$dom = Utils::domFromUTF8((string)$response->getBody());
		$finder = new \DOMXPath($dom);
		$this->processLdJson($finder);
	}

	private function processLdJson(\DOMXPath $domFinder): void
	{
		$places = $this->ldJsonProcessor->processLocation($domFinder);
		foreach ($places as $place) {
			$location = new BetterLocation($this->url, $place->getLat(), $place->getLon(), self::class, self::TYPE_SCHEMA_JSON_GEO);
			$location->setAddress($place->address);
			if ($place->placeName !== null) {
				$location->setPrefixTextInLink($place->placeName, usePrefixServiceName: false, usePrefixServiceType: false);
				$location->setInlinePrefixMessage($place->placeName);
			}
			$location->prependToPrefixMessage(Icons::MAGIC);

			// @TODO remove after it is properly tested on production (introduced 2025-05-08)
			$disclaimer = sprintf(
				Icons::INFO . ' Location was extracted using new experimental feature. Use %s to report any issues.',
				FeedbackCommand::getTgCmd(true),
			);
			$location->addDescription($disclaimer, self::class . '-disclaimer');
			$this->collection->add($location);
		}
	}

	private function loadUrl(): ResponseInterface
	{
		assert($this->url !== null);
		$cacheKey = md5(self::class . $this->url);
		$cachedResult = $this->cache->get($cacheKey);
		if ($cachedResult !== null) {
			[$statusCode, $headers, $body, $protocolVersion] = $cachedResult;
			return new Response($statusCode, $headers, $body, $protocolVersion);
		}

		$response = $this->doRequest();
		$contentToCache = [
			$response->getStatusCode(),
			$response->getHeaders(),
			(string)$response->getBody(),
			$response->getProtocolVersion(),
		];
		$this->cache->set($cacheKey, $contentToCache, Config::CACHE_TTL_UNIVERSAL_WEBSITE);
		return $response;
	}

	private function doRequest(): ResponseInterface
	{
		$requestHeaders = [];
		if ($this->randomizeUserAgent()) {
			$requestHeaders['User-Agent'] = UserAgents::getRandom();
		}
		$request = new Request('GET', (string)$this->url, $requestHeaders);
		return $this->httpClient->sendRequest($request);
	}

	private function randomizeUserAgent(): bool
	{
		if (str_starts_with($this->url->getDomain(2), 'hornbach.')) {
			// As of 2025-05-07, Hornbach websites returning weird response, that couldn load website, if request is
			// HTTP 2.0 and valid modern user agent is provided. If user agent is gibberish (eg. BetterLocation) then
			// correct response is returned.
			//
			// Invalid response is HTML, but this is text rendered in browser:
			// > A required part of this site couldn’t load. This may be due to a browser extension, network issues, or
			// > browser settings. Please check your connection, disable any ad blockers, or try using a different browser.
			return false;
		}

		return true;
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_SCHEMA_JSON_GEO,
		];
	}
}
