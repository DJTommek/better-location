<?php declare(strict_types=1);

namespace Tests\TelegramCustomWrapper;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use PHPUnit\Framework\TestCase;

final class ProcessedMessageResultTest extends TestCase
{
	public static function basicProvider(): array
	{
		return [

			// Default settings with one item
			[
				'<a href="">WGS84</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D49.000000%26x%3D14.000000%26source%3Dcoor%26id%3D14.000000%252C49.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>49.000000,14.000000</code>
<a href="https://better-location.palider.cz/49.000000,14.000000" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/49.000000,14.000000?q=49.000000,14.000000" target="_blank">Google</a> | <a href="https://mapy.cz/zakladni?y=49.000000&x=14.000000&source=coor&id=14.000000%2C49.000000" target="_blank">Mapy.cz</a> | <a href="https://duckduckgo.com/?q=49.000000,14.000000&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=49.000000,14.000000" target="_blank">Waze</a> | <a href="https://share.here.com/l/49.000000,14.000000?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=49.000000,14.000000&mlat=49.000000&mlon=14.000000#map=17/49.000000/14.000000" target="_blank">OSM</a>

',
				[
					[
						[
							'text' => 'Google 🚗',
							'url' => 'https://www.google.com/maps/dir/?api=1&destination=49.000000%2C14.000000&travelmode=driving&dir_action=navigate',
						],
						[
							'text' => 'Waze 🚗',
							'url' => 'https://www.waze.com/ul?ll=49.000000,14.000000&navigate=yes',
						],
						[
							'text' => 'HERE 🚗',
							'url' => 'https://share.here.com/r/49.000000,14.000000',
						],
						[
							'text' => 'OsmAnd 🚗',
							'url' => 'https://osmand.net/go.html?lat=49.000000&lon=14.000000',
						],
					],
				],
				(new BetterLocationCollection())->add(new BetterLocation('abcd', 49, 14, WGS84DegreesService::class)),
				new BetterLocationMessageSettings(address: false),
			],

			// Empty collection
			[
				'',
				[],
				new BetterLocationCollection(),
				new BetterLocationMessageSettings(address: false),
			],
		];
	}

	/**
	 * @dataProvider basicProvider
	 */
	public function testBasic(string $expectedText, array $expectedButtons, BetterLocationCollection $collection, BetterLocationMessageSettings $settings): void
	{
		$processedCollection = new ProcessedMessageResult($collection, $settings);
		$processedCollection->process();

		$realText = str_replace("\n", PHP_EOL, $processedCollection->getText(false));
		$this->assertSame($expectedText, $realText);
		$this->assertButtons($expectedButtons, $processedCollection->getButtons());
	}

	private function assertButtons(array $expectedButtons, array $realButtons): void
	{
		$this->assertCount(count($expectedButtons), $realButtons);
		foreach ($expectedButtons as $rowKey => $expectedButtonRow) {
			$realButtonRow = $realButtons[$rowKey];
			$this->assertCount(count($expectedButtonRow), $realButtonRow);
			foreach ($expectedButtonRow as $colKey => $expectedButton) {
				$realButton = $realButtonRow[$colKey];
				assert($realButton instanceof \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button);
				$this->assertSame($expectedButton['text'], $realButton->text);
				$this->assertSame($expectedButton['url'], $realButton->url);
			}
		}
	}
}
