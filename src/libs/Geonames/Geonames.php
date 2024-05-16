<?php declare(strict_types=1);

namespace App\Geonames;

use App\Geonames\Types\TimezoneType;
use Nette\Utils\Json;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class Geonames
{
	public const API_URL_FREE = 'http://api.geonames.org';
	public const API_URL_PREMIUM = 'https://secure.geonames.org';
	public const CACHE_TTL = 300;

	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly CacheInterface $cache,
		private readonly string $username,
		private readonly string $url = self::API_URL_FREE,
	) {
	}

	/**
	 * @throws GeonamesException
	 */
	public function timezone(float $lat, float $lon): ?TimezoneType
	{
		$cacheKey = sprintf('timezone2-%F-%F', $lat, $lon);

		if ($this->cache->has($cacheKey)) {
			return $this->cache->get($cacheKey);
		}

		$result = $this->timezoneReal($lat, $lon);

		$this->cache->set($cacheKey, $result, self::CACHE_TTL);

		return $result;
	}

	/**
	 * @throws GeonamesException
	 */
	private function timezoneReal(float $lat, float $lon): ?TimezoneType
	{
		$queryParams = [
			'username' => $this->username,
			'lat' => $lat,
			'lng' => $lon,
		];
		$url = $this->url . '/timezoneJSON?' . http_build_query($queryParams);

		$request = new \GuzzleHttp\Psr7\Request(
			method: 'GET',
			uri: $url,
		);

		$response = $this->httpClient->sendRequest($request);
		$jsonResponse = Json::decode((string)$response->getBody());
		if (isset($jsonResponse->status)) {
			throw new GeonamesApiException($jsonResponse->status->message, $jsonResponse->status->value);
		}

		return TimezoneType::fromResponse($jsonResponse);
	}

}
