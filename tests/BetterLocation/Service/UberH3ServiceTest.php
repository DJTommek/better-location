<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\UberH3Service;

final class UberH3ServiceTest extends AbstractServiceTestCase
{
	#[\Override]
	protected function getServiceClass(): string
	{
		return UberH3Service::class;
	}

	#[\Override]
	protected function getShareLinks(): array
	{
		return [
			'https://h3geo.org/#hex=8d1e3541e32a53f',
			'https://h3geo.org/#hex=8d1e3540a6e007f',
			'https://h3geo.org/#hex=8dd0f5bae835dbf',
			'https://h3geo.org/#hex=8d18184e5c6957f',
			'https://h3geo.org/#hex=8ddcf42ea12a4ff',
		];
	}

	#[\Override]
	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testShareText(): void
	{
		$this->assertSame('8d1e3541e32a53f', UberH3Service::getShareText(50.087451, 14.420671));
	}

	public static function isValidProvider(): array
	{
		return [
			[true, '8d1e3541e32aa7f'],
			[true, '8a1e3541e32ffff'],
			[true, '831e30fffffffff'],
			[true, '81cfbffffffffff'],
			[true, '8D1E3541E32AA7F'],
			[true, 'https://h3geo.org/#hex=8d1e3541e32aa7f'],
			[true, 'http://h3geo.org/#hex=8a1e3541e32ffff'],
			[true, 'https://h3geo.org/#hex=81cfbffffffffff'],

			[false, '8d1e3541e32aa7g'],
			[false, '8d1e3541e32aa7'],
			[false, '81087ffffffffff'], // pentagon with deleted k-axes digit
			[false, '08d1e3541e32aa7f'], // valid H3 but not canonical, too similar to random hex strings
			[false, 'https://h3geo.org/'],
			[false, 'https://h3geo.org/#hex='],
			[false, 'https://h3geo.org/#hex=8d1e3541e32aa7g'],
			[false, 'https://example.com/#hex=8d1e3541e32aa7f'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$this->assertServiceIsValid(new UberH3Service(), $input, $expectedIsValid);
	}

	public static function processProvider(): array
	{
		return [
			[50.087648021, 14.421216248, '8d1e3541e32aa7f', 'Resolution 13, average cell area 43.9 m².'],
			[50.087910736, 14.421251456, '8a1e3541e32ffff', 'Resolution 10, average cell area 15 048 m².'],
			[49.495205812, 14.102299669, '831e30fffffffff', 'Resolution 3, average cell area 12 393 km².'],
			[-48.104103209, -69.146022106, '81cfbffffffffff', 'Resolution 1, average cell area 609 788 km².'],
			[50.087648021, 14.421216248, '8D1E3541E32AA7F', 'Resolution 13, average cell area 43.9 m².'],
			[50.087648021, 14.421216248, 'https://h3geo.org/#hex=8d1e3541e32aa7f', 'Resolution 13, average cell area 43.9 m².'],
			[-48.104103209, -69.146022106, 'https://h3geo.org/#hex=81cfbffffffffff', 'Resolution 1, average cell area 609 788 km².'],
		];
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input, string $expectedDescription): void
	{
		$location = $this->assertServiceLocation(new UberH3Service(), $input, $expectedLat, $expectedLon);
		$this->assertSame($expectedDescription, $location->getDescriptions()[0]->content);
	}

	public function testFindInText(): void
	{
		$collection = UberH3Service::findInText('Meet me at 8d1e3541e32aa7f or near 81CFBFFFFFFFFFF, not at 8d1e3541e32aa7g or 98d1e3541e32aa7f.');
		$this->assertCount(2, $collection);
		$this->assertCoordsWithDelta(50.087648021, 14.421216248, $collection[0]);
		$this->assertCoordsWithDelta(-48.104103209, -69.146022106, $collection[1]);

		$this->assertCount(0, UberH3Service::findInText('some random text'));
		$this->assertCount(0, UberH3Service::findInText('commit 55abfd3e8d1e3541e32aa7f00'));
	}
}
