<?php declare(strict_types=1);

namespace Tests\TelegramCustomWrapper;

use App\Config;
use App\TelegramCustomWrapper\TelegramHelper;
use PHPUnit\Framework\TestCase;

final class TelegramHelperTest extends TestCase
{
	public function testGenerateStartLocation(): void
	{
		$this->assertSame(TelegramHelper::generateStartLocation(50.087451, 14.420671), sprintf('https://t.me/%s?start=50087451_14420671', Config::TELEGRAM_BOT_NAME));
		$this->assertSame(TelegramHelper::generateStartLocation(50.1, 14.5), sprintf('https://t.me/%s?start=50100000_14500000', Config::TELEGRAM_BOT_NAME));
		$this->assertSame(TelegramHelper::generateStartLocation(-50.2, 14.6000001), sprintf('https://t.me/%s?start=-50200000_14600000', Config::TELEGRAM_BOT_NAME));
		$this->assertSame(TelegramHelper::generateStartLocation(50.3, -14.7000009), sprintf('https://t.me/%s?start=50300000_-14700000', Config::TELEGRAM_BOT_NAME));
		$this->assertSame(TelegramHelper::generateStartLocation(-50.4, -14.800008), sprintf('https://t.me/%s?start=-50400000_-14800008', Config::TELEGRAM_BOT_NAME));
		$this->assertSame(TelegramHelper::generateStartLocation(1, -2), sprintf('https://t.me/%s?start=1000000_-2000000', Config::TELEGRAM_BOT_NAME));
		$this->assertSame(TelegramHelper::generateStartLocation(-79.56789, 113.123456789), sprintf('https://t.me/%s?start=-79567890_113123456', Config::TELEGRAM_BOT_NAME));
	}
}
