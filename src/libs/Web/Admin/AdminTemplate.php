<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Config;
use App\DefaultConfig;
use App\TelegramUpdateDb;
use App\Web\LayoutTemplate;
use Nette\Http\Request;
use Nette\Http\UrlImmutable;

class AdminTemplate extends LayoutTemplate
{
	public readonly Request $request;
	public readonly bool $isAppUrlSet;
	public readonly UrlImmutable $appUrl;
	public readonly UrlImmutable $tgWebhookUrl;

	public readonly int $autorefreshAllCount;
	public readonly ?TelegramUpdateDb $newestRefresh;
	public readonly ?TelegramUpdateDb $oldestRefresh;


	public function prepare(Request $request): void
	{
		$this->request = $request;
		$this->appUrl = Config::getAppUrl();
		$this->isAppUrlSet = $this->appUrl->isEqual(DefaultConfig::getAppUrl()) === false;
		$this->tgWebhookUrl = Config::getTelegramWebhookUrl();

		$autorefreshAll = \App\TelegramUpdateDb::loadAll(\App\TelegramUpdateDb::STATUS_ENABLED);
		$this->autorefreshAllCount = count($autorefreshAll);
		$this->oldestRefresh = $autorefreshAll[0] ?? null;
		$this->newestRefresh = $autorefreshAll[$this->autorefreshAllCount - 1] ?? null;
	}
}
