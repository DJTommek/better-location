<?php declare(strict_types=1);

namespace Tests\TelegramCustomWrapper;

use App\Config;
use App\TelegramCustomWrapper\DatetimeFormat;
use App\TelegramCustomWrapper\TelegramHelper;
use PHPUnit\Framework\TestCase;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User;

final class TelegramHelperTest extends TestCase
{
	public static function datetimeFormatProvider(): array
	{
		return [
			['<tg-time unix="1647531900" format="r">2022-03-17 15:45:00 UTC</tg-time>', 1647531900, [DatetimeFormat::RELATIVE]],
			['<tg-time unix="743551200">1993-07-24 22:00:00 UTC</tg-time>', 743551200, []],
			['<tg-time unix="-25605885" format="Dt">1969-03-10 15:15:15 UTC</tg-time>', -25605885, [DatetimeFormat::DATE_LONG, DatetimeFormat::TIME_SHORT]],
		];
	}

	public static function datetimeFormatSmartProvider(): array
	{
		return [
			['<tg-time unix="1647531900" format="DT">2022-03-17 15:45:00 UTC</tg-time> (<tg-time unix="1647531900" format="r">2022-03-17 15:45:00 UTC</tg-time>)', 1647531900, true],
			['<tg-time unix="-25605885" format="DT">1969-03-10 15:15:15 UTC</tg-time> (<tg-time unix="-25605885" format="r">1969-03-10 15:15:15 UTC</tg-time>)', -25605885, true],
			['<tg-time unix="1647531900" format="D">2022-03-17 15:45:00 UTC</tg-time> (<tg-time unix="1647531900" format="r">2022-03-17 15:45:00 UTC</tg-time>)', 1647531900, false],
			['<tg-time unix="1566950400" format="D">2019-08-28 00:00:00 UTC</tg-time> (<tg-time unix="1566950400" format="r">2019-08-28 00:00:00 UTC</tg-time>)', 1566950400, false],
		];
	}

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
			// URL without scheme
			['Hello ||||||||||||||||||||||||||||||||||||||| here!', 'Hello www.deaddrops.com/db/?page=view&id=2772 here!', [['offset' => 6, 'length' => 39, 'type' => 'url']]],
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

	public static function entityUrlProvider(): array
	{
		return [
			['https://t.me/', 'Hello https://t.me/ here!', ['offset' => 6, 'length' => 13, 'type' => 'url']],
			['http://t.me/', 'Hello http://t.me/ here!', ['offset' => 6, 'length' => 12, 'type' => 'url']],
			// URL without scheme
			['https://www.deaddrops.com/db/?page=view&id=2772', 'Hello www.deaddrops.com/db/?page=view&id=2772 here!', ['offset' => 6, 'length' => 39, 'type' => 'url']],
			['https://t.me/', 'Hello t.me/ here!', ['offset' => 6, 'length' => 5, 'type' => 'url']],
			// URL is hidden under text
			['https://t.me/', 'Hello there!', ['offset' => 6, 'length' => 5, 'type' => 'text_link', 'url' => 'https://t.me/']],
		];
	}

	/**
	 * @dataProvider entityUrlProvider
	 */
	public function testGetEntityUrl(string $expectedUrl, string $message, array $entityRaw): void
	{
		$this->assertSame($expectedUrl, TelegramHelper::getEntityUrl($message, new MessageEntity($entityRaw)));
	}

	/**
	 * Real webhook update from Telegram with URL hidden under text (text_link entity)
	 */
	public function testGetEntityUrlTextLinkFromUpdate(): void
	{
		$update = self::fromFile(__DIR__ . '/fixtures/message_text_link.json');
		$message = $update->message;
		$entities = iterator_to_array($message->entities, false);
		$this->assertCount(1, $entities);
		$entity = $entities[0];
		$this->assertSame('text_link', $entity->type);

		$this->assertSame('https://www.deaddrops.com/db/?page=view&id=2772', TelegramHelper::getEntityUrl($message->text, $entity));
		// Text of text_link entity is not URL, so it must stay untouched
		$this->assertSame($message->text, TelegramHelper::getMessageWithoutUrls($message->text, $entities));
	}

	private static function fromFile(string $file): Update
	{
		return new Update(json_decode(file_get_contents($file), true));
	}

	/**
	 * @dataProvider datetimeFormatProvider
	 */
	public function testDatetimeFormat(string $expected, int $datetimeUnix, array $format): void
	{
		$datetime = (new \DateTime())->setTimestamp($datetimeUnix);
		$this->assertSame($expected, TelegramHelper::datetimeFormat($datetime, $format));
	}

	/**
	 * @dataProvider datetimeFormatSmartProvider
	 */
	public function testDatetimeFormatSmart(string $expected, int $datetimeUnix, bool $includeTime): void
	{
		$datetime = (new \DateTime())->setTimestamp($datetimeUnix);
		$this->assertSame($expected, TelegramHelper::datetimeFormatSmart($datetime, $includeTime));
	}
}
