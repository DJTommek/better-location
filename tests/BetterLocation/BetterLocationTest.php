<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeCompactService;
use App\BetterLocation\Service\GeohashService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\WazeService;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use PHPUnit\Framework\TestCase;
use unreal4u\TelegramAPI\Telegram;


final class BetterLocationTest extends TestCase
{
	public function testBasic(): void
	{
		$input = 'https://www.waze.com/ul?ll=50.087451123456789,14.420671123456789';
		$inputLat = 50.087451123456789;
		$inputLon = 14.420671123456789;
		$service = WazeService::class;

		$location = new BetterLocation($input, $inputLat, $inputLon, $service);
		$this->assertSame($location->getInput(), $input);

		$coords = $location->getCoordinates();
		$this->assertSame($inputLat, $coords->getLat());
		$this->assertSame($inputLon, $coords->getLon());

		$this->assertSame([], $location->getDescriptions());
		$this->assertNull($location->getSourceType());
		$this->assertFalse($location->hasAddress());
		$this->assertFalse($location->isRefreshable());

		$this->assertSame(50.087451123456789, $location->getLat());
		$this->assertSame(14.420671123456789, $location->getLon());
		$this->assertSame('50.087451,14.420671', $location->key());
		$this->assertSame('50.087451,14.420671', (string)$location);

		$this->assertSame($location->getLat(), $coords->getLat());
		$this->assertSame($location->getLon(), $coords->getLon());
		$this->assertSame($location->key(), $coords->key());
		$this->assertSame((string)$location, (string)$coords);

		$expectedExport = [
			'lat' => 50.087451123456789,
			'lon' => 14.420671123456789,
			'service' => 'Waze',
		];
		$this->assertSame($expectedExport, $location->export());

		$msgSettings = $this->generateMessageSettings();

		$this->checkMessage($location->generateMessage($msgSettings));
		$this->checkButtons($location->generateDriveButtons($msgSettings));
	}

	private function checkMessage(string $message): void
	{
		$expectedMessage = '<a href="https://www.waze.com/ul?ll=50.087451123456789%2C14.420671123456789">Waze</a> <a href="https://en.mapy.cz/screenshoter?url=https%3A%2F%2Fmapy.cz%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a>  <code>50.087451,14.420671</code> | <code>u2fkbnhu9cxs</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a>

';
		$this->assertSame($expectedMessage, $message);
	}

	/**
	 * @param array<Telegram\Types\Inline\Keyboard\Button> $buttons
	 * @return void
	 */
	private function checkButtons(array $buttons): void
	{
		$this->assertIsArray($buttons);
		foreach ($buttons as $button) {
			$this->assertInstanceOf(Telegram\Types\Inline\Keyboard\Button::class, $button);
		}
		$this->assertCount(2, $buttons);
		$this->assertSame('Mapy.cz 🚗', $buttons[0]->text);
		$this->assertSame('https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451', $buttons[0]->url);

		$this->assertSame('Google 🚗', $buttons[1]->text);
		$this->assertSame('https://www.google.com/maps/dir/?api=1&destination=50.087451%2C14.420671&travelmode=driving&dir_action=navigate', $buttons[1]->url);
	}

	/**
	 * Make sure that all services here are not doing any requests, to limit potential problems.
	 */
	private function generateMessageSettings(): BetterLocationMessageSettings
	{
		return new BetterLocationMessageSettings(
			[BetterLocationService::class, WazeService::class],
			[BetterLocationService::class],
			[MapyCzService::class, GoogleMapsService::class],
			[WGS84DegreeCompactService::class, GeohashService::class],
			MapyCzService::class,
			false,
		);
	}
}
