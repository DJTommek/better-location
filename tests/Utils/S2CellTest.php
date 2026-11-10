<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\S2Cell;
use PHPUnit\Framework\TestCase;

/**
 * Expected values were verified against s2sphere (Python port of the official Google S2 library).
 */
final class S2CellTest extends TestCase
{
	public static function tokenProvider(): array
	{
		return [
			['470b94e93ddc8715', '5119349130922854165', 50.087451, 14.420671, 30],
			['470b9334f90f27ab', '5119347257162803115', 50.1, 14.5, 30],
			['b64bef13fffcfe89', '13135855607334370953', -50.2, 14.6000001, 30], // face 5, highest bit set
			['49b4058f1c6d7d77', '5310875972792057207', 50.3, -14.7000009, 30],
			['b8f40280319349bb', '13327279946906945979', -50.4, -14.800008, 30],
			['5', '5764607523034234880', 50.087451, 14.420671, 0],
		];
	}

	/**
	 * @dataProvider tokenProvider
	 */
	public function testToken(string $expectedToken, string $expectedId, float $lat, float $lon, int $level): void
	{
		$cell = S2Cell::fromLatLon($lat, $lon, $level);
		$this->assertSame($expectedToken, $cell->getToken());
		$this->assertSame($expectedId, sprintf('%u', $cell->getId()));
	}

	public function testTokenAllLevels(): void
	{
		// Every level adds two bits (half of hex character), level marker bit is moving accordingly
		$tokens = [];
		for ($level = 0; $level <= S2Cell::MAX_LEVEL; $level++) {
			$tokens[$level] = S2Cell::fromLatLon(50.087451, 14.420671, $level)->getToken();
		}
		$this->assertSame('5', $tokens[0]);
		$this->assertSame('44', $tokens[1]);
		$this->assertSame('470b94e94', $tokens[15]);
		$this->assertSame('470b94e93ddc8715', $tokens[30]);
		for ($level = 1; $level <= S2Cell::MAX_LEVEL; $level++) {
			$this->assertSame(substr($tokens[30], 0, intdiv($level, 2)), substr($tokens[$level], 0, intdiv($level, 2)));
		}
	}

	public function testInvalid(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		S2Cell::fromFaceIJ(6, 0, 0, 0);
	}
}
