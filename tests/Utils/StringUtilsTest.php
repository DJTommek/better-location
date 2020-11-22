<?php declare(strict_types=1);

use App\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

final class StringUtilsTest extends TestCase
{
	public function testCheckIfValueInHeaderMatchArray(): void
	{
		$this->assertTrue(StringUtils::isGuid('00000000-0000-0000-0000-000000000000'));
		$this->assertTrue(StringUtils::isGuid('00000000-0000-0000-0000-000000000000', true));
		$this->assertTrue(StringUtils::isGuid('00000000-0000-0000-0000-000000000000', false));
		$this->assertTrue(StringUtils::isGuid('ffffffff-ffff-ffff-ffff-ffffffffffff'));
		$this->assertTrue(StringUtils::isGuid('ffffffff-ffff-ffff-ffff-ffffffffffff', true));
		$this->assertTrue(StringUtils::isGuid('ffffffff-ffff-ffff-ffff-ffffffffffff', false));
		$this->assertTrue(StringUtils::isGuid('498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4', true));
		$this->assertTrue(StringUtils::isGuid('498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4', false));

		$this->assertTrue(StringUtils::isGuid('{00000000-0000-0000-0000-000000000000}'));
		$this->assertTrue(StringUtils::isGuid('{00000000-0000-0000-0000-000000000000}', true));
		$this->assertTrue(StringUtils::isGuid('{ffffffff-ffff-ffff-ffff-ffffffffffff}'));
		$this->assertTrue(StringUtils::isGuid('{ffffffff-ffff-ffff-ffff-ffffffffffff}', true));
		$this->assertTrue(StringUtils::isGuid('{498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4}'));
		$this->assertTrue(StringUtils::isGuid('{498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4}', true));

		$this->assertFalse(StringUtils::isGuid('{00000000-0000-0000-0000-000000000000}', false));
		$this->assertFalse(StringUtils::isGuid('{ffffffff-ffff-ffff-ffff-ffffffffffff}', false));
		$this->assertFalse(StringUtils::isGuid('{498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4}', false));

		$this->assertFalse(StringUtils::isGuid('gggggggg-0000-0000-0000-000000000000'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffff-ffff-ffff-gggggggggggg'));

		$this->assertFalse(StringUtils::isGuid('fff-ffff-ffff-ffff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-f-ffff-ffff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffff-fff-ffff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffff-ffff-fff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffff-ffff-ffff-ffffff'));

		$this->assertFalse(StringUtils::isGuid('ffffffffffff-ffff-ffff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffffffff-ffff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffff-ffffffff-ffffffffffff'));
		$this->assertFalse(StringUtils::isGuid('ffffffff-ffff-ffff-ffffffffffffffff'));
	}
}
