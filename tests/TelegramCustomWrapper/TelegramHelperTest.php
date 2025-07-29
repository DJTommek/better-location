<?php declare(strict_types=1);

namespace Tests\TelegramCustomWrapper;

use App\Config;
use App\TelegramCustomWrapper\TelegramHelper;
use PHPUnit\Framework\TestCase;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User;

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

	/**
	 * @return array<string,array{bool, array<mixed>}>
	 */
	public static function isMarkupEmptyProvider(): array
	{
		$button = ['text' => 'hello!'];

		return [
			'completely-empty' => [true, []],
			'keyboard-empty' => [true, ['inline_keyboard' => []]],
			'keyboard-empty-row-empty-button' => [true, ['inline_keyboard' => [[]]]],
			'keyboard-empty-row-empty-buttons' => [true, ['inline_keyboard' => [[], []]]],

			'keyboard-row-button' => [false, ['inline_keyboard' => [[$button]]]],
			'keyboard-row-buttons' => [false, ['inline_keyboard' => [[$button, $button]]]],
			'keyboard-rows-button' => [false, ['inline_keyboard' => [[$button], [$button]]]],
			'keyboard-rows-buttons' => [false, ['inline_keyboard' => [[$button], [$button, $button]]]],
		];
	}

	/**
	 * @dataProvider isMarkupEmptyProvider
	 * @param array<mixed> $markupRaw
	 */
	public function testIsMarkupEmpty(bool $expect, array $markupRaw): void
	{
		$markup = new Markup($markupRaw);
		$result = TelegramHelper::isMarkupEmpty($markup);

		$this->assertSame($expect, $result);
	}

	public function messageUrlEntitiesProvider(): array
	{
		return [
			['Hello ||||||||||||| here!', 'Hello https://t.me/ here!', [['offset' => 6, 'length' => 13, 'type' => 'url']]],
			// URL is not marked as URL via entities
			['Hello https://t.me/ here!', 'Hello https://t.me/ here!', []],
			// Valid IP address
			['||||||||||||||||||||||', 'http://123.123.222.111', [['offset' => 0, 'length' => 22, 'type' => 'url']]],
		];

	}

	/**
	 * @dataProvider messageUrlEntitiesProvider
	 */
	public function testGetMessageWithoutUrls(string $expectedMessage, string $inputMessage, array $inputEntitiesRaw): void
	{
		$inputEntities = array_map(fn(array $entityRaw) => new MessageEntity($entityRaw), $inputEntitiesRaw);
		$result = TelegramHelper::getMessageWithoutUrls($inputMessage, $inputEntities);
		$this->assertSame($expectedMessage, $result);
	}

	public function testIsMigrate(): void
	{
		$updateChatMigrateFrom = self::fromFile(__DIR__ . '/fixtures/chat_migrate_from.json');
		$updateChatMigrateTo = self::fromFile(__DIR__ . '/fixtures/chat_migrate_to.json');
		$updateChatSettingsButtonClickEnableAddress = self::fromFile(__DIR__ . '/fixtures/settings_button_click_enable_address.json');

		$this->assertTrue(TelegramHelper::isChatMigrateFrom($updateChatMigrateFrom));
		$this->assertFalse(TelegramHelper::isChatMigrateFrom($updateChatMigrateTo));
		$this->assertFalse(TelegramHelper::isChatMigrateFrom($updateChatSettingsButtonClickEnableAddress));

		$this->assertTrue(TelegramHelper::isChatMigrateTo($updateChatMigrateTo));
		$this->assertFalse(TelegramHelper::isChatMigrateTo($updateChatMigrateFrom));
		$this->assertFalse(TelegramHelper::isChatMigrateTo($updateChatSettingsButtonClickEnableAddress));
	}

	public function testViaBot(): void
	{
		$updateViaBot = self::fromFile(__DIR__ . '/fixtures/message_via_bot.json');
		$this->assertTrue(TelegramHelper::isViaBot($updateViaBot));
		$this->assertTrue(TelegramHelper::isViaBot($updateViaBot, 'BetterLocationBot'));
		$this->assertFalse(TelegramHelper::isViaBot($updateViaBot, 'Different username'));

		$viaBot = TelegramHelper::getViaBot($updateViaBot);
		$this->assertInstanceOf(User::class, $viaBot);
		$this->assertTrue($viaBot->is_bot);
		$this->assertSame($viaBot->id, 1382240789);
		$this->assertSame($viaBot->username, 'BetterLocationBot');

		// Random other update
		$updateChatMigrateFrom = self::fromFile(__DIR__ . '/fixtures/chat_migrate_from.json');
		$this->assertFalse(TelegramHelper::isViaBot($updateChatMigrateFrom));
		$this->assertNull(TelegramHelper::getViaBot($updateChatMigrateFrom));
	}

	private static function fromFile(string $file): Update
	{
		return new Update(json_decode(file_get_contents($file), true));
	}
}
