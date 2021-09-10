<?php declare(strict_types=1);

namespace App\OpenElevation;

use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;

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
	const LINK = 'https://api.open-elevation.com/api/v1';
	const LINK_LOOKUP = self::LINK . '/lookup';

	public $cacheTtl = 0;

	/** @param int $ttl Number of seconds to store results in cache or 0 to disable. */
	public function setCache(int $ttl): self
	{
		$this->cacheTtl = $ttl;
		return $this;
	}

	/** Fill elevation into provided Coordinates object */
	public function fill(Coordinates $coordinates): void
	{
		$response = $this->request([$coordinates]);
		$coordinates->setElevation($response->results[0]->elevation);
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
		$response = $this->request([$coords]);
		$coords->setElevation($response->results[0]->elevation);
		return $coords;
	}

	/**
	 * Get elevation for multiple coordinates
	 *
	 * @param array[array[string|int|float, string|int|float]] $inputs List of coorinates mapped as [[$lat1, $lon1],  [$lat2, $lon2], ...]
	 * @return Coordinates[]
	 */
	public function lookupBatch(array $inputs): array
	{
		if (count($inputs) === 0) {
			throw new \InvalidArgumentException('Must provide at least one location');
		}
		$locations = array_map(function ($input) {
			list($lat, $lon) = $input;
			return new Coordinates($lat, $lon);
		}, $inputs);
		$response = $this->request($locations);
		foreach ($response->results as $key => $result) { // assume, that order of coorinates is equal
			// Fill elevation to previously created object, to prevent loosing precision
			$locations[$key]->setElevation($result->elevation);
		}
		return $locations;
	}

	private function request(array $coordinates): \stdClass
	{
		$postBody = [
			'locations' => [],
		];
		foreach ($coordinates as $coords) {
			$postBody['locations'][] = [
				'latitude' => $coords->getLat(),
				'longitude' => $coords->getLon(),
			];
		}
		$curl = new MiniCurl(self::LINK_LOOKUP);
		$curl->setHttpHeader('content-type', 'application/json');
		$curl->setCurlOption(CURLOPT_POSTFIELDS, json_encode($postBody));
		$curl->allowCache($this->cacheTtl);
		return $curl->run()->getBodyAsJson();
	}
}
