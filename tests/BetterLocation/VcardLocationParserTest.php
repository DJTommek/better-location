<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\GooglePlaceApi;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\VcardLocationParser;
use App\Config;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;
use Tests\LocationTrait;

final class VcardLocationParserTest extends TestCase
{
	use LocationTrait;

	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
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
			[
				__DIR__ . '/fixtures/contact3-invalid-address.vcf',
				[],
			],
		];
	}

	/**
	 * @dataProvider basicProvider
	 * @group request
	 */
	public function testBasicReal(string $filePath, array $expectedResults): void
	{
		if (!Config::isGooglePlaceApi()) {
			$this->markTestSkipped('Google Place API key is missing.');
		}

		$googlePlaceApi = new GooglePlaceApi($this->httpTestClients->realRequestor, Config::GOOGLE_PLACE_API_KEY);
		$this->testBasic($googlePlaceApi, $filePath, $expectedResults);
	}

	/**
	 * @dataProvider basicProvider
	 */
	public function testBasicOffline(string $filePath, array $expectedResults): void
	{
		$googlePlaceApi = new GooglePlaceApi($this->httpTestClients->offlineRequestor, '');
		$this->testBasic($googlePlaceApi, $filePath, $expectedResults);
	}

	private function testBasic(GooglePlaceApi $googlePlaceApi, string $filePath, array $expectedResults): void
	{
		$parser = new VcardLocationParser(file_get_contents($filePath), $googlePlaceApi);
		$parser->process();

		$this->assertCollection($parser->getCollection(), $expectedResults);
	}

	public function testUnprocessedYet(): void
	{
		$googlePlaceApi = new GooglePlaceApi($this->httpTestClients->mockedRequestor, '');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Run App\BetterLocation\VcardLocationParser::process() first.');
		$parser = new VcardLocationParser('anything here', $googlePlaceApi);
		$parser->getCollection();
	}
}
