<?php declare(strict_types=1);

namespace Tests\Exif;

use App\Exif\ExifException;
use App\Exif\ExifUtils;
use PHPUnit\Framework\TestCase;

final class ExifUtilsTest extends TestCase
{
	public static function exifToDecimalValidProvider(): array
	{
		return [
			'Basic example 1' => [57.6491194, ['57/1', '38/1', '5683/100'], 'N'],
			'Basic example 2' => [-57.6491194, ['57/1', '38/1', '5683/100'], 'S'],
			'Lowercased hemisphere' => [57.6491194, ['57/1', '38/1', '5683/100'], 'n'],
			'One segment 1' => [50, ['50/1'], 'E'],
			'One segment 2' => [-25, ['50/2'], 'W'],
			'Two segments' => [25.01666667, ['50/2', '9/9'], 'N'],
			'Missing hemisphere' => [25.01666667, ['50/2', '9/9'], ''],
			'Positive hemisphere' => [25.01666667, ['50/2', '9/9'], '+'],
			'Negative hemisphere' => [-25.01666667, ['50/2', '9/9'], '-'],
			'More than three segments' => [25.0169444, ['50/2', '9/9', '1/1', '2/2', '3/3', '4/4', '5/5', '6/6', '7/7'], 'N'],
			'Zero as first part of segment' => [-28.0004277, ["28/1", "0/1", "154/100"], 'S'],
		];
	}

	public static function exifToDecimalInvalidProvider(): array
	{
		return [
			'Invalid hemisphere 1' => ['Invalid hemisphere', ['1/1', '1/1', '1/1'], 'b'],
			'Invalid hemisphere 2' => ['Invalid hemisphere', ['1/1', '1/1', '1/1'], 'NN'],
			'One segment is not valid 1' => ['Provided part of coordination "f" is not valid.', ['f', '1/1', '1/1'], 'N'],
			'One segment is not valid 2' => ['Provided part of coordination "9999999" is not valid.', ['1/1', '9999999'], 'N'],
			'One segment contains non-supported characters 1' => ['Provided part of coordination "f1/1" is not valid.', ['f1/1', '1/1', '1/1'], 'N'],
			'One egment contains non-supported characters 2' => ['Provided part of coordination "1/f" is not valid.', ['1/1', '1/f', '1/1'], 'N'],
			'Division by zero' => ['Provided part of coordination "0/0" is not valid.', ['0/0'], 'N'],
			'Division by zero 2' => ['Provided part of coordination "1/0" is not valid.', ['1/0'], 'N'],
		];
	}

	/**
	 * @dataProvider exifToDecimalValidProvider
	 */
	public function testExifToDecimal(
		float $expectedCoord,
		array $exifCoordRaw,
		string $hemisphere,
	): void {
		$resultCoord = ExifUtils::exifToDecimal($exifCoordRaw, $hemisphere);
		$this->assertEqualsWithDelta($expectedCoord, $resultCoord, 0.0000001);
	}

	/**
	 * @dataProvider exifToDecimalInvalidProvider
	 */
	public function testExifToDecimalInvalidProvider(
		string $expectedExceptionMessage,
		array $exifCoordRaw,
		string $hemisphere,
	): void {
		$this->expectException(ExifException::class);
		$this->expectExceptionMessage($expectedExceptionMessage);

		ExifUtils::exifToDecimal($exifCoordRaw, $hemisphere);
	}


	public function testFloatConvert(): void
	{
		// values from oneplus5t-snezka1
		$this->assertSame(50.0, ExifUtils::floatConvert('50/1'));
		$this->assertSame(41.0, ExifUtils::floatConvert('41/1'));
		$this->assertSame(54.0644, ExifUtils::floatConvert('540644/10000'));
		$this->assertSame(15.0, ExifUtils::floatConvert('15/1'));
		$this->assertSame(44.0, ExifUtils::floatConvert('44/1'));
		$this->assertSame(12.2187, ExifUtils::floatConvert('122187/10000'));

		// values from oneplus5t-snezka2
		$this->assertSame(50.0, ExifUtils::floatConvert('50/1'));
		$this->assertSame(41.0, ExifUtils::floatConvert('41/1'));
		$this->assertSame(45.4754, ExifUtils::floatConvert('454754/10000'));
		$this->assertSame(15.0, ExifUtils::floatConvert('15/1'));
		$this->assertSame(44.0, ExifUtils::floatConvert('44/1'));
		$this->assertSame(15.5659, ExifUtils::floatConvert('155659/10000'));

		// values from DSCN0010
		$this->assertSame(43.0, ExifUtils::floatConvert('43/1'));
		$this->assertSame(28.0, ExifUtils::floatConvert('28/1'));
		$this->assertSame(2.814, ExifUtils::floatConvert('281400000/100000000'));
		$this->assertSame(11.0, ExifUtils::floatConvert('11/1'));
		$this->assertSame(53.0, ExifUtils::floatConvert('53/1'));
		$this->assertSame(6.45599999, ExifUtils::floatConvert('645599999/100000000'));
	}
}
