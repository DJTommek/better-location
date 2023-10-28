<?php declare(strict_types=1);

namespace Tests\Address;

use App\Address\Address;
use App\Address\AddressInterface;
use App\Address\Country;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
	/**
	 * @return array<array{string}>
	 */
	public function basicProvider(): array
	{
		return [
			// From Google provider
			['Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia'],
			['9F22G222+22'], // Plus code is returned automatically when Google is unable to translate coordinates to real address (eg location in ocean)
			['2-chōme-45-35 Izumi, Suginami City, Tokyo 168-0063, Japan'],
			['241 Rue du Mont Aimé, 76780 Nolléval, France'],

			// From Nominatim provider
			['Staré Město, Praha 1, Hlavní město Praha, Praha, Česko'],
			['和泉二丁目, 杉並区, 東京都, 168-0063, 日本'],
			['Route du Mont Aimé, Nolléval, Dieppe, Seine-Maritime, Normandie, France métropolitaine, 76780, France'],
		];
	}

	/**
	 * @dataProvider basicProvider
	 */
	public final function testBasic(string $input): void
	{
		$address = new Address($input);

		$this->assertInstanceOf(AddressInterface::class, $address);
		$this->assertSame($input, (string)$address);
		$this->assertSame($input, $address->toString());
		$this->assertSame($input, $address->toString(false));
		$this->assertSame($input, $address->toString(true));

		$this->assertSame($address, $address->getAddress());

		$this->assertNull($address->country);

		new Address($input, null); // null should be allowed
	}

	/**
	 * @return array<array{string}>
	 */
	public function invalidProvider(): array
	{
		return [
			[''],
			['    '],
		];
	}

	/**
	 * @dataProvider invalidProvider
	 */
	public final function testInvalid(string $input): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new Address($input);
	}

	/**
	 * @return array<array{string, string, string, string}>
	 */
	public function basicCountryProvider(): array
	{
		return [
			['Some nice address', '🇨🇿 Some nice address', 'CZ', 'Some nice address'],
			['Staré Město, Praha 1, Hlavní město Praha, Praha, Česko', '🇨🇿 Staré Město, Praha 1, Hlavní město Praha, Praha, Česko', 'CZ', '   Staré Město, Praha 1, Hlavní město Praha, Praha, Česko  '],
			['和泉二丁目, 杉並区, 東京都, 168-0063, 日本', '🇯🇵 和泉二丁目, 杉並区, 東京都, 168-0063, 日本', 'jp', '和泉二丁目, 杉並区, 東京都, 168-0063, 日本'],
		];
	}

	/**
	 * @dataProvider basicCountryProvider
	 *
	 * For more tests see dedicated tests for Country class
	 */
	public final function testBasicCountry(
		string $expectedDisplayname,
		string $expectedDisplaynameFlag,
		string $inputCountryCode,
		string $inputAddress,
	): void {
		$country = new Country($inputCountryCode);
		$address = new Address($inputAddress, $country);

		$this->assertInstanceOf(Country::class, $address->country);
		$this->assertSame($country, $address->country);

		$this->assertSame($expectedDisplayname, $address->toString(false));
		$this->assertSame($expectedDisplaynameFlag, $address->toString(true));
	}
}
