<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\EitaaMapsService;

final class EitaaMapsServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return EitaaMapsService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://map.eitaa.com/#13.0/50.087451/14.420671',
			'https://map.eitaa.com/#13.0/50.100000/14.500000',
			'https://map.eitaa.com/#13.0/-50.200000/14.600000',
			'https://map.eitaa.com/#13.0/50.300000/-14.700001',
			'https://map.eitaa.com/#13.0/-50.400000/-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			'Default URL when page is loaded without fragment part' => [true, 'https://map.eitaa.com/#13/34.64185/50.87897'],
			'With bearing and tilt' => [true, 'https://map.eitaa.com/#13/34.56959/50.83642/-5.3/54'],
			'With bearing without tilt' => [true, 'https://map.eitaa.com/#13/34.56959/50.83642/-5.3'],
			'Shortest possible' => [true, 'https://map.eitaa.com/#/9/9'],
			[true, 'https://map.eitaa.com/#13.0/50.087451/14.420671'],
			[true, 'https://map.eitaa.com/#13/50.087451/14.420671'],
			'Do not care about invalid sub-parameters' => [true, 'https://map.eitaa.com/#aaaa/50.087451/14.420671/bbb/ccc'],
			[true, 'https://map.eitaa.com/#/1/50.087451/14.420671'],
			[true, 'https://map.eitaa.com/#5.99999999/-50.087451/-0.420671'],

			[false, 'non url'],
			[false, 'https://example.com/#50.087451/14.420671'],
			[false, 'https://map.eitaa.com/'],
			[false, 'https://map.eitaa.com/#13/950.087451/14.420671'],
			[false, 'https://map.eitaa.com/#13/50.087451/194.420671'],
			[false, 'https://map.eitaa.com/#50.087451/194.420671'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[34.64185, 50.87897, 'https://map.eitaa.com/#13/34.64185/50.87897'],
			[50.087451, 14.420671, 'https://map.eitaa.com/#aaaa/50.087451/14.420671'],
			[-50.087451, -0.420671, 'https://map.eitaa.com/#5.99999999/-50.087451/-0.420671'],
			[34.56959, 50.83642, 'https://map.eitaa.com/#13/34.56959/50.83642/-5.3/54'],
			[34.56959, 50.83642, 'https://map.eitaa.com/#13/34.56959/50.83642/-5.3'],
			[34.56959, 50.83642, 'https://map.eitaa.com/#13/34.56959/50.83642'],
			'Shortest possible' => [9, 9, 'https://map.eitaa.com/#/9/9'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $link): void
	{
		$this->assertServiceIsValid(new EitaaMapsService(), $link, $expectedIsValid);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new EitaaMapsService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}
}
