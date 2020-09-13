<?php
declare(strict_types=1);
require_once __DIR__ . '/src/config.php';

?>
<h1>BetterLocationBot</h1>
<p>
	Hello world! <?= Icons::CHECKED; ?>
</p>
<h2>Database</h2>
<p>
	<?php
	$db = null;
	try {
		$db = Factory::Database();
		printf('%s Connected to database "%s".', Icons::SUCCESS, DB_NAME);
	} catch (\Exception $exception) {
		printf('%s Error while connecting to database "%s". Error: "%s"', Icons::ERROR, DB_NAME, $exception->getMessage());
	}
	if ($db) {
		printf('<h3>Stats</h3>');
		printf('<ul>');
		$now = new DateTimeImmutable();

		// Detected users
		$usersCount = $db->query('SELECT COUNT(*) AS count FROM better_location_user')->fetch();
		printf('<li><b>%d</b> detected users (wrote at least one message or command)</li>', $usersCount['count']);
		$lastChangedUser = $db->query('SELECT * FROM better_location_user ORDER BY user_registered DESC LIMIT 1')->fetch();
		if ($lastChangedUser) {
			$lastChangedUser['user_registered'] = new DateTimeImmutable($lastChangedUser['user_registered'], new DateTimeZone('UTC'));
			$lastChangedUser['user_last_update'] = new DateTimeImmutable($lastChangedUser['user_last_update'], new DateTimeZone('UTC'));

			printf('<li>Newest registered user:<br>ID = <b>%d</b><br>TG ID = <b>%d</b><br>TG Name = <b>%s</b><br>Registered = <b>%s</b> (%s ago)<br>Last update = <b>%s</b> (%s ago)</li>',
				$lastChangedUser['user_id'],
				$lastChangedUser['user_telegram_id'],
				$lastChangedUser['user_telegram_name'] ?? '<i>unknown</i>',
				$lastChangedUser['user_registered']->format(DateTimeInterface::W3C),
				Utils\General::sToHuman($now->getTimestamp() - $lastChangedUser['user_registered']->getTimestamp()),
				$lastChangedUser['user_last_update']->format(DateTimeInterface::W3C),
				Utils\General::sToHuman($now->getTimestamp() - $lastChangedUser['user_last_update']->getTimestamp()),
			);
		}

		$newestUser = $db->query('SELECT * FROM better_location_user ORDER BY user_last_update DESC LIMIT 1')->fetch();
		if ($newestUser) {
			$newestUser['user_registered'] = new DateTimeImmutable($newestUser['user_registered'], new DateTimeZone('UTC'));
			$newestUser['user_last_update'] = new DateTimeImmutable($newestUser['user_last_update'], new DateTimeZone('UTC'));
			printf('<li>Most recent active user:<br>ID = <b>%d</b><br>TG ID = <b>%d</b><br>TG Name = <b>%s</b><br>Registered = <b>%s</b> (%s ago)<br>Last update = <b>%s</b> (%s ago)</li>',
				$newestUser['user_id'],
				$newestUser['user_telegram_id'],
				$newestUser['user_telegram_name'] ?? '<i>unknown</i>',
				$newestUser['user_registered']->format(DateTimeInterface::W3C),
				Utils\General::sToHuman($now->getTimestamp() - $newestUser['user_registered']->getTimestamp()),
				$newestUser['user_last_update']->format(DateTimeInterface::W3C),
				Utils\General::sToHuman($now->getTimestamp() - $newestUser['user_last_update']->getTimestamp()),
			);
		}

		// Detected chats
		$chatsCount = $db->query('SELECT chat_telegram_type, COUNT(*) as count FROM `better_location_chat` GROUP BY chat_telegram_type')->fetchAll();
		$results = [];
		$totalCount = 0;
		foreach ($chatsCount as $row) {
			$results[] = sprintf('%s = <b>%d</b>', $row['chat_telegram_type'], $row['count']);
			$totalCount += $row['count'];
		}
		printf('<li><b>%d</b> detected chats (%s)</li>', $totalCount, join(', ', $results));

		printf('</ul>');
	}
	?>
</p>
<h2>Webhook setup</h2>
<ol>
	<li>
		Update "<b><?= __DIR__ ?>/data/config.local.php:TELEGRAM_WEBHOOK_URL"</b> to your desired URL.<br>
		<?php
		if (defined('TELEGRAM_WEBHOOK_URL')) {
			printf('%s Currently set to <a href="%2$s" target="_blank">%2$s</a>', Icons::SUCCESS, TELEGRAM_WEBHOOK_URL);
		} else {
			printf('%s Currently is not set.', Icons::WARNING);
		}
		?>

	</li>
	<li>Open <a href="set-webhook.php">set-webhook.php</a></li>
</ol>
<p>
	If you successfully setup webhook, you can send message to your bot from Telegram and you should get reply.
</p>
<h2>Webhook status</h2>
<?php

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\TgLog;

$loop = \React\EventLoop\Factory::create();
$tgLog = new TgLog(TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($loop));

$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\GetWebhookInfo();

$promise = $tgLog->performApiRequest($setWebhook);

$promise->then(
	function (\unreal4u\TelegramAPI\Telegram\Types\WebhookInfo $response) {
		printf('<h4>Raw</h4><pre>%s</pre>', json_encode(get_object_vars($response), JSON_PRETTY_PRINT));
		printf('<h4>Formatted</h4><table id="webhook-info">');
		printf('<tr>');
		foreach (get_object_vars($response) as $key => $value) {
			if ($key === 'url') {
				$stringValue = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $value);
			} else if ($key === 'pending_update_count') {
				$stringValue = ($value === 0) ? Icons::SUCCESS . ' None' : Icons::WARNING . ' ' . $value;
			} else if ($key === 'last_error_message' && $value === '') {
				$stringValue = Icons::SUCCESS . ' None';
			} else if ($key === 'last_error_date') {
				if ($value === 0) {
					$stringValue = Icons::SUCCESS . ' Never';
				} else {
					$lastErrorDate = new DateTimeImmutable('@' . $value);
					$now = new DateTimeImmutable();
					$diff = $now->getTimestamp() - $lastErrorDate->getTimestamp();

					$stringValue = sprintf('%d<br>%s<br>%s ago',
						$lastErrorDate->getTimestamp(),
						$lastErrorDate->format(DATE_ISO8601),
						\Utils\General::sToHuman($diff),
					);
				}
			} else if (is_bool($value)) {
				$stringValue = $value ? 'true' : 'false';
			} else if (is_array($value)) {
				$stringValue = sprintf('Array of <b>%d</b> values: %s', count($value), print_r($value, true));
			} else {
				$stringValue = $value;
			}
			printf('<tr><td>%s</td><td>%s</td></tr>', $key, $stringValue);
		}
		printf('</table>');
	},
	function (\Exception $exception) {
		printf('<h1>Error</h1><p>Failed to get Telegram webhook info. Error: <b>%s</b></p>', $exception->getMessage());
		Debugger::log($exception, ILogger::EXCEPTION);
	}
);
$loop->run();

?>
<h2>Tester</h2>
<div id="tester">
	<?php
	$input = (isset($_POST['input']) ? trim($_POST['input']) : null);
	?>
	<form method="POST">
		<label>
			<textarea name="input"><?= $input ?? 'Type something...' ?></textarea>
		</label>
		<button type="submit">Send</button>
	</form>
	<h3>Result</h3>
	<div>
		<?php
		if ($input) {
			$urls = \Utils\General::getUrls($input);

			// Simulate Telegram message by creating URL entities
			$entities = [];
			foreach ($urls as $url) {
				$entity = new stdClass();
				$entity->type = 'url';
				$entity->offset = mb_strpos($input, $url);
				$entity->length = mb_strlen($url);
				$entities[] = $entity;
			}
			try {
				$betterLocations = BetterLocation::generateFromTelegramMessage($input, $entities);
				if (count($betterLocations->getLocations())) {
					$result = '';
					foreach ($betterLocations->getLocations() as $betterLocation) {
						$result .= $betterLocation->generateBetterLocation();
					}
					printf('<pre>%s</pre>', $result);
				} else {
					printf('No location(s) was detected in text.');
				}
				foreach ($betterLocations->getErrors() as $betterLocationError) {
					printf('<p>%s Error: <b>%s</b></p>', \Icons::ERROR, htmlentities($betterLocationError->getMessage()));
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				printf('%s Error occured while processing input: %s', Icons::ERROR, $exception->getMessage());
			}
		} else {
			print('Fill and send some data.');
		}
		?>
	</div>
</div>
<style>
	table {
		border-collapse: collapse;
	}

	#webhook-info tr td {
		border: 1px solid black;
		padding: 0.2em 0.4em;
	}

	#webhook-info tr td:first-child {
		text-align: right;
		font-weight: bold;
	}

	#tester textarea {
		height: 10em;
		width: 100%;
	}

</style>
