<?php declare(strict_types=1);

use App\Config;
use App\TelegramUpdateDb;

require_once __DIR__ . '/../../src/bootstrap.php';

if (empty(\App\Config::ADMIN_PASSWORD)) {
	die('Set ADMIN_PASSWORD in your local config file first');
}

$request = (new \Nette\Http\RequestFactory())->fromGlobals();

if ($request->getPost('password') === \App\Config::ADMIN_PASSWORD) {
	$response = new \Nette\Http\Response();
	$response->setCookie(\App\Config::ADMIN_PASSWORD_COOKIE, \App\Config::ADMIN_PASSWORD, '1 year');
	$url = Config::getAppUrl('/admin');
	$response->redirect((string)$url);
	die();
}

if ($request->getCookie(\App\Config::ADMIN_PASSWORD_COOKIE) !== \App\Config::ADMIN_PASSWORD) {
	die('Missing or invalid password. <form method="POST">Password: <input type="password" name="password"><button type="submit">Sign in</button></form>');
}

if ($request->getQuery('delete-tracy-email-sent') !== null) {
	try {
		\Nette\Utils\FileSystem::delete(Config::getTracyEmailPath());
		printf('<p>%s Tracy\'s "email-sent" file was deleted.</p>', \App\Icons::SUCCESS);
	} catch (\Nette\IOException $exception) {
		printf('<p>%s Error while deleting Tracy\'s "email-sent" file: <b>%s</b></p>', \App\Icons::ERROR, $exception->getMessage());
	}
	die('<p>Go back to <a href="./index.php">index.php</a></p>');
}

?>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>BetterLocation - Admin</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha256-MBffSnbbXwHCuZtgPYiwMQbfE7z+GOZ7fBPCNB06Z98=" crossorigin="anonymous">
	<link rel="stylesheet" href="./css/main.css">
	<link rel="shortcut icon" href="../favicon.ico">
</head>
<body>
<div class="container">
	<h1><?= \App\Icons::LOCATION; ?> <a href="./">BetterLocation</a> - Admin</h1>
	<ul class="nav nav-tabs nav-fill" id="main-tab" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" id="tab-status" data-toggle="tab" href="#status"><?= \App\Dashboard\Status::getInstallTabIcon() ?> Status</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="tab-logs" data-toggle="tab" href="#logs"><?= \App\Dashboard\Status::getTracyEmailIcon() ?> Logs</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="tab-statistics" data-toggle="tab" href="#statistics">Statistics</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="tab-tester" data-toggle="tab" href="#tester">Tester</a>
		</li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane fade show active" id="status">
			<h2>Install and status</h2>
			<ol>
				<li>Download/clone <a href="https://github.com/DJTommek/better-location" target="_blank" title="DJTommek/better-location on Github">BetterLocation repository</a> <?= \App\Icons::SUCCESS; ?></li>
				<li>Run <code>composer install</code> <?= \App\Icons::SUCCESS; ?></li>
				<?php
				printf('<li>Update <code>APP_URL</code> constant in <b>%s</b>: ', \App\Dashboard\Status::getLocalConfigPath());
				if (Config::getAppUrl()->isEqual(\App\DefaultConfig::getAppUrl())) {
					printf('%s Not set or equal to default', \App\Icons::ERROR);
				} else {
					printf('%1$s Set to <a href="%2$s" target="_blank">%2$s</a>.', \App\Icons::SUCCESS, \App\Config::getAppUrl());
				}
				printf('</li>');

				$dbTextPrefix = sprintf('Update all <code>DB_*</code> constants in <b>%s</b>', \App\Dashboard\Status::getLocalConfigPath());
				$tablesTextPrefix = 'Create tables in database using <b>asset/sql/structure.sql</b>';
				if (\App\Dashboard\Status::isDatabaseConnectionSet()) {
					printf('<li>%s: %s Connected to <b>%s</b></li>', $dbTextPrefix, \App\Icons::SUCCESS, \App\Config::DB_NAME);
					if (\App\Dashboard\Status::isDatabaseTablesSet()) {
						printf('<li>%s: %s All tables and columns are set.</li>', $tablesTextPrefix, \App\Icons::CHECKED);
					} else {
						printf('<li>%s: %s Error while checking columns: <b>%s</b></li>', $tablesTextPrefix, \App\Icons::ERROR, \App\Dashboard\Status::$tablesError->getMessage());
					}
				} else {
					printf('<li>%s: %s Error while connecting to database <b>%s</b>. Error: <b>%s</b></li>', $dbTextPrefix, \App\Icons::ERROR, \App\Config::DB_NAME, \App\Dashboard\Status::$dbError->getMessage());
					printf('<li>%s: %s Setup database connection first</li>', $tablesTextPrefix, \App\Icons::ERROR);
				}
				$tgStatusTextPrefix = sprintf('Update all <code>TELEGRAM_*</code> constants in <b>%s</b>', \App\Dashboard\Status::getLocalConfigPath());
				if (\App\Config::isTelegramWebhookPassword() === false) {
					printf('<li>%s: %s webhook password is not set.</li>', $tgStatusTextPrefix, \App\Icons::ERROR);
				} else if (\App\Config::isTelegramBotToken() === false) {
					printf('<li>%s: %s bot token is not set.</li>', $tgStatusTextPrefix, \App\Icons::ERROR);
				} else if (\App\Config::isTelegramBotName() === false) {
					printf('<li>%s: %s bot name is not set.</li>', $tgStatusTextPrefix, \App\Icons::ERROR);
				} else {
					printf('<li>%s: %s TG set to bot <a href="https://t.me/%3$s" target="_blank">%3$s</a> and webhook url to <a href="%4$s" target="_blank">%4$s</a></li>',
						$tgStatusTextPrefix, \App\Icons::SUCCESS, \App\Config::TELEGRAM_BOT_NAME, \App\Config::getTelegramWebhookUrl(),
					);
				}

				$tgWebhookTextPrefix = sprintf(
					'Setup webhook and commands via <a href="%1$s" target="_blank">%1$s</a> (or with <a href="%1$s?drop_pending_updates=true" target="_blank">drop_pending_updates=true</a>)',
					'set-telegram.php',
				);
				if (\App\Config::isTelegram()) {
					\App\Dashboard\Status::runGetWebhookStatus();
					if (\App\Dashboard\Status::$webhookError) {
						$jsonText = sprintf('<pre>%s</pre>', json_encode(get_object_vars(\App\Dashboard\Status::$webhookError->getError()), JSON_PRETTY_PRINT));
						$webhookDetailStatus = sprintf('%s Something is wrong: <b>%s</b>:%s', \App\Icons::ERROR, \App\Dashboard\Status::$webhookError->getMessage(), $jsonText);
					} else {
						if (\App\Dashboard\Status::$webhookOk) {
							$webhookDetailStatus = sprintf('%s Everything seems to be ok, check response below.', \App\Icons::SUCCESS);
						} else {
							$webhookDetailStatus = sprintf('%s Something might be wrong, check response below.', \App\Icons::WARNING);
						}
					}
					printf('<li>%s - response from <a href="https://core.telegram.org/bots/api#getwebhookinfo" target="_blank">getWebhookInfo</a>: %s</li>', $tgWebhookTextPrefix, $webhookDetailStatus);
				} else {
					printf('<li>%s: %s setup <code>TELEGRAM_*</code> in local config first.</li>', $tgWebhookTextPrefix, \App\Icons::ERROR);
				}
				?>
			</ol>
			<?php
			if (is_null(\App\Dashboard\Status::$webhookResponseRaw) === false) {
				?>
				<ul class="nav nav-tabs" id="webhook-tab" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="webhook-tab-formatted" data-toggle="tab" href="#webhook-formatted">Formatted</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="tab-status" data-toggle="tab" href="#webhook-raw">Raw</a>
					</li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane fade show active" id="webhook-formatted">
						<table class="table table-striped table-bordered table-hover table-sm">
							<?php
							foreach (get_object_vars(\App\Dashboard\Status::$webhookResponse) as $key => $value) {
								printf('<tr><td>%s</td><td>%s</td></tr>', $key, $value);
							}
							?>
						</table>
					</div>
					<div class="tab-pane fade" id="webhook-raw">
						<?php
							$json = get_object_vars(\App\Dashboard\Status::$webhookResponseRaw);
						?>
						<pre><?= htmlentities(json_encode($json, JSON_PRETTY_PRINT)) ?></pre>
					</div>
				</div>
				<?php
			}
			?>
			<ol>
				<li value="7">Setup CRON to <a href="../api/cron-refresh.php" target="_blank">cron-refresh.php</a>.
					<?php
					$now = new DateTimeImmutable();
					$autorefreshAll = TelegramUpdateDb::loadAll(TelegramUpdateDb::STATUS_ENABLED);
					$oldestRefresh = null;
					$newestRefresh = null;
					if (count($autorefreshAll) > 0) {
						$oldestRefresh = $autorefreshAll[0];
						$newestRefresh = $autorefreshAll[count($autorefreshAll) - 1];
						$diffNowOldRefresh = $now->getTimestamp() - $oldestRefresh->getLastUpdate()->getTimestamp();
						print(($diffNowOldRefresh > 3600) ? \App\Icons::WARNING : \App\Icons::SUCCESS); // @TODO move to config
						printf(' Oldest refresh was at <b>%s</b> (<b>%s</b> ago)', $now->format(\App\Config::DATETIME_FORMAT), \App\Utils\Utils::sToHuman($diffNowOldRefresh));
						// @TODO solve this
						print('<br><small>Note: might not be true - refresh is actually just last update which will change everytime user will use "Refresh" button or send refreshable location and enable autorefresh.</small>');
					}
					?>
				</li>
				<li>Google Place API: <?= \App\Config::isGooglePlaceApi() ? sprintf('%s Enabled (cache set to %s)', \App\Icons::SUCCESS, \App\Utils\Utils::sToHuman(\App\Config::CACHE_TTL_GOOGLE_PLACE_API)) : sprintf('%s Disabled', \App\Icons::ERROR) ?></li>
				<li>What3Words API: <?= \App\Config::isW3W() ? sprintf('%s Enabled', \App\Icons::SUCCESS) : sprintf('%s Disabled', \App\Icons::ERROR) ?></li>
				<li>Glympse API: <?= \App\Config::isGlympse() ? sprintf('%s Enabled', \App\Icons::SUCCESS) : sprintf('%s Disabled', \App\Icons::ERROR) ?></li>
				<li>Foursquare API: <?= \App\Config::isFoursquare() ? sprintf('%s Enabled (cache set to %s)', \App\Icons::SUCCESS, \App\Utils\Utils::sToHuman(\App\Config::CACHE_TTL_FOURSQUARE_API)) : sprintf('%s Disabled', \App\Icons::ERROR) ?></li>
			</ol>

		</div>
		<div class="tab-pane fade" id="logs">
			<h2>Errors</h2>
			<h4>Email reporting (<a href="https://tracy.nette.org/guide" target="_blank" title="Getting started with Tracy">help</a>)</h4>
			<?php
			if (is_null(\App\Config::TRACY_DEBUGGER_EMAIL)) {
				printf('<p>%s Email reporting is disabled. Set email to <b>%s::TRACY_DEBUGGER_EMAIL</b> to enable.</p>', \App\Icons::INFO, \App\Dashboard\Status::getLocalConfigPath());
			} else {
				printf('<p>%s Email reporting is enabled and set to <a href="mailto:%2$s">%2$s</a>.</p>', \App\Icons::SUCCESS, \App\Config::TRACY_DEBUGGER_EMAIL);
				$tracyEmailHelpPrefix = 'Tracy\'s "email-sent" file ';
				if (file_exists(Config::getTracyEmailPath()) === true) {
					printf('%s %s detected - no futher emails will be sent unless this file is removed. <a href="?delete-tracy-email-sent" onclick="return confirm(\'Are you sure, you want to delete Tracy\\\'s \\\'email-sent\\\' file?\')">Delete</a>', \App\Icons::WARNING, $tracyEmailHelpPrefix);
				} else {
					printf('%s %s not detected - in case of error, email will be sent.', \App\Icons::SUCCESS, $tracyEmailHelpPrefix);
				}
			}
			?>
			<h2>Tracy logs</h2>
			<?php
			$tracyLogs = \App\Dashboard\Logs::getTracyLogs(10);
			if (count($tracyLogs) === 0) {
				printf('<p>No logs are available.</p>');
			} else {
				foreach ($tracyLogs as $tracyLogName => $tracyLogContent) {
					$className = $tracyLogName;
					if (in_array($tracyLogName, [\Tracy\ILogger::ERROR, \Tracy\ILogger::EXCEPTION, \Tracy\ILogger::CRITICAL], true)) {
						$className = 'danger';
					}
					printf('<h4 class="text-%s">%s</h4>', $className, $tracyLogName);
					printf('<pre>%s</pre>', htmlentities(join(PHP_EOL, $tracyLogContent)));
				}
			}
			?>
			<h2>SimpleLogger logs</h2>
			<?php
			$maxLines = 10;
			printf('<p>Showing last %d lines per log file, oldest lines first.</p>', $maxLines);
			$now = new DateTimeImmutable();
			$logs = \App\Dashboard\Logs::getLogs($now, $maxLines);
			foreach ($logs as $logName => $logLines) {
				printf('<h4>%s <small>(%s)</small></h4>', $logName, $now->format(\App\Config::DATE_FORMAT));
				if (count($logLines) === 0) {
					printf('<p>No log for this day is available.</p>');
				} else {
					$newestLogLine = htmlentities(json_encode(end($logLines)));
					printf('<button class="btn btn-sm btn-outline-primary copy-log-content" data-to-copy="%s">Copy content of newest log</button> <span style="display: none;">Copied!</span>', $newestLogLine);
					printf('<pre>%s</pre>', htmlentities(join(PHP_EOL, array_map('json_encode', $logLines))));
				}
			}
			?>
		</div>
		<div class="tab-pane fade" id="statistics">
			<h2>Statistics</h2>
			<p>
				<?php
				if (\App\Dashboard\Status::isDatabaseConnectionSet() && \App\Dashboard\Status::isDatabaseTablesSet()) {
					printf('<ul>');
					$now = new DateTimeImmutable();

					// Detected chats
					$results = [];
					$totalCount = 0;
					foreach (\App\Dashboard\Status::getChatsStats() as $groupType => $groupCount) {
						$results[] = sprintf('%s = <b>%d</b>', $groupType, $groupCount);
						$totalCount += $groupCount;
					}
					printf('<li><b>%d</b> detected chats (%s)</li>', $totalCount, join(', ', $results));

					// Detected users
					printf('<li><b>%d</b> detected users (wrote at least one message or command)</li>', \App\Dashboard\Status::getUsersCount());

					// Newest user
					$newestUser = \App\Dashboard\Status::getNewestUser();
					if ($newestUser) {
						$newestUserUsername = (mb_strpos($newestUser['user_telegram_name'], '@') === 0) ? mb_substr($newestUser['user_telegram_name'], 1) : null;
						printf('<li>Most recent active user:<br>ID = <b>%d</b><br>TG ID = <b>%d</b><br>TG Name = <b>%s</b><br>Registered = <b>%s</b> (%s ago)<br>Last update = <b>%s</b> (%s ago)</li>',
							$newestUser['user_id'],
							$newestUser['user_telegram_id'],
							$newestUserUsername ? sprintf('<a href="https://t.me/%1$s" target="_blank">%1$s</a>', $newestUserUsername) : sprintf('<i>%s</i>', $newestUser['user_telegram_name']),
							$newestUser['user_registered']->format(DateTimeInterface::W3C),
							\App\Utils\Utils::sToHuman($now->getTimestamp() - $newestUser['user_registered']->getTimestamp()),
							$newestUser['user_last_update']->format(DateTimeInterface::W3C),
							\App\Utils\Utils::sToHuman($now->getTimestamp() - $newestUser['user_last_update']->getTimestamp()),
						);
					}

					// Last changed user
					$lastChangedUser = \App\Dashboard\Status::getLatestChangedUser();
					if ($lastChangedUser) {
						$lastChangedUserUsername = (mb_strpos($lastChangedUser['user_telegram_name'], '@') === 0) ? mb_substr($lastChangedUser['user_telegram_name'], 1) : null;
						printf('<li>Newest registered user:<br>ID = <b>%d</b><br>TG ID = <b>%d</b><br>TG Name = <b>%s</b><br>Registered = <b>%s</b> (%s ago)<br>Last update = <b>%s</b> (%s ago)</li>',
							$lastChangedUser['user_id'],
							$lastChangedUser['user_telegram_id'],
							$lastChangedUserUsername ? sprintf('<a href="https://t.me/%1$s" target="_blank">%1$s</a>', $lastChangedUserUsername) : sprintf('<i>%s</i>', $lastChangedUser['user_telegram_name']),
							$lastChangedUser['user_registered']->format(DateTimeInterface::W3C),
							\App\Utils\Utils::sToHuman($now->getTimestamp() - $lastChangedUser['user_registered']->getTimestamp()),
							$lastChangedUser['user_last_update']->format(DateTimeInterface::W3C),
							\App\Utils\Utils::sToHuman($now->getTimestamp() - $lastChangedUser['user_last_update']->getTimestamp()),
						);
					}

					// Autorefresh messages
					printf('<li>Count of messages with enabled autorefresh: <b>%d</b></li>', count($autorefreshAll));
					if (count($autorefreshAll) > 0) {
						$now = new DateTimeImmutable();
						$diffOldNewRefresh = $newestRefresh->getLastUpdate()->getTimestamp() - $oldestRefresh->getLastUpdate()->getTimestamp();
						$diffNowOldRefresh = $now->getTimestamp() - $newestRefresh->getLastUpdate()->getTimestamp();
						$diffNowNewRefresh = $now->getTimestamp() - $oldestRefresh->getLastUpdate()->getTimestamp();

						printf('<li>Oldest autorefresh: <b>%s</b> (%s ago)</li>',
							$oldestRefresh->getLastUpdate()->format(\App\Config::DATETIME_FORMAT),
							\App\Utils\Utils::sToHuman($diffNowOldRefresh),
						);
						printf('<li>Newest autorefresh: <b>%s</b> (%s ago)</li>',
							$newestRefresh->getLastUpdate()->format(\App\Config::DATETIME_FORMAT),
							\App\Utils\Utils::sToHuman($diffNowNewRefresh),
						);
						printf('<li>Diff between newest and oldest autorefresh: <b>%s</b></li>', $diffOldNewRefresh > 0 ? \App\Utils\Utils::sToHuman($diffOldNewRefresh) : 'none');
					}

					printf('</ul>');
				} else {
					printf('<p>%s Setup database connection and prepare tables.</p>', \App\Icons::ERROR);
				}
				?>
			</p>
		</div>
		<div class="tab-pane fade" id="tester">
			<h2>Tester</h2>
			<div id="tester">
				<?php
				$tester = new \App\Dashboard\Tester($request->getPost('input'));
				?>
				<form method="POST">
					<textarea name="input" class="form-control" placeholder="Type something..."><?= $tester->getTextareaInput() ?></textarea>
					<button type="submit" class="btn btn-primary">Send</button>
				</form>
				<h3>Result</h3>
				<div class="result">
					<?php
					if ($tester->isInput()) {
						$tester->handleInput();
						if ($tester->isOutputTextEmpty()) {
							print('<div class="alert alert-info">No location was detected</div>');
						} else {
							printf('<div class="message">');
							printf('<pre>%s</pre>', $tester->getOutputText());
							foreach ($tester->getOutputButtons() as $row) {
								printf('<div class="row">');
								foreach ($row as $button) {
									printf('<div class="col buttons">');
									if (empty($button->url) === false) {
										printf('<a href="%1$s" class="btn btn-secondary" target="_blank" data-toggle="tooltip" title="%1$s">%2$s</a>', $button->url, $button->text);
									} else if ($button->callback_data) {
										printf('<button class="btn btn-secondary" data-toggle="tooltip" title="Callback data: \'%s\'">%s</button>', $button->callback_data, $button->text);
									} else {
										throw new \OutOfBoundsException('Unexpected button type.');
									}
									printf('</div>');
								}
								printf('</div>');
							}
							printf('</div>');
						}
					} else {
						print('<div class="alert alert-info">Fill and send some data.</div>');
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha256-gvZPYrsDwbwYJLD5yeBfcNujPhRoGOY831wwbIzz3t0=" crossorigin="anonymous"></script>
<script src="./js/main.js"></script>
</body>
