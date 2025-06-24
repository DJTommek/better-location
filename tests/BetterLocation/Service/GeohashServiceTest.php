<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\GeohashService;

final class GeohashServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return GeohashService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://geohash.softeng.co/u2fkbnhu9cxe',
			'https://geohash.softeng.co/u2fm1bqtdkzt',
			'https://geohash.softeng.co/hr46kjr7u9tp',
			'https://geohash.softeng.co/g8vw1kzf9psg',
			'https://geohash.softeng.co/5xj3r0yywz41',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidCodeProvider(): array
	{
		return [
		[true, 'u2fkbnhu9cxe'],
		[true, '6gkzwgjzn820'],
		[true, '6gkzmg1w'],
		[true, 'u'],
		[true, 'uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu'],

		[false, 'a'], // invalid number of characters
		[false, 'uuuuuuuu1uuuuua'], // invalid character, number a
		[false, 'c216ne:Mt_Hood'], // do not allow name, it is not part of code but it is ok in URL
		];
	}

	public static function isValidUrlOldProvider(): array
	{
		return [
			[true, 'http://geohash.org/u2fkbnhu9cxe'],
			[true, 'https://geohash.org/u2fkbnhu9cxe'],
			[true, 'http://geohash.org/6gkzwgjzn820'],
			[true, 'http://geohash.org/6gkzwgjzn820'],
			[true, 'http://geohash.org/6gkzmg1w'],
			[true, 'http://geohash.org/b'],
			[true, 'http://geohash.org/9'],
			[true, 'http://geohash.org/uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu'],
			[true, 'http://geohash.org/c216ne:Mt_Hood'],  // with name in url

			[false, 'http://geohash.org/'],
			[false, 'http://geohash.org/abcdefgh'],  // invalid character a
		];
	}

	public static function isValidUrlNewProvider(): array
	{
		return [
			[true, 'https://geohash.softeng.co/u2fkbnhu9cxe'],
			[true, 'http://geohash.softeng.co/u2fkbnhu9cxe'],
			[true, 'https://geohash.softeng.co/6gkzwgjzn820'],
			[true, 'https://geohash.softeng.co/6gkzwgjzn820'],
			[true, 'https://geohash.softeng.co/6gkzmg1w'],
			[true, 'https://geohash.softeng.co/b'],
			[true, 'https://geohash.softeng.co/9'],
			[true, 'https://geohash.softeng.co/uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu'],
			[true, 'https://geohash.softeng.co/c216ne:Mt_Hood'],  // with name in url

			[false, 'https://geohash.softeng.co/'],
			[false, 'https://geohash.softeng.co/abcdefgh'],  // invalid character a
		];
	}

	public static function processCodeProvider(): array {
		return [
			[50.087451,14.420671, 'u2fkbnhu9cxe'],
			[-25.382708,-49.265506,'6gkzwgjzn820'],
			[-25.426741,-49.315395,'6gkzmg1w'],

			// Due to ignoring above certaing precision, these coordinates are same even if geohash is different
			'precision test part 1' => [72.580645,40.645161,'uuuuuuuuuuu'],
			'precision test part 2' => [72.580645,40.645161,'uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu'],
		];
	}

	public static function processUrlOldProvider(): array {
		return [
			[50.087451,14.420671, 'http://geohash.org/u2fkbnhu9cxe'],
			[45.370789,-121.701050, 'http://geohash.org/c216ne:Mt_Hood'], // with name
			[-25.382708,-49.265506, 'http://geohash.org/6gkzwgjzn820'],
			[-25.426741,-49.315395, 'http://geohash.org/6gkzmg1w'],
		];
	}

	public static function processUrlNewProvider(): array {
		return [
			[50.087451,14.420671, 'https://geohash.softeng.co/u2fkbnhu9cxe'],
			[45.370789,-121.701050, 'https://geohash.softeng.co/c216ne:Mt_Hood'], // with name
			[-25.382708,-49.265506, 'https://geohash.softeng.co/6gkzwgjzn820'],
			[-25.426741,-49.315395, 'https://geohash.softeng.co/6gkzmg1w'],
		];
	}

	/**
	 * @dataProvider isValidCodeProvider
	 * @dataProvider isValidUrlOldProvider
	 * @dataProvider isValidUrlNewProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$this->assertServiceIsValid(new GeohashService(), $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processCodeProvider
	 * @dataProvider processUrlOldProvider
	 * @dataProvider processUrlNewProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$this->assertServiceLocation(new GeohashService(), $input, $expectedLat, $expectedLon);
	}

	public function testSearchInText(): void
	{
		$collection = GeohashService::findInText('some random text');
		$this->assertCount(0, $collection, 'Searching in string is currently disabled, because it is too similar to normal words');
	}
}
