<?php declare(strict_types=1);

namespace App\Address;

use App\Google\Geocoding\StaticApi;
use App\Nominatim\NominatimWrapper;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;

/**
 * Iterate via registered AddressProviders until match is found or if no provider is able to find address, then returns
 * null. No exceptions are thrown.
 */
readonly class UniversalAddressProvider implements AddressProvider
{
	/**
	 * @var list<AddressProvider>
	 */
	private array $providers;

	public function __construct(
		?StaticApi $google,
		?NominatimWrapper $nominatim,
	) {
		$this->providers = array_values(array_filter([
			$google,
			$nominatim,
		]));
	}

	public function reverse(CoordinatesInterface $coordinates): ?AddressInterface
	{
		foreach ($this->providers as $provider) {
			assert($provider instanceof AddressProvider);
			try {
				$address = $provider->reverse($coordinates)?->getAddress();
				if ($address !== null) {
					return $address;
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception);
			}
		}

		return null;
	}
}
