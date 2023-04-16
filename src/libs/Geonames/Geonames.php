<?php declare(strict_types=1);

namespace App\Geonames;

use App\Factory;
use App\Geonames\Types\TimezoneType;
use GuzzleHttp\Client;
use Nette\Caching\Cache;
use Nette\Utils\Json;
use Psr\Http\Client\ClientInterface;

class Geonames
{
	public const API_URL_FREE = 'http://api.geonames.org';
	public const API_URL_PREMIUM = 'https://secure.geonames.org';

	private string $username;
	private ClientInterface $client;

	public function __construct(string $username, string $url = self::API_URL_FREE)
	{
		$this->username = $username;
		$this->client = new Client([
			'base_uri' => $url,
			'timeout' => 10,
		]);
	}

	/**
	 * @throws GeonamesException
	 */
	public function timezone(float $lat, float $lon): ?TimezoneType
	{
		$cacheKey = sprintf('timezone2-%F-%F', $lat, $lon);
		return Factory::cache('geonames')->load($cacheKey, function (&$dependencies) use ($lat, $lon) {
			$dependencies[Cache::EXPIRE] = '5 minutes';
			return $this->timezoneReal($lat, $lon);
		});
	}

	/**
	 * @throws GeonamesException
	 */
	private function timezoneReal(float $lat, float $lon): ?TimezoneType
	{
		$response = $this->client->get('timezoneJSON', [
			'query' => [
				'username' => $this->username,
				'lat' => $lat,
				'lng' => $lon,
			],
		]);
		$jsonResponse = Json::decode((string)$response->getBody());
		if (isset($jsonResponse->status)) {
			throw new GeonamesApiException($jsonResponse->status->message, $jsonResponse->status->value);
		}

		return TimezoneType::fromResponse($jsonResponse);
	}

}
