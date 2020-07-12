<?php
declare(strict_types=1);
require_once __DIR__ . '/src/config.php';
// $db = Factory::Database();
?>
	<h1>PHP Template</h1>
	<p>
		Hello world! <?= Icons::CHECKED; ?>
	</p>
	<h2>Module Telegram</h2>
	<h3>Send message</h3>
	<form method="GET" action="modules/telegramSendMessage.php" target="_blank">
		<label>
			If you want to send test message, setup 'TELEGRAM_BOT_TOKEN' in data/config.local.php and put some Telegram chat ID here:<br>
			<input type="text" name="telegramChatId" value="148953285">
			<button type="submit">Odeslat</button>
		</label>
		<br>Btw, predefined value is <a href="https://t.me/DJTommek" target="_blank" title="DJTommek's Teleegram">my TG</a> so you can ask me for help :)
		<p>
			Note: User has to allow your bot to talk in that chat.<br>
			For example if chat ID is your private chat, you need to start conversation with that bot.<br>
			To send message to group chat or channel, bot has to be in this chat and have at least send message permission.
		</p>
	</form>
	<h3>Setup webhook</h3>
	<p>
		<a href="modules/telegramSetWebhook.php" target="_blank">This script</a>
		will automatically setup webhook to
		<a href="modules/telegramWebhook.php" target="_blank">this URL</a>
		but only if it is HTTPS, otherwise you will be rejected from Telegram servers.<br>
		See <a href="https://github.com/unreal4u/telegram-api/wiki/Getting-updates-via-Webhook" target="_blank">unreal4u/telegram-api</a> for more info.
	</p>
	<p>
		If you successfully setup webhook, you can send message to your bot from Telegram and you should get reply.
	</p>
	<p>
		Note: If you are not able to use webhook, there is possible to setup polling.
	</p>
<?php
