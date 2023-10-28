<?php declare(strict_types=1);

namespace Address;

use App\Address\Country;
use PHPUnit\Framework\TestCase;

final class CountryTest extends TestCase
{
	/**
	 * @return array<array{string, string, string, ?string}>
	 */
	public function basicProvider(): array
	{
		return [
			['CZ', 'Czechia', 'CZ', 'Czechia'],
			['CZ', 'Czech republic', 'cZ', '  Czech republic  '],
			['CZ', 'Česká republika', 'cZ', '  Česká republika  '],
			['JP', '日本', 'jp', '日本'],
			['CZ', 'CZ', 'cZ', null],
			['NL', 'NL', 'nl', null],
			['US', 'US', 'Us', null],
			['CH', 'CH', 'CH', null],
		];
	}

	/**
	 * @return array<array{string, string}>
	 */
	public function emojiProvider(): array
	{
		return [
			['🇨🇿', 'CZ'],
			['🇨🇿', 'cz'],
			['🇨🇿', 'cZ'],
			['🇳🇱', 'nl'],
			['🇺🇸', 'Us'],
			['🇨🇭', 'CH'],
		];
	}

	/**
	 * @return array<array{string}>
	 */
	public function invalidCountryCodeProvider(): array
	{
		return [
			['czcz'],
			['Czechia'],
			[''],
			['c'],
			['čr'],
			['ČR'],
			[' cz '],
			['1'],
			['23'],
		];
	}

	/**
	 * @dataProvider basicProvider
	 */
	public final function testBasic(
		string $expectedCountryCode,
		?string $expectedDisplayname,
		string $countryCode,
		?string $displayname,
	): void {
		$country = new Country($countryCode, $displayname);
		$this->assertSame($expectedCountryCode, $country->code);
		$this->assertSame($expectedDisplayname, $country->displayname);
	}

	/**
	 * @dataProvider emojiProvider
	 */
	public final function testFlagEmoji(string $expectedEmoji, string $countryCode): void
	{
		$country = new Country($countryCode);
		$this->assertSame($expectedEmoji, $country->flagEmoji());
	}

	/**
	 * @dataProvider invalidCountryCodeProvider
	 */
	public final function testInvalidCountryCode(string $input): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new Country($input);
	}
}
