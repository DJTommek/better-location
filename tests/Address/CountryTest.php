<?php declare(strict_types=1);

namespace Address;

use App\Address\Country;
use League\ISO3166\Exception\ISO3166Exception;
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

	/**
	 * @return array<array{string, string, string, string}>
	 */
	public function countriesProvider(): array
	{
		return [
			['CZ', 'CZE', '203', 'Czechia'],
			['NL', 'NLD', '528', 'Netherlands'],
			['US', 'USA', '840', 'United States of America'],
			['CH', 'CHE', '756', 'Switzerland'],
			['AL', 'ALB', '008', 'Albania'],
		];
	}

	/**
	 * @dataProvider countriesProvider
	 */
	public final function testFromNumericCode(string $alpha2, string $alpha3, string $numeric, string $name): void
	{
		$country = Country::fromNumericCode($numeric);
		$this->assertSame($country->code, $alpha2);
		$this->assertSame($country->displayname, $name);
	}

	public final function testFromNumericCodeInt(): void
	{
		$country = Country::fromNumericCode(203);
		$this->assertSame($country->code, 'CZ');
		$this->assertSame($country->displayname, 'Czechia');
	}

	/**
	 * @dataProvider countriesProvider
	 */
	public final function testFromAlpha2Code(string $alpha2, string $alpha3, string $numeric, string $name): void
	{
		$country = Country::fromAlpha2Code($alpha2);
		$this->assertSame($country->code, $alpha2);
		$this->assertSame($country->displayname, $name);

		$countryLower = Country::fromAlpha2Code(mb_strtolower($alpha2));
		$this->assertSame($countryLower->displayname, $country->displayname);
	}

	/**
	 * @dataProvider countriesProvider
	 */
	public final function testFromAlpha3Code(string $alpha2, string $alpha3, string $numeric, string $name): void
	{
		$country = Country::fromAlpha3Code($alpha3);
		$this->assertSame($country->code, $alpha2);
		$this->assertSame($country->displayname, $name);

		$countryLower = Country::fromAlpha3Code(mb_strtolower($alpha3));
		$this->assertSame($countryLower->displayname, $country->displayname);
	}

	public final function testInvalidFromNumericCode(): void
	{
		$this->expectException(ISO3166Exception::class);
		Country::fromNumericCode('11111');
	}

	public final function testInvalidFromNumericCode2(): void
	{
		$this->expectException(ISO3166Exception::class);
		Country::fromNumericCode(8); // valid code but should be 008
	}

	public final function testInvalidFromAlpha2Code(): void
	{
		$this->expectException(ISO3166Exception::class);
		Country::fromAlpha2Code('žž');
	}

	public final function testInvalidFromAlpha3Code(): void
	{
		$this->expectException(ISO3166Exception::class);
		Country::fromAlpha3Code('baf');
	}
}
