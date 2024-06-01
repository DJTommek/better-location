<?php declare(strict_types=1);

namespace App\OpenElevation;

use App\BetterLocation\BetterLocationCollection;
use App\Utils\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use GuzzleHttp\Psr7\Request;
use Nette\Utils\Json;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Open-Elevation is a free and open-source alternative to the Google Elevation API and similar offerings.
 *
 * This service came out of the need to have a hosted, easy to use and easy to setup elevation API. While there are
 * some alternatives out there, none of them work out of the box, and seem to point to dead datasets. Open-Elevation
 * is easy to setup, has its own docker image and provides scripts for you to easily acquire whatever datasets you want.
 * We offer you the whole world with our public API.
 *
 * The code is fully open-source, licensed under the GPLv2. If you want, submit a pull request!
 *
 * If you enjoy our service, please consider donating to us. Servers aren't free :)
 *
 * @see https://open-elevation.com/
 * @see https://github.com/Jorl17/open-elevation
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/ Author of this PHP wrapper only, not related to API in any way.
 */
class OpenElevation
{
	public const LINK = 'https://api.open-elevation.com/api/v1';
	public const LINK_LOOKUP = self::LINK . '/lookup';

	/**
	 * @param ClientInterface $httpClient
	 * @param CacheInterface $cache
	 * @param int $cacheTtl Number of seconds to store results in cache.
	 */
	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly CacheInterface $cache,
		private readonly int $cacheTtl = 0,
	) {
	}

	/** Fill elevation into provided Coordinates object */
	public function fill(Coordinates $coordinates): void
	{
		$this->fillBatch([$coordinates]);
	}

	/**
	 * Fill elevation into provided Coordinates objects
	 *
	 * @param Coordinates[] $inputs
	 */
	public function fillBatch(array $inputs): void
	{
		$inputs = array_values($inputs);
		if (count($inputs) === 0) {
			throw new \InvalidArgumentException('Must provide at least one location');
		}
		$response = $this->request($inputs);
		foreach ($response->results as $key => $result) { // assume, that order of coorinates is equal
			// Fill elevation to previously created object, to prevent loosing precision
			$inputs[$key]->setElevation($result->elevation);
		}
	}

	/**
	 * Get elevation for specific coordinates
	 *
	 * @param float $lat latitude
	 * @param float $lon longitude
	 * @return Coordinates
	 */
	public function lookup(float $lat, float $lon): Coordinates
	{
		$coords = new Coordinates($lat, $lon);
		$this->fill($coords);
		return $coords;
	}

	/**
	 * Get elevation for multiple coordinates
	 *
	 * @param iterable<array<string|int|float, string|int|float|CoordinatesInterface>>|BetterLocationCollection $inputs List of coorinates mapped as [[$lat1, $lon1],  [$lat2, $lon2], ...]
	 * @return Coordinates[]
	 */
	public function lookupBatch(iterable $inputs): array
	{
		$coordinates = [];
		foreach ($inputs as $key => $input) {
			if ($input instanceof CoordinatesInterface) {
				$coordinates[$key] = new Coordinates($input->getLat(), $input->getLon());
				continue;
			}

			[$lat, $lon] = $input;
			$coordinates[$key] = new Coordinates($lat, $lon);
		}

		$this->fillBatch($coordinates);
		return $coordinates;
	}

	private function request(array $coordinates): \stdClass
	{
		$postBody = [
			'locations' => [],
		];
		foreach ($coordinates as $key => $coords) {
			if ($coords instanceof CoordinatesInterface === false) {
				throw new \InvalidArgumentException(sprintf('Array value on index %s is not instance of %s.', $key, CoordinatesInterface::class));
			}
			$postBody['locations'][] = [
				'latitude' => $coords->getLat(),
				'longitude' => $coords->getLon(),
			];
		}

		$requestBody = Json::encode($postBody);
		$cacheKey = md5($requestBody);
		$cacheResult = $this->cache->get($cacheKey);
		if ($cacheResult !== null) {
			return $cacheResult;
		}

		$request = new Request(
			'POST',
			self::LINK_LOOKUP,
			['content-type' => 'application/json'],
			$requestBody,
		);
		$response = $this->httpClient->sendRequest($request);
		$body = (string)$response->getBody();
		$responseJson = Json::decode($body);

		$this->cache->set($cacheKey, $responseJson, $this->cacheTtl);

		return $responseJson;
	}
}
