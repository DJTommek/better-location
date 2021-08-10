<?php declare(strict_types=1);

namespace App\Web;

use App\Config;
use App\Database;
use App\Factory;
use App\User;
use App\Utils\Strict;
use App\Web\Login\LoginFacade;
use Nette\Utils\Strings;

abstract class MainPresenter
{
	/** @var Database */
	protected $db;
	/** @var LoginFacade */
	protected $login;
	/** @var User */
	protected $user;
	/** @var LayoutTemplate */
	public $template;

	/**
	 * Set template and basic variables
	 */
	public function __construct()
	{
		if ($this->template === null) { // load default template if any was provided
			$this->template = new LayoutTemplate();
		}
		$this->db = Factory::Database();
		$this->login = new LoginFacade();
		if ($this->login->isLogged()) {
			$this->user = new User($this->login->getTelegramId(), $this->login->getDisplayName());
		}
		$this->template->login = $this->login;
		$this->template->user = $this->user;
		$this->template->cachebusterMainCss = filemtime(__DIR__ . '/../../../www/css/main.css');
		$appUrl = Config::getAppUrl();
		$this->template->baseUrl = rtrim($appUrl->getAbsoluteUrl(), '/');
		$this->template->basePath = rtrim($appUrl->getPath(), '/');
		$this->action();
		$this->render();
	}

	public function action()
	{
		// can be overriden
	}

	public function render()
	{
		// Should be overriden
		throw new \LogicException(sprintf('No render method was provided for %s', static::class));
	}

	public final function redirect($url, $permanent = false): void
	{
		if (is_string($url) && Strings::startsWith($url, '/')) { // dynamic path, eg '/login.php'
			$url = Config::getAppUrl($url);
		}
		if (Strict::isUrl($url) === false) {
			throw new \InvalidArgumentException('Invalid redirect link');
		}
		header('Location: ' . $url, true, $permanent ? 301 : 302);
		die(sprintf('Redirecting to <a href="%1$s">%1$s</a> ...', $url));
	}
}

