<?php declare(strict_types=1);

use App\Utils\Formatter;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
	public function testSeconds(): void
	{
		$this->assertSame('0s', Formatter::seconds(0));
		$this->assertSame('1s', Formatter::seconds(1));
		$this->assertSame('9s', Formatter::seconds(9));
		$this->assertSame('10s', Formatter::seconds(10));
		$this->assertSame('59s', Formatter::seconds(59));
		$this->assertSame('1m', Formatter::seconds(60));
		$this->assertSame('1m 1s', Formatter::seconds(61));

		$this->assertSame('16d 1h 23s', Formatter::seconds(1386023));
		$this->assertSame('16d 1h 3m 23s', Formatter::seconds(1386203));
		$this->assertSame('32d 2h 6m 46s', Formatter::seconds(2772406));
	}

	public function testSecondsShort(): void
	{
		$this->assertSame('0s', Formatter::seconds(0, true));
		$this->assertSame('1m', Formatter::seconds(60, true));
		$this->assertSame('1m', Formatter::seconds(61, true));

		$this->assertSame('16d', Formatter::seconds(1386023, true));
		$this->assertSame('16d', Formatter::seconds(1386203, true));
		$this->assertSame('32d', Formatter::seconds(2772406, true));
	}

	public final function testSecondsInvalid1(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Input must be higher or equal zero.');
		Formatter::seconds(-1);
	}

	public function testDistance(): void
	{
		$this->assertSame('< 1 m', Formatter::distance(0));
		$this->assertSame('< 1 m', Formatter::distance(0.0));
		$this->assertSame('< 1 m', Formatter::distance(0.1));
		$this->assertSame('< 1 m', Formatter::distance(0.999999));

		$this->assertSame('1.0 m', Formatter::distance(1));
		$this->assertSame('1.0 m', Formatter::distance(1.0000000001));
		$this->assertSame('1.1 m', Formatter::distance(1.1));
		$this->assertSame('9.0 m', Formatter::distance(9));
		$this->assertSame('10.0 m', Formatter::distance(9.999));

		$this->assertSame('10 m', Formatter::distance(10));
		$this->assertSame('11 m', Formatter::distance(11));
		$this->assertSame('999 m', Formatter::distance(999));
		$this->assertSame('999 m', Formatter::distance(999.999));

		$this->assertSame('1.00 km', Formatter::distance(1000));
		$this->assertSame('1.00 km', Formatter::distance(1000.1));
		$this->assertSame('1.00 km', Formatter::distance(1001));
		$this->assertSame('1.01 km', Formatter::distance(1009));
		$this->assertSame('1.01 km', Formatter::distance(1010));
		$this->assertSame('1.10 km', Formatter::distance(1099));
		$this->assertSame('5.55 km', Formatter::distance(5555));
		$this->assertSame('9.99 km', Formatter::distance(9990));
		$this->assertSame('10.00 km', Formatter::distance(9999));
		$this->assertSame('10.00 km', Formatter::distance(9999.9999));

		$this->assertSame('10.0 km', Formatter::distance(10_000));
		$this->assertSame('10.9 km', Formatter::distance(10_900));
		$this->assertSame('55.1 km', Formatter::distance(55_123));
		$this->assertSame('55.6 km', Formatter::distance(55_555));
		$this->assertSame('55.6 km', Formatter::distance(55_555.9999));

		$this->assertSame('100.0 km', Formatter::distance(99_999));
		$this->assertSame('100 km', Formatter::distance(100_000));
		$this->assertSame('101 km', Formatter::distance(100_900));
		$this->assertSame('101 km', Formatter::distance(101_000));
		$this->assertSame('102 km', Formatter::distance(101_900));
		$this->assertSame('555 km', Formatter::distance(554_999));
		$this->assertSame('5555 km', Formatter::distance(5_554_999));
	}

	/**
	 * @TODO Code should be updated to work with these commented tests
	 */
	public function testDistanceToFix(): void
	{
		$this->assertSame('1.0 m', Formatter::distance(1));
		// $this->assertSame('1.0 m', Formatter::distance(1));

		$this->assertSame('1.0 m', Formatter::distance(1.0000000001));
		// $this->assertSame('1 m', Formatter::distance(1.0000000001));

		$this->assertSame('9.0 m', Formatter::distance(9));
		// $this->assertSame('9 m', Formatter::distance(9));

		$this->assertSame('10.0 m', Formatter::distance(9.999));
		// $this->assertSame('10 m', Formatter::distance(9.999));
	}

	public final function testDistanceInvalid1(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Distance must be higher or equal zero.');
		Formatter::distance(-1);
	}
}
