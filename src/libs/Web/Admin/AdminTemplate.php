<?php declare(strict_types=1);

namespace App\Web\Admin;

use App\Config;
use App\DefaultConfig;
use App\Web\LayoutTemplate;
use Nette\Http\Request;
use Nette\Http\UrlImmutable;

class AdminTemplate extends LayoutTemplate
{
	public readonly Request $request;
	public readonly bool $isAppUrlSet;
	public readonly UrlImmutable $appUrl;
	public readonly UrlImmutable $tgWebhookUrl;

	public function prepare(Request $request): void
	{
		$this->request = $request;
		$this->appUrl = Config::getAppUrl();
		$this->isAppUrlSet = $this->appUrl->isEqual(DefaultConfig::getAppUrl()) === false;
		$this->tgWebhookUrl = Config::getTelegramWebhookUrl();
	}
}
