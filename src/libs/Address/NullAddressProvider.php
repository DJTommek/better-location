<?php declare(strict_types=1);

namespace App\Address;

use DJTommek\Coordinates\CoordinatesInterface;

/**
 * Address provider, that always returns null. Useful for tests.
 */
final readonly class NullAddressProvider implements AddressProvider
{
	public function reverse(CoordinatesInterface $coordinates): ?AddressInterface
	{
		return null;
	}
}
