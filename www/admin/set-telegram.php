<?php declare(strict_types=1);

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\Command;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetMyCommands;
use unreal4u\TelegramAPI\Telegram\Methods\SetMyCommands;
use unreal4u\TelegramAPI\Telegram\Types\BotCommand;
use unreal4u\TelegramAPI\Telegram\Types\BotCommandScope;
use unreal4u\TelegramAPI\TgLog;
use function Clue\React\Block\await;

require_once __DIR__ . '/../../src/bootstrap.php';

if (empty(\App\Config::ADMIN_PASSWORD)) {
	die('Set ADMIN_PASSWORD in your local config file first');
}

$request = (new \Nette\Http\RequestFactory())->fromGlobals();

if ($request->getQuery('password') !== \App\Config::ADMIN_PASSWORD && $request->getCookie('bl-admin-password') !== \App\Config::ADMIN_PASSWORD) {
	die('You don\'t have access without password.');
}

if (Config::isTelegram()) {
	$loop = \React\EventLoop\Factory::create();
	$tgLog = new TgLog(Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($loop));

	printf('<h2>Setting up webhook...</h2>');
	$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook();
	$setWebhook->url = Config::getTelegramWebhookUrl()->getAbsoluteUrl();
	$setWebhook->max_connections = Config::TELEGRAM_MAX_CONNECTIONS;
	$setWebhook->secret_token = Config::TELEGRAM_WEBHOOK_PASSWORD;
	if (App\Utils\Utils::globalGetToBool('drop_pending_updates')) {
		printf('%s All pending updates will be dropped.<br>', Icons::INFO);
		$setWebhook->drop_pending_updates = true;
	}
	try {
		await($tgLog->performApiRequest($setWebhook), $loop);
		printf('%1$s Telegram webhook URL successfully set to <a href="%2$s" target="_blank">%2$s</a> with secret password as HTTP header.<br>', Icons::SUCCESS, $setWebhook->url);
	} catch (Exception $exception) {
		printf('%1$s Failed to set Telegram webhook URL to <a href="%2$s" target="_blank">%2$s</a>:<br><b>%3$s</b>. See logs for more info.<br>.', Icons::ERROR, $setWebhook->url, $exception->getMessage());
		Debugger::log($exception, ILogger::EXCEPTION);
	}

	printf('<h2>Setting up commands...</h2>');
	foreach (Config::TELEGRAM_COMMANDS as $scope => $classStrings) {
		printf('Setting commands for scope "<b>%s</b>"...<br>', $scope);
		$setCommands = new SetMyCommands();
		$setCommands->scope = new BotCommandScope();
		$setCommands->scope->type = $scope;

		foreach ($classStrings as $classString) {
			/** @var Command $classString */
			$result = new BotCommand();
			$result->command = ltrim($classString::getTgCmd(), '/');
			$result->description = sprintf('%s %s', $classString::ICON, $classString::DESCRIPTION);
			$setCommands->commands[] = $result;
		}
		$response = await($tgLog->performApiRequest($setCommands), $loop);
		printf('%s Commands for scope "<b>%s</b>" were set.<br>', Icons::SUCCESS, $scope);
	}

	printf('<h2>Loading commands from API...</h2>');
	foreach (Config::TELEGRAM_COMMANDS as $scope => $classStrings) {
		$getCommands = new GetMyCommands();
		$getCommands->scope = new BotCommandScope();
		$getCommands->scope->type = $scope;
		$response = await($tgLog->performApiRequest($getCommands), $loop);

		printf('There are %d command(s) for scope "<b>%s</b>"<br>', count($response->data), $scope);
		foreach ($response->data as $command) {
			/** @var $command BotCommand */
			printf('/%s - %s<br>', $command->command, $command->description);
		}
	}
} else {
	printf('Updating Telegram webhook URL is not allowed. Set all necessary TELEGRAM_* constants in local config and try again.');
}

