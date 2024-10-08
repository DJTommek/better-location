<?php declare(strict_types=1);

namespace App\Web;

use App\Config;
use App\Factory\LatteFactory;
use App\Factory\UserFactory;
use App\TelegramCustomWrapper\TelegramHelper;
use App\User;
use App\Utils\Strict;
use App\Web\Login\LoginFacade;
use Nette\Http\Request;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Nette\Utils\Json;

abstract class MainPresenter
{
	public const HTTP_OK = 200;

	public const HTTP_BAD_REQUEST = 400;
	public const HTTP_UNAUTHORIZED = 401;
	public const HTTP_FORBIDDEN = 403;
	public const HTTP_NOT_FOUND = 404;

	public const HTTP_INTERNAL_SERVER_ERROR = 500;

	private readonly LatteFactory $latteFactory;
	private readonly UserFactory $userFactory;
	protected readonly Request $request;
	protected readonly LoginFacade $login;
	protected ?User $user = null;
	public LayoutTemplate $template;
	public string $templatefile;

	/**
	 * Dependencies that are required in MainPresenter. To request dependencies in specific presenters, define them in
	 * __construct() method.
	 */
	public final function setDependencies(
		UserFactory $userFactory,
		LatteFactory $latteFactory,
		LoginFacade $loginFacade,
		Request $request,
	): void {
		$this->latteFactory = $latteFactory;
		$this->userFactory = $userFactory;
		$this->request = $request;
		$this->login = $loginFacade;

		if (!isset($this->template)) { // load default template if any was provided
			$this->template = new LayoutTemplate();
		}
	}

	public final function run(): void
	{
		if ($this->login->isLogged()) {
			$loginEntity = $this->login->getEntity();
			$this->user = $this->userFactory->createOrRegisterFromTelegram(
				$loginEntity->userTelegramId,
				$loginEntity->displayname(),
			);
		}

		$this->template->login = $this->login;
		$this->template->user = $this->user;
		$this->template->cachebusterMainCss = filemtime(__DIR__ . '/../../../www/css/main.css');
		$appUrl = Config::getAppUrl();
		$this->template->baseUrl = rtrim($appUrl->getAbsoluteUrl(), '/');
		$this->template->basePath = rtrim($appUrl->getPath(), '/');
		$this->template->flashMessages = $this->getFlashMessages();
		$this->template->botName = Config::TELEGRAM_BOT_NAME;
		$this->template->botLink = TelegramHelper::userLink(Config::TELEGRAM_BOT_NAME);
		$this->action();
		$this->beforeRender();
		$this->render();
	}

	protected function setTemplateFilename(string $templateFile): void
	{
		$this->templatefile = Config::FOLDER_TEMPLATES . '/' . $templateFile;
	}

	public function action(): void
	{
		// can be overriden
	}

	/**
	 * Can be overriden to set template file
	 */
	public function beforeRender(): void
	{
	}

	/**
	 * Can be overriden for custom rendering.
	 */
	public function render(): void
	{
		if (!isset($this->template)) {
			throw new \LogicException(sprintf('Template must be set to render %s', static::class));
		}
		if (!isset($this->templatefile)) {
			throw new \LogicException(sprintf('TemplateFile must be set to render %s', static::class));
		}

		$this->latteFactory->render($this->templatefile, $this->template);
	}

	/**
	 * Where user will be redirected to:
	 * - absolute example: 'http://tomas.palider.cz/something'
	 * - relative path example: '/something' will append to URL defined in config and make it absolute
	 */
	public final function redirect(string|Url|UrlImmutable $url, bool $permanent = false): never
	{
		if (is_string($url) && str_starts_with($url, '/')) { // dynamic path, eg '/login.php'
			$url = Config::getAppUrl($url);
		}
		if (Strict::isUrl($url) === false) {
			throw new \InvalidArgumentException('Invalid redirect link');
		}
		header('Location: ' . $url, true, $permanent ? 301 : 302);
		die(sprintf('Redirecting to <a href="%1$s">%1$s</a> ...', $url));
	}

	/**
	 * Store flash message so can be displayed when proper layout is rendered.
	 *
	 * @param string $content HTML content to be displayed.
	 * @param ?int $dismiss int = milliseconds after message should dissapear, null = user has to close manually
	 */
	public final function flashMessage(string $content, Flash $type = Flash::INFO, ?int $dismiss = 4_000): FlashMessage
	{
		$flashMessage = new FlashMessage($content, $type, $dismiss);
		if (!isset($_SESSION['FLASH_MESSAGES']) || !is_array($_SESSION['FLASH_MESSAGES'])) {
			$_SESSION['FLASH_MESSAGES'] = [];
		}
		$_SESSION['FLASH_MESSAGES'][] = $flashMessage;
		return $flashMessage;
	}

	/**
	 * Load stored flash messages from storage. Once message is loaded, it is automatically removed from storage and not
	 * displayed again.
	 *
	 * @return \Generator<FlashMessage>
	 * @internal Used only to passing into template.
	 */
	public final function getFlashMessages(): \Generator
	{
		foreach ($_SESSION['FLASH_MESSAGES'] ?? [] as $key => $flashMessage) {
			yield $flashMessage;
			unset($_SESSION['FLASH_MESSAGES'][$key]);
		}
	}

	protected function isPostRequest(): bool
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}

	/**
	 * @param array<mixed,mixed>|\stdClass $data
	 */
	protected function sendJson(array|\stdClass $data, int $httpCode = self::HTTP_OK): never
	{
		http_response_code($httpCode);
		header('Content-Type: application/json');
		die(Json::encode($data));
	}

	/**
	 * @param \stdClass|array<string|int, mixed>|null $result Array list is just for backward compatibility, should not
	 *      be used for new responses.
	 * @param self::HTTP_* $httpCode
	 */
	protected function apiResponse(
		bool $error,
		?string $message = null,
		\stdClass|array|null $result = null,
		int $httpCode = self::HTTP_OK,
	): never {
		$data = [
			'error' => $error,
			'datetime' => time(),
			'message' => $message ?? '',
			'result' => $result ?? new \stdClass(),
		];
		$this->sendJson($data, $httpCode);
	}

	final protected function renderForbidden(): never
	{
		$this->setTemplateFilename('403.latte');
		$this->latteFactory->render($this->templatefile, $this->template);
		die();
	}
}

