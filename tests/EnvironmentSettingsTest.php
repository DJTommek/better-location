<?php declare(strict_types=1);

namespace Tests;

use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use PHPUnit\Framework\TestCase;

final class EnvironmentSettingsTest extends TestCase
{
	/**
	 * Keep same floating point character even if locale is different
	 */
	public function testLocale(): void
	{
		$localeOriginal = setlocale(LC_NUMERIC, 0); // do not change anything, just save original location to restore it later
		$betterLocationPositive = WGS84DegreesService::processStatic('50.123456,10.123456')->getFirst();
		$betterLocationPositiveNegative = WGS84DegreesService::processStatic('50.123456,-10.123456')->getFirst();
		$betterLocationNegativePositive = WGS84DegreesService::processStatic('-50.123456,10.123456')->getFirst();
		$betterLocationNegative = WGS84DegreesService::processStatic('-50.123456,-10.123456')->getFirst();

		// default formatting (usually from environment settings)
		$this->assertSame('50.123456,10.123456', $betterLocationPositive->key());
		$this->assertSame('50.123456,-10.123456', $betterLocationPositiveNegative->key());
		$this->assertSame('-50.123456,10.123456', $betterLocationNegativePositive->key());
		$this->assertSame('-50.123456,-10.123456', $betterLocationNegative->key());

		setlocale(LC_NUMERIC, 'swedish'); // swedish formatting is using "," instead of "." in floating point
		$this->assertSame('50.123456,10.123456', $betterLocationPositive->key());
		$this->assertSame('50.123456,-10.123456', $betterLocationPositiveNegative->key());
		$this->assertSame('-50.123456,10.123456', $betterLocationNegativePositive->key());
		$this->assertSame('-50.123456,-10.123456', $betterLocationNegative->key());

		setlocale(LC_NUMERIC, 'american'); // american formatting is using "." instead of "," in floating point
		$this->assertSame('50.123456,10.123456', $betterLocationPositive->key());
		$this->assertSame('50.123456,-10.123456', $betterLocationPositiveNegative->key());
		$this->assertSame('-50.123456,10.123456', $betterLocationNegativePositive->key());
		$this->assertSame('-50.123456,-10.123456', $betterLocationNegative->key());

		setlocale(LC_NUMERIC, $localeOriginal); // restore original settings (again default formatting)
		$this->assertSame('50.123456,10.123456', $betterLocationPositive->key()); //
		$this->assertSame('50.123456,-10.123456', $betterLocationPositiveNegative->key());
		$this->assertSame('-50.123456,10.123456', $betterLocationNegativePositive->key());
		$this->assertSame('-50.123456,-10.123456', $betterLocationNegative->key());
	}
}
