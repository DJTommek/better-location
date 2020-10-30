<?php declare(strict_types=1);

namespace App;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidApiKeyException;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\TelegramCustomWrapper\Exceptions\MessageDeletedException;
use App\TelegramCustomWrapper\SendMessage;
use unreal4u\TelegramAPI\Telegram;

class Cron
{
	/** @var Database */
	private $db;

	/** @var Telegram\Types\Update */
	private $update;
	/** @var int */
	private $telegramChatId;
	/** @var int */
	private $telegramOriginalMessageId;
	/** @var int */
	private $telegramBetterMessageId;

	/**
	 * Cron constructor.
	 *
	 * @param Telegram\Types\Update $update Full update object from button click in BetterLocation message with reply to original message
	 */
	public function __construct(Telegram\Types\Update $update)
	{
		$this->db = Factory::Database();

		$this->update = $update;

		$betterMessageId = $this->update->callback_query->message->message_id;
		if (is_int($betterMessageId) === false || $betterMessageId === 0) {
			throw new MessageDeletedException(sprintf('Better Message ID "%s" in Update object is not valid.', $betterMessageId));
		}
		$this->telegramBetterMessageId = $betterMessageId;

		$chatId = $this->update->callback_query->message->reply_to_message->chat->id ?? null;
		if (is_int($chatId) === false || $chatId === 0) {
			throw new MessageDeletedException(sprintf('Chat ID "%s" in Update object is not valid.', $chatId));
		}
		$this->telegramChatId = $chatId;

		$messageId = $this->update->callback_query->message->reply_to_message->message_id ?? null;
		if (is_int($chatId) === false || $chatId === 0) {
			throw new MessageDeletedException(sprintf('Original message ID "%s" in Update object is not valid.', $messageId));
		}
		$this->telegramOriginalMessageId = $messageId;
	}

	public function isInDb(): bool
	{
		$numberOfRows = $this->db->query('SELECT COUNT(*) FROM better_location_cron WHERE cron_telegram_chat_id = ? AND cron_telegram_message_id = ?',
			$this->telegramChatId, $this->telegramOriginalMessageId
		)->fetchColumn();
		return ($numberOfRows === 1);
	}

	private static function generateFromDb(array $row): self
	{
		$dataJson = json_decode($row['cron_telegram_update_object'], true, 512, JSON_THROW_ON_ERROR);
		$update = new Telegram\Types\Update($dataJson);
		return new self($update);
	}

	/** @return self[] */
	public static function loadAll(): array
	{
		$result = [];
		$rows = Factory::Database()->query('SELECT * FROM better_location_cron')->fetchAll();
		foreach ($rows as $row) {
			$result[] = self::generateFromDb($row);
		}
		return $result;
	}

	public function insert(): void
	{
		$this->db->query('INSERT INTO better_location_cron (cron_telegram_chat_id, cron_telegram_message_id, cron_telegram_update_object) VALUES (?, ?, ?)',
			$this->telegramChatId, $this->telegramOriginalMessageId, json_encode($this->update),
		);
	}

	public function delete(): void
	{
		$this->db->query('DELETE FROM better_location_cron WHERE cron_telegram_chat_id = ? AND cron_telegram_message_id = ?',
			$this->telegramChatId, $this->telegramOriginalMessageId
		);
	}

	public function run()
	{
		// @TODO move somewhere else
		$loop = \React\EventLoop\Factory::create();
		$tgLog = new \unreal4u\TelegramAPI\TgLog(Config::TELEGRAM_BOT_TOKEN, new \unreal4u\TelegramAPI\HttpClientRequestHandler($loop));
		$collection = BetterLocation::generateFromTelegramMessage(
			$this->update->callback_query->message->reply_to_message->text,
			$this->update->callback_query->message->reply_to_message->entities,
		);
		$result = '';
		$buttonLimit = 1; // @TODO move to config (chat settings)
		$buttons = [];
		foreach ($collection->getAll() as $betterLocation) {
			if ($betterLocation instanceof BetterLocation) {
				$result .= $betterLocation->generateBetterLocation();
				if (count($buttons) < $buttonLimit) {
					$driveButtons = $betterLocation->generateDriveButtons();
					$driveButtons[] = $betterLocation->generateAddToFavouriteButtton();
					$buttons[] = $driveButtons;
				}
			} else if (
				$betterLocation instanceof InvalidLocationException ||
				$betterLocation instanceof InvalidApiKeyException
			) {
				$result .= Icons::ERROR . $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
			} else {
				$result .= Icons::ERROR . 'Unexpected error occured while proceessing message for locations.' . PHP_EOL . PHP_EOL;
				\Tracy\Debugger::log($betterLocation, \Tracy\Debugger::EXCEPTION);
			}
		}
		$buttons[] = BetterLocation::generateRefreshButtons(true);

		$now = new \DateTimeImmutable();
		$result .= sprintf('%s Last refresh: %s', Icons::REFRESH, $now->format(Config::DATETIME_FORMAT_ZONE));
		$markup = (new Telegram\Types\Inline\Keyboard\Markup());
		$markup->inline_keyboard = $buttons;

		$sendMessage = new SendMessage($this->telegramChatId, $result, null, null, $this->telegramBetterMessageId);
		$sendMessage->disableWebPagePreview(true);
		$sendMessage->setReplyMarkup($markup);
		$promise = $tgLog->performApiRequest($sendMessage->msg);
		$promise->then(
			function (Telegram\Types\Message $message) {
				printf('<h1>Success</h1><p>Message ID <b>%d</b> in chat ID <b>%d</b> was successfully edited.</p>',
					$message->message_id, $message->chat->id
				);
			},
			function (\Exception $exception) {
				printf('<h1>Error</h1><p>Failed to edit message: <b>%s</b>.</p>', $exception->getMessage());
				\Tracy\Debugger::log($exception, \Tracy\ILogger::EXCEPTION);
			}
		);
		$loop->run();
	}

	public function getUpdate()
	{
		return $this->update;
	}
}
