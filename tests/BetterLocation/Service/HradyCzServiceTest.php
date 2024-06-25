<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\HradyCzService;
use App\MiniCurl\Exceptions\InvalidResponseException;

final class HradyCzServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return HradyCzService::class;
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
			[true, 'https://www.hrady.cz/aaa-bbb-ccc'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy'],
			[true, 'https://hrady.cz/certovy-hlavy-zelizy'],
			[true, 'http://hrady.cz/certovy-hlavy-zelizy'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy/'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy/komentare'],
			[true, 'https://www.hrady.cz/certovy-hlavy-zelizy/komentare/new'],
			[true, 'https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha'],

			[false, 'some invalid url'],
			[false, 'https://www.hrady.cz/aaa-bbb'],
			[false, 'https://www.hrady.cz/mapa'],
			[false, 'https://www.hrady.cz/clanky/pohadkovemu-jicinu-predchazela-jedna-z-nejvetsich-katastrof-17-stoleti'],
			[false, 'https://www.hrady.cz/search?typ_dop=105'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.420540, 14.464405, 'https://www.hrady.cz/certovy-hlavy-zelizy'],
			[50.306440, 14.288090, 'https://www.hrady.cz/pevnost-bunkr-lo-vz-37-a-124az1z-vaha'],
			[50.305519, 14.235415, 'https://www.hrady.cz/kaple-nanebevzeti-panny-marie-miletice/ubytovani'],
		];
	}

	public static function processProviderInvalid(): array
	{
		return [
			['https://www.hrady.cz/aaa-bbb-ccc'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new HradyCzService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new HradyCzService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processProviderInvalid
	 */
	public function testInvalidId(string $input): void
	{
		$service = new HradyCzService();

		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "www.hrady.cz".');

		$this->assertServiceNoLocation($service, $input);
	}
}
