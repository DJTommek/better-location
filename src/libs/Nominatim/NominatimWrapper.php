<?php declare(strict_types=1);

namespace App\Nominatim;

use App\Address\AddressProvider;
use DJTommek\Coordinates\CoordinatesInterface;
use maxh\Nominatim\Exceptions\NominatimException;
use Psr\SimpleCache\CacheInterface;

class NominatimWrapper implements AddressProvider
{
	const ERRORS_WHITELIST = [
		'Unable to geocode',
	];

	public function __construct(
		private readonly CacheInterface $cache,
		private readonly \maxh\Nominatim\Nominatim $nominatimClient,
	) {
	}

	public function reverse(CoordinatesInterface $coordinates): ?ReverseResponseDto
	{
		$cacheKey = sprintf('reverse-%F-%F', $coordinates->getLat(), $coordinates->getLon());

		if ($this->cache->has($cacheKey)) {
			return $this->cache->get($cacheKey);
		}

		$result = $this->reverseReal($coordinates);

		$this->cache->set($cacheKey, $result);

		return $result;
	}

	private function reverseReal(CoordinatesInterface $coordinates): ?ReverseResponseDto
	{
		$query = $this->nominatimClient->newReverse()->latlon($coordinates->getLat(), $coordinates->getLon());
		$result = $this->nominatimClient->find($query);
		if (isset($result['error'])) {
			if (in_array($result['error'], self::ERRORS_WHITELIST, true)) {
				return null;
			}
			throw new NominatimException(sprintf('NominatimWrapper API returned error: "%s"', $result['error']));
		}

		return ReverseResponseDto::cast($result);
	}
}
