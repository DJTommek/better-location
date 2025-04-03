<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\Address\Address;
use App\Address\Country;
use App\BetterLocation\MessageGenerator;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\ServicesManager;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use DJTommek\Coordinates\Coordinates;
use PHPUnit\Framework\TestCase;
use unreal4u\TelegramAPI\Telegram;

final class MessageGeneratorTest extends TestCase
{
	private readonly MessageGenerator $generator;

	protected function setUp(): void
	{
		parent::setUp();

		$servicesManager = new ServicesManager();
		$this->generator = new MessageGenerator($servicesManager);
	}

	public function testBasic(): void
	{
		$coords = new Coordinates(50.087451, 14.420671);
		$settings = new BetterLocationMessageSettings();

		// Minimum requirements, everything is by default
		$expected1 = 'Some prefix <a href="https://mapy.com/screenshoter?url=https%3A%2F%2Fmapy.com%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087451,14.420671</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671" target="_blank">Google</a> | <a href="https://mapy.com/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a> | <a href="https://share.here.com/l/50.087451,14.420671?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671" target="_blank">OSM</a>

';
		$result1 = $this->generator->generate($coords, $settings, 'Some prefix');
		$this->assertSame($expected1, $result1);

		// Tests with showing address
		$settings->showAddress(true);

		// Address is allowed, but not available
		$expected2 = $expected1;
		$result2 = $this->generator->generate($coords, $settings, 'Some prefix');
		$this->assertSame($expected2, $result2);

		// Address is allowed but only address without country is available
		$expected3 = 'Some <a href="https://tomas.palider.cz/">prefix</a> <a href="https://mapy.com/screenshoter?url=https%3A%2F%2Fmapy.com%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087451,14.420671</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671" target="_blank">Google</a> | <a href="https://mapy.com/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a> | <a href="https://share.here.com/l/50.087451,14.420671?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671" target="_blank">OSM</a>
Some nice address here

';
		$address = new Address('Some nice address here');
		$result3 = $this->generator->generate($coords, $settings, 'Some <a href="https://tomas.palider.cz/">prefix</a>', address: $address);
		$this->assertSame($expected3, $result3);

		// Address is allowed and full address including country is available, emoji is generated
		$expected4 = 'Some prefix <a href="https://mapy.com/screenshoter?url=https%3A%2F%2Fmapy.com%2Fzakladni%3Fy%3D50.087451%26x%3D14.420671%26source%3Dcoor%26id%3D14.420671%252C50.087451%26p%3D3%26l%3D0" target="_blank">🗺</a> <code>50.087451,14.420671</code>
<a href="https://better-location.palider.cz/50.087451,14.420671" target="_blank">BetterLocation</a> | <a href="https://www.google.com/maps/place/50.087451,14.420671?q=50.087451,14.420671" target="_blank">Google</a> | <a href="https://mapy.com/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451" target="_blank">Mapy.com</a> | <a href="https://duckduckgo.com/?q=50.087451,14.420671&iaxm=maps" target="_blank">DDG</a> | <a href="https://www.waze.com/ul?ll=50.087451,14.420671" target="_blank">Waze</a> | <a href="https://share.here.com/l/50.087451,14.420671?p=yes" target="_blank">HERE</a> | <a href="https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671" target="_blank">OSM</a>
🇨🇿 Some nice address here

';
		$address = new Address('Some nice address here', new Country('CZ', 'Czechia'));
		$result4 = $this->generator->generate($coords, $settings, 'Some prefix', address: $address);
		$this->assertSame($expected4, $result4);
	}

	public function testMinimal(): void
	{
		$coords = new Coordinates(-1, -2);
		$settings = new BetterLocationMessageSettings([], [], [], [], MapyCzService::class);

		$expected = 'Some prefix <a href="https://mapy.com/screenshoter?url=https%3A%2F%2Fmapy.com%2Fzakladni%3Fy%3D-1.000000%26x%3D-2.000000%26source%3Dcoor%26id%3D-2.000000%252C-1.000000%26p%3D3%26l%3D0" target="_blank">🗺</a> 


';
		$result = $this->generator->generate($coords, $settings, 'Some prefix');

		$this->assertSame($expected, $result);
	}
}
