<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\EStudankyEuService;
use App\MiniCurl\Exceptions\InvalidResponseException;

final class EStudankyEuServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return EStudankyEuService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://estudanky.eu/3762-studanka-kinska'],
			[true, 'http://estudanky.eu/3762-studanka-kinska'],
			[true, 'https://www.estudanky.eu/3762-studanka-kinska'],
			[true, 'https://www.estudanky.eu/3762'],
			[true, 'https://www.estudanky.eu/3762-'],

			// Invalid
			[false, 'some invalid url'],
			[false, 'https://estudanky.eu/nepristupne-cislo-zpet-strana-1'],
			[false, 'https://estudanky.eu/kraj-B-cislo-strana-1'],
			[false, 'https://estudanky.eu/zachranme-studanky'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.078999, 14.400600, 'https://estudanky.eu/3762-studanka-kinska'],
			[50.068591, 14.420468, 'https://estudanky.eu/10596-studna-bez-jmena'],
			[49.517083, 18.729550, 'https://estudanky.eu/4848'],
		];
	}

	public static function processInvalidIdProvider(): array
	{
		return [
			['https://estudanky.eu/999999999'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new EStudankyEuService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new EStudankyEuService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processInvalidIdProvider
	 */
	public function testInvalidId(string $input): void
	{
		$service = new EStudankyEuService();

		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "estudanky.eu"');

		$this->assertServiceNoLocation($service, $input);
	}
}
