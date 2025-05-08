<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\UniversalWebsite;

use App\Address\Address;
use App\Address\Country;
use App\BetterLocation\Service\UniversalWebsiteService\LdJsonCoordinates;
use App\BetterLocation\Service\UniversalWebsiteService\LdJsonProcessor;
use App\Utils\Utils;
use PHPUnit\Framework\TestCase;

final class LdJsonProcessorTest extends TestCase
{
	private readonly LdJsonProcessor $ldJsonProcessor;

	protected function setUp(): void
	{
		parent::setUp();

		$this->ldJsonProcessor = new LdJsonProcessor();
	}

	public static function dataProvider(): array
	{
		return [
			[
				[
					new LdJsonCoordinates(50.182083, 15.801783, new Address('Rovná 1697 500 02 Hradec Králové CZ', new Country('CZ')), 'HORNBACH Hradec Králové'),
				],
				__DIR__ . '/fixtures/jsonld/hornbach.cz.html',
			],
			[
				[
					new LdJsonCoordinates(21.0140338034431, 105.83156603015266, new Address('Xã Đàn 2, Nam Đồng, Đống Đa 100000 Hanoi Capital VN', new Country('VN')), 'VietNamNet'),
				],
				__DIR__ . '/fixtures/jsonld/vietnam.net.html',
			],
			[
				[
					new LdJsonCoordinates(40.761293, -73.982294, new Address('900 Linton Blvd 33444 Delray Beach US', new Country('US')), 'Pat\'s Crab Shack'),
				],
				__DIR__ . '/fixtures/jsonld/local-business-1.html', // https://jsonld.com/local-business/
			],
			[
				[
					new LdJsonCoordinates(40.761293, -73.982294, new Address('148 W 51st St 10019 New York US', new Country('US')), 'Dave\'s Steak House'),
				],
				__DIR__ . '/fixtures/jsonld/local-business-2.html', // https://jsonld.com/local-business/
			],
			[
				[
					new LdJsonCoordinates(50.087451, 14.420671, null, 'Dave\'s Steak House'),
					new LdJsonCoordinates(-1.1234, -14.987654321, new Address('148 W 51st St 10019 New York US', new Country('US')), 'Another interesting place'),
				],
				__DIR__ . '/fixtures/jsonld/local-business-3.html', // Custom example inspired by https://jsonld.com/local-business/
			],
			[
				[], // @TODO location can be extracted from address
				__DIR__ . '/fixtures/jsonld/venue-1.html', // https://jsonld.com/event/venue/
			],
			[
				[], // @TODO location can be extracted from address
				__DIR__ . '/fixtures/jsonld/job-training-1.html', // https://jsonld.com/json-ld-course/job-training-vocational-training/
			],
			[
				[], // probably the smallest example of valid LD JSON file
				__DIR__ . '/fixtures/jsonld/table-1.html', // https://jsonld.com/table/
			],
			[
				[
					new LdJsonCoordinates(40.770766050505436, -73.96467034232786, new Address('740 Park Avenue 12345 New York United States'), 'Restaurant Pronto'),
				],
				__DIR__ . '/fixtures/jsonld/pronto-ny.com.html', // https://www.pronto-ny.com/
			],
		];
	}

	/**
	 * @param list<LdJsonCoordinates> $expectedResults
	 * @dataProvider dataProvider
	 */
	public function testProcess(array $expectedResults, string $filePath): void
	{
		$fileContent = file_get_contents($filePath);
		$dom = Utils::domFromUTF8($fileContent);
		$finder = new \DOMXPath($dom);
		$results = $this->ldJsonProcessor->processLocation($finder);
		$this->assertSameSize($expectedResults, $results);
		foreach ($results as $key => $result) {
			$expectedResult = $expectedResults[$key] ?? null;
			$this->assertEquals($expectedResult, $result);
		}
	}
}
