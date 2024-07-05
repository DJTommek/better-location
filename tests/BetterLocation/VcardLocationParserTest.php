<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\GooglePlaceApi;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\VcardLocationParser;
use App\Config;
use App\Factory;
use PHPUnit\Framework\TestCase;
use Tests\LocationTrait;

final class VcardLocationParserTest extends TestCase
{
	use LocationTrait;

	private static GooglePlaceApi $api;

	public static function setUpBeforeClass(): void
	{
		if (!Config::isGooglePlaceApi()) {
			self::markTestSkipped('Missing Google API key');
		}

		self::$api = Factory::googlePlaceApi();
	}

	public static function basicProvider(): array
	{
		return [
			[
				__DIR__ . '/fixtures/contact1-multiple-addresses.vcf',
				[
					[50.087400, 14.419857, GoogleMapsService::TYPE_INLINE_SEARCH, 'Contact Tomas Palider HOME address'],
					[37.790106, -122.390502, GoogleMapsService::TYPE_INLINE_SEARCH, 'Contact Tomas Palider WORK address'],
					[-41.330520, 174.812066, GoogleMapsService::TYPE_INLINE_SEARCH, 'Contact Tomas Palider OTHER address'],
				],
			],
			[
				__DIR__ . '/fixtures/contact2-no-location.vcf',
				[],
			],
		];
	}


	/**
	 * @dataProvider basicProvider
	 * @group request
	 */
	public function testBasic(string $filePath, array $expectedResults): void
	{
		$fileContent = file_get_contents($filePath);
		$parser = new VcardLocationParser($fileContent, self::$api);
		$parser->process();

		$this->assertCollection($parser->getCollection(), $expectedResults);
	}

	public function testUnprocessedYet(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Run App\BetterLocation\VcardLocationParser::process() first.');
		$parser = new VcardLocationParser('anything here', self::$api);
		$parser->getCollection();
	}
}
