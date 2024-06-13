<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\SumavaCzService;

/**
 * Methods containing "original" are URLs, that were used before 2022-06-20.
 * Methods containing "new" are URLs, that were used after 2022-06-20.
 */
final class SumavaCzServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return SumavaCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidOriginalProvider(): array
	{
		return [
			// Place
			[true, 'http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/'],
			[true, 'https://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/'],
			[true, 'http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d'],
			[true, 'http://www.sumava.cz/objekt_az/765'],
			[true, 'http://www.sumava.cz/objekt_az/146-infocentrum-albtn/'],

			// Accomodation
			[true, 'http://www.sumava.cz/objekt/2/'],
			[true, 'https://www.sumava.cz/objekt/2/'],
			[true, 'http://www.sumava.cz/objekt/2'],

			// Companies
			[true, 'http://www.sumava.cz/firma/565-aldi-sd-bodenmais-d/'],
			[true, 'https://www.sumava.cz/firma/565-aldi-sd-bodenmais-d'],
			[true, 'http://www.sumava.cz/firma/565'],

			// Gallery
			[true, 'http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/'],
			[true, 'https://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/'],
			[true, 'http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice'],
			[true, 'http://www.sumava.cz/galerie_sekce/4711'],

			// Invalid
			[false, 'some invalid url'],
			[false, 'http://www.sumava.cz/mapa-stranek/'],
			[false, 'http://www.sumava.cz/rozcestnik-kategorie/3-infocentra/'],
			[false, 'http://www.sumava.cz/galerie/'],
			[false, 'http://www.sumava.cz/blabla/objekt_az/765-stezka-v-korunch-d/'],
			[false, 'http://www.sumava.cz/foooo/objekt/2/'],
			[false, 'http://www.sumava.cz/palider/firma/565-aldi-sd-bodenmais-d/'],
			[false, 'http://www.sumava.cz/tomas/galerie_sekce/4711-zmeck-park-hrdek-u-suice/'],
		];
	}

	public static function isValidNewProvider(): array
	{
		return [
			// Place
			[true, 'http://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'],
			[true, 'https://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'],
			[true, 'http://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d'],
			[true, 'http://www.sumava.cz/objekt_az/765'],
			[true, 'http://www.sumava.cz/objekt_az/146-infocentrum-albtn/'],

			// Accomodation
			[true, 'http://www.sumava.cz/ubytovani/sumavska-roubenka/'],

			// Companies
			[true, 'http://www.sumava.cz/firmy/obchody/smisene/aldi-sud-bodenmais-d/'],
			[true, 'http://www.sumava.cz/firmy/obchody/cerpaci-stanice/cerpaci-stanice-shell-freyung-d/'],

			// Gallery
			[true, 'http://www.sumava.cz/galerie/mesta-a-obce/mesta-a-obce/tedrazice/'],
			[true, 'http://www.sumava.cz/galerie/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'],

			// Invalid
			[false, 'some invalid url'],
		];
	}

	public static function processPlaceOriginalProvider(): array
	{
		return [
			[[[48.890900, 13.485400, 'Place']], 'http://www.sumava.cz/objekt_az/765-stezka-v-korunch-d/'],
			[[[49.121800, 13.209300, 'Place']], 'http://www.sumava.cz/objekt_az/146-infocentrum-albtn/'],
		];
	}

	public static function processPlaceNewProvider(): array
	{
		return [
			[[[48.890900, 13.485400, 'Place']], 'http://www.sumava.cz/rozcestnik/priroda/vrcholy-rozhledny/stezka-v-korunach-d/'],
			[[[49.121800, 13.209300, 'Place']], 'http://www.sumava.cz/rozcestnik/instituce/infocentra/infocentrum-alzbetin/'],
		];
	}


	public static function processAccomodationOriginalProvider(): array
	{
		return [
			[[[49.170100, 13.454600, 'Accomodation']], 'http://www.sumava.cz/objekt/2/'],
			[[[48.670000, 14.162900, 'Accomodation']], 'http://www.sumava.cz/objekt/39'],
		];
	}

	public static function processAccomodationNewProvider(): array
	{
		return [
			[[[49.170100, 13.454600, 'Accomodation']], 'http://www.sumava.cz/ubytovani/apartmany-stara-posta-hartmanice/'],
			[[[48.670000, 14.162900, 'Accomodation']], 'http://www.sumava.cz/ubytovani/rekreace-na-lipne/'],
		];
	}

	public static function processCompaniesOriginalProvider(): array
	{
		return [
			[[[49.071600, 13.092100, 'Company']], 'http://www.sumava.cz/firma/565-aldi-sd-bodenmais-d/'],
			[[[49.063400, 13.104300, 'Company']], 'http://www.sumava.cz/firma/805-erpac-stanice-shell-bodenmais-d/'],
		];
	}

	public static function processCompaniesNewProvider(): array
	{
		return [
			[[[49.071600, 13.092100, 'Company']], 'http://www.sumava.cz/firmy/obchody/smisene/aldi-sud-bodenmais-d/'],
			[[[49.063400, 13.104300, 'Company']], 'http://www.sumava.cz/firmy/obchody/cerpaci-stanice/cerpaci-stanice-shell-bodenmais-d/'],
		];
	}

	/**
	 * Type is place because gallery is just original source
	 */
	public static function processGalleryOriginalProvider(): array
	{
		return [
			[[[49.265100, 13.520600, 'Place']], 'http://www.sumava.cz/galerie_sekce/4710-tedraice/'],

			[
				[
					[49.261300, 13.498500, 'Place'],
					[49.261100, 13.498100, 'Place'],
					[49.260500, 13.497900, 'Place'],
				],
				'http://www.sumava.cz/galerie_sekce/4711-zmeck-park-hrdek-u-suice/',
			],
		];
	}

	/**
	 * Type is place because gallery is just original source
	 */
	public static function processGalleryNewProvider(): array
	{
		return [
			[[[49.265100, 13.520600, 'Place']], 'http://www.sumava.cz/galerie/mesta-a-obce/mesta-a-obce/tedrazice/'],

			[
				[
					[49.261300, 13.498500, 'Place'],
					[49.261100, 13.498100, 'Place'],
					[49.260500, 13.497900, 'Place'],
				],
				'http://www.sumava.cz/galerie/zabava/odpocinek/zamecky-park-hradek-u-susice/',
			],
		];
	}

	/**
	 * Gallery is not linked to any specific place
	 */
	public static function galleryNotRelatedProviderOriginal(): array
	{
		return [
			['http://www.sumava.cz/galerie_sekce/4688-strovsk-skotsk-horalsk-hry-2020/'],
		];
	}

	/**
	 * Gallery is not linked to any specific place
	 */
	public static function galleryNotRelatedProviderNew(): array
	{
		return [
			['http://www.sumava.cz/galerie/kultura-a-pamatky/akce/strazovske-skotske-horalske-hry-2020/'],
		];
	}

	public static function invalidIdProvider(): array
	{
		return [
			['https://www.sumava.cz/objekt_az/99999999'],
			['https://www.sumava.cz/objekt/99999999'],
			['https://www.sumava.cz/galerie_sekce/9999999'],
		];
	}

	/**
	 * @dataProvider isValidOriginalProvider
	 * @dataProvider isValidNewProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new SumavaCzService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processPlaceOriginalProvider
	 * @dataProvider processPlaceNewProvider
	 * @dataProvider processAccomodationOriginalProvider
	 * @dataProvider processAccomodationNewProvider
	 * @dataProvider processCompaniesOriginalProvider
	 * @dataProvider processCompaniesNewProvider
	 * @dataProvider processGalleryOriginalProvider
	 * @dataProvider processGalleryNewProvider
	 * @group request
	 */
	public function testProcess(array $expectedResults, string $input): void
	{
		$service = new SumavaCzService();
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();

		$collection = $service->getCollection();
		$this->assertCount(count($expectedResults), $collection);

		foreach ($expectedResults as $key => $expectedResult) {
			[$expectedLat, $expectedLon, $expectedSourceType] = $expectedResult;
			$location = $collection[$key];
			$this->assertSame($expectedLat, $location->getLat());
			$this->assertSame($expectedLon, $location->getLon());
			$this->assertSame($expectedSourceType, $location->getSourceType());
		}
	}

	/**
	 * @group request
	 * @dataProvider invalidIdProvider
	 * @dataProvider galleryNotRelatedProviderOriginal
	 * @dataProvider galleryNotRelatedProviderNew
	 */
	public function testInvalidId(string $input): void
	{
		$service = new SumavaCzService();
		$this->assertServiceNoLocation($service, $input);
	}
}
