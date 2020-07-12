<?php
declare(strict_types=1);
require_once __DIR__ . '/src/config.php';

?>
	<h1>BetterLocationBot</h1>
	<p>
		Hello world! <?= Icons::CHECKED; ?>
	</p>
	<h3>Setup webhook</h3>
	<ol>
		<li>Update <?= __DIR__ ?>/data/config.local.php:TELEGRAM_WEBHOOK_URL to your desired URL.</li>
		<li>Open <a href="set-webhook.php">set-webhook.php</a></li>
	</ol>
	<p>
		If you successfully setup webhook, you can send message to your bot from Telegram and you should get reply.
	</p>
	<p>
		Note: If you are not able to use webhook, there is possible to setup polling. Currently not implemented in this app and is in @TODO with low priority.
	</p>
<?php
