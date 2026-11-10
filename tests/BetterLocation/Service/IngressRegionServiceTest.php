<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\IngressRegionService;

final class IngressRegionServiceTest extends AbstractServiceTestCase
{
	#[\Override]
	protected function getServiceClass(): string
	{
		return IngressRegionService::class;
	}

	#[\Override]
	protected function getShareLinks(): array
	{
		return [];
	}

	#[\Override]
	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testShareText(): void
	{
		$this->assertSame('NR02-FOXTROT-05', IngressRegionService::getShareText(50.087451, 14.420671));
		$this->assertSame('AM01-LIMA-01', IngressRegionService::getShareText(40.7128, -74.006));
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'NR02-FOXTROT-05'],
			[true, 'nr02-foxtrot-05'],
			[true, 'NR2-FOXTROT-5'],
			[true, 'NR02FOXTROT05'],
			[true, 'NR 02 FOXTROT 05'],
			[true, 'AM01-LIMA-01'],
			[true, 'PA08-MIKE-15'],

			[false, 'NR02-FOXTROT'],
			[false, 'NR02-FOXTROT-16'],
			[false, 'NR17-FOXTROT-05'],
			[false, 'NR02-INDIA-05'],
			[false, 'Meet me in NR02-FOXTROT-05'],
			[false, 'https://example.com/NR02-FOXTROT-05'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$this->assertServiceIsValid(new IngressRegionService(), $input, $expectedIsValid);
	}

	public static function processProvider(): array
	{
		return [
			[
				49.859109764, 13.761333119, 'NR02-FOXTROT-05',
				'NR02-FOXTROT-05', 'Area of ~18 520 km².',
			],
			[
				49.859109764, 13.761333119, 'nr 2 foxtrot 5',
				'NR02-FOXTROT-05', 'Area of ~18 520 km².',
			],
			[
				40.827706513, -74.217932561, 'AM01-LIMA-01',
				'AM01-LIMA-01', 'Area of ~17 768 km².',
			],
			[
				-17.289387991, 179.398528316, 'PA08-MIKE-15',
				'PA08-MIKE-15', 'Area of ~21 651 km².',
			],
			[
				89.149421831, 45.0, 'NR08-HOTEL-10',
				'NR08-HOTEL-10', 'Area of ~18 164 km².',
			],
		];
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcess(
		float $expectedLat,
		float $expectedLon,
		string $input,
		string $expectedName,
		string $expectedDescription,
	): void {
		$location = $this->assertServiceLocation(new IngressRegionService(), $input, $expectedLat, $expectedLon);
		$this->assertStringContainsString('<code>' . $expectedName . '</code>', $location->getPrefixMessage());
		$this->assertSame($expectedDescription, $location->getDescriptions()[0]->content);
	}

	public function testFindInText(): void
	{
		$collection = IngressRegionService::findInText('Anomaly in NR02-FOXTROT-05, next one in am01-lima-01. Not NR02-FOXTROT-16, XNR02-FOXTROT-05 or NR02-FOXTROT-05-1.');
		$this->assertCount(2, $collection);
		$this->assertCoordsWithDelta(49.859109764, 13.761333119, $collection[0]);
		$this->assertCoordsWithDelta(40.827706513, -74.217932561, $collection[1]);

		$this->assertCount(0, IngressRegionService::findInText('some random text'));
		$this->assertCount(0, IngressRegionService::findInText('NR02-FOXTROT and NR2FOXTROT5'));
	}
}
