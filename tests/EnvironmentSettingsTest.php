<?php declare(strict_types=1);

use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/bootstrap.php';

final class EnvironmentSettingsTest extends TestCase
{
	/**
	 * Keep same floating point character even if locale is different
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testLocale(): void
	{
		$localeOriginal = setlocale(LC_NUMERIC, 0); // do not change anything, just save original location to restore it later
		$betterLocation = WGS84DegreesService::parseCoords('50.123456,10.123456');

		$this->assertEquals('50.123456,10.123456', $betterLocation->__toString()); // default formatting (usually from environment settings)

		setlocale(LC_NUMERIC, 'swedish'); // swedish formatting is using "," instead of "." in floating point
		$this->assertEquals('50.123456,10.123456', $betterLocation->__toString());

		setlocale(LC_NUMERIC, 'american'); // american formatting is using "." instead of "," in floating point
		$this->assertEquals('50.123456,10.123456', $betterLocation->__toString());

		setlocale(LC_NUMERIC, $localeOriginal); // restore original settings
		$this->assertEquals('50.123456,10.123456', $betterLocation->__toString()); // again default formatting
	}
}
