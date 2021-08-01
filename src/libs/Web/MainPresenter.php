<?php declare(strict_types=1);

namespace App\Web;

use App\Config;
use App\Factory;
use App\Utils\Strict;
use App\Web\Login\LoginFacade;

abstract class MainPresenter
{
	protected $db;
	protected $login;
	public $template;

	public function __construct()
	{
		$this->db = Factory::Database();
		$this->login = new LoginFacade();
		$this->setTemplate();
		$this->template->login = $this->login;
		$appUrl = Config::getAppUrl();
		$this->template->baseUrl = rtrim($appUrl->getAbsoluteUrl(), '/');
		$this->template->basePath = rtrim($appUrl->getPath(), '/');
	}

	public function setTemplate(): void
	{
		$this->template = new LayoutTemplate();
	}

	public function redirect($url, $permanent = false)
	{
		if (Strict::isUrl($url) === false) {
			throw new \InvalidArgumentException('Invalid redirect link');
		}
		header('Location: ' . $url, true, $permanent ? 301 : 302);
		die(sprintf('Redirecting to <a href="%1$s">%1$s</a> ...', $url));
	}

	public abstract function render();
}

