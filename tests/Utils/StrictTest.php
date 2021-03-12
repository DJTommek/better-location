<?php declare(strict_types=1);

namespace Utils;

use App\Utils\Strict;
use PHPUnit\Framework\TestCase;

final class StrictTest extends TestCase
{
	public function testIsIntTrue(): void
	{
		$this->assertTrue(Strict::isInt(0));
		$this->assertTrue(Strict::isInt(1));
		$this->assertTrue(Strict::isInt(-1));
		$this->assertTrue(Strict::isInt(10));
		$this->assertTrue(Strict::isInt(-10));

		$this->assertTrue(Strict::isInt('0'));
		$this->assertTrue(Strict::isInt('1'));
		$this->assertTrue(Strict::isInt('-1'));
		$this->assertTrue(Strict::isInt('10'));
		$this->assertTrue(Strict::isInt('-10'));

		$this->assertTrue(Strict::isInt(99999999999));
		$this->assertTrue(Strict::isInt(-99999999999));
	}

	public function testIsIntFalse(): void
	{
		$this->assertFalse(Strict::isInt('a'));
		$this->assertFalse(Strict::isInt('1a'));
		$this->assertFalse(Strict::isInt('1 a'));
		$this->assertFalse(Strict::isInt('a1'));
		$this->assertFalse(Strict::isInt('+1a'));
		$this->assertFalse(Strict::isInt('-1a'));
		$this->assertFalse(Strict::isInt('+1'));
		$this->assertFalse(Strict::isInt('- 1'));
	}

	public function testIsFloatFalse(): void
	{
		$this->assertFalse(Strict::isFloat('a', false));
		$this->assertFalse(Strict::isFloat('1a', false));
		$this->assertFalse(Strict::isFloat('1 a', false));
		$this->assertFalse(Strict::isFloat('a1', false));
		$this->assertFalse(Strict::isFloat('+1a', false));
		$this->assertFalse(Strict::isFloat('-1a', false));
		$this->assertFalse(Strict::isFloat('+1', false));
		$this->assertFalse(Strict::isFloat('- 1', false));

		$this->assertFalse(Strict::isFloat(0, false));
		$this->assertFalse(Strict::isFloat(1, false));
		$this->assertFalse(Strict::isFloat(-1, false));
		$this->assertFalse(Strict::isFloat(10, false));
		$this->assertFalse(Strict::isFloat(-10, false));

		$this->assertFalse(Strict::isFloat('0', false));
		$this->assertFalse(Strict::isFloat('1', false));
		$this->assertFalse(Strict::isFloat('-1', false));
		$this->assertFalse(Strict::isFloat('10', false));
		$this->assertFalse(Strict::isFloat('-10', false));

		$this->assertFalse(Strict::isFloat(99999999999, false));
		$this->assertFalse(Strict::isFloat(-99999999999, false));
	}

	public function testIsFloatAllowIntTrue(): void
	{
		$this->assertTrue(Strict::isFloat(0, true));
		$this->assertTrue(Strict::isFloat(1, true));
		$this->assertTrue(Strict::isFloat(-1, true));
		$this->assertTrue(Strict::isFloat(10, true));
		$this->assertTrue(Strict::isFloat(-10, true));

		$this->assertTrue(Strict::isFloat('0', true));
		$this->assertTrue(Strict::isFloat('1', true));
		$this->assertTrue(Strict::isFloat('-1', true));
		$this->assertTrue(Strict::isFloat('10', true));
		$this->assertTrue(Strict::isFloat('-10', true));

		$this->assertTrue(Strict::isFloat(99999999999, true));
		$this->assertTrue(Strict::isFloat(-99999999999, true));
	}

	public function testIsFloatAllowIntFalse(): void
	{
		$this->assertFalse(Strict::isFloat('a', true));
		$this->assertFalse(Strict::isFloat('1a', true));
		$this->assertFalse(Strict::isFloat('1 a', true));
		$this->assertFalse(Strict::isFloat('a1', true));
		$this->assertFalse(Strict::isFloat('+1a', true));
		$this->assertFalse(Strict::isFloat('-1a', true));
		$this->assertFalse(Strict::isFloat('+1', true));
		$this->assertFalse(Strict::isFloat('- 1', true));
	}
}
