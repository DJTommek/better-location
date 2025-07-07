<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\EitaaSnappMaps;

use Tests\BetterLocation\Service\AbstractServiceTestCase;

abstract class EitaaSnappMapsAbstract extends AbstractServiceTestCase
{
	abstract protected static function getDomain(): string;

	abstract protected static function isValidExtraProvider(): array;

	abstract protected static function processExtraProvider(): array;

	final protected function getShareLinks(): array
	{
		$domain = static::getDomain();
		return [
			'https://' . $domain . '/#13.0/50.087451/14.420671',
			'https://' . $domain . '/#13.0/50.100000/14.500000',
			'https://' . $domain . '/#13.0/-50.200000/14.600000',
			'https://' . $domain . '/#13.0/50.300000/-14.700001',
			'https://' . $domain . '/#13.0/-50.400000/-14.800008',
		];
	}

	final protected function getDriveLinks(): array
	{
		return [];
	}

	/**
	 * @return array<array{bool, string}>
	 */
	final public static function isValidCommonProvider(): array
	{
		$domain = static::getDomain();
		return [
			'Default URL when page is loaded without fragment part' => [true, 'https://' . $domain . '/#13/34.64185/50.87897'],
			'With bearing and tilt' => [true, 'https://' . $domain . '/#13/34.56959/50.83642/-5.3/54'],
			'With bearing without tilt' => [true, 'https://' . $domain . '/#13/34.56959/50.83642/-5.3'],
			'Shortest possible' => [true, 'https://' . $domain . '/#/9/9'],
			[true, 'https://' . $domain . '/#13.0/50.087451/14.420671'],
			[true, 'https://' . $domain . '/#13/50.087451/14.420671'],
			'Do not care about invalid sub-parameters' => [true, 'https://' . $domain . '/#aaaa/50.087451/14.420671/bbb/ccc'],
			[true, 'https://' . $domain . '/#/1/50.087451/14.420671'],
			[true, 'https://' . $domain . '/#5.99999999/-50.087451/-0.420671'],

			[false, 'non url'],
			[false, 'https://example.com/#50.087451/14.420671'],
			[false, 'https://' . $domain . '/'],
			[false, 'https://' . $domain . '/#13/950.087451/14.420671'],
			[false, 'https://' . $domain . '/#13/50.087451/194.420671'],
			[false, 'https://' . $domain . '/#50.087451/194.420671'],
		];
	}

	final public static function processCommonProvider(): array
	{
		$domain = static::getDomain();
		return [
			[34.64185, 50.87897, 'https://' . $domain . '/#13/34.64185/50.87897'],
			[50.087451, 14.420671, 'https://' . $domain . '/#aaaa/50.087451/14.420671'],
			[-50.087451, -0.420671, 'https://' . $domain . '/#5.99999999/-50.087451/-0.420671'],
			[34.56959, 50.83642, 'https://' . $domain . '/#13/34.56959/50.83642/-5.3/54'],
			[34.56959, 50.83642, 'https://' . $domain . '/#13/34.56959/50.83642/-5.3'],
			[34.56959, 50.83642, 'https://' . $domain . '/#13/34.56959/50.83642'],
			'Shortest possible' => [9, 9, 'https://' . $domain . '/#/9/9'],
		];
	}

	/**
	 * @dataProvider isValidCommonProvider
	 * @dataProvider isValidExtraProvider
	 */
	final public function testIsValid(bool $expectedIsValid, string $link): void
	{
		$serviceClass = $this->getServiceClass();
		$this->assertServiceIsValid(new $serviceClass(), $link, $expectedIsValid);
	}

	/**
	 * @dataProvider processCommonProvider
	 * @dataProvider processExtraProvider
	 */
	final public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$serviceClass = $this->getServiceClass();
		$this->assertServiceLocation(new $serviceClass(), $input, $expectedLat, $expectedLon);
	}
}
