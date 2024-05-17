<?php declare(strict_types=1);

namespace App\Web;

use App\Config;
use App\Factory\LatteFactory;
use App\Repository\ChatRepository;
use App\Repository\FavouritesRepository;
use App\Repository\UserRepository;
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
	protected readonly Request $request;
	protected readonly LoginFacade $login;
	protected ?User $user = null;
	public LayoutTemplate $template;
	public string $templatefile;

	public final function run(
		UserRepository $userRepository,
		ChatRepository $chatRepository,
		FavouritesRepository $favouritesRepository,
		LatteFactory $latteFactory,
		LoginFacade $loginFacade,
		Request $request,
	): void {
		$this->latteFactory = $latteFactory;
		$this->request = $request;
		$this->login = $loginFacade;

		if (!isset($this->template)) { // load default template if any was provided
			$this->template = new LayoutTemplate();
		}
		if ($this->login->isLogged()) {
			$this->user = new User(
				$userRepository,
				$chatRepository,
				$favouritesRepository,
				$this->login->getTelegramId(),
				$this->login->getDisplayName(),
			);
		}
		$this->template->login = $this->login;
		$this->template->user = $this->user;
		$this->template->cachebusterMainCss = filemtime(__DIR__ . '/../../../www/css/main.css');
		$appUrl = Config::getAppUrl();
		$this->template->baseUrl = rtrim($appUrl->getAbsoluteUrl(), '/');
		$this->template->basePath = rtrim($appUrl->getPath(), '/');
		$this->template->flashMessages = $this->getFlashMessages();
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
	protected function sendJson(array|\stdClass $data, int $httpCode = self::HTTP_OK): void
	{
		http_response_code($httpCode);
		header('Content-Type: application/json');
		die(Json::encode($data));
	}

	protected function apiResponse(bool $error, ?string $message = null, \stdClass|null $result = null, int $httpCode = self::HTTP_OK): void
	{
		$data = [
			'error' => $error,
			'datetime' => time(),
			'message' => $message === null ? '' : $message,
			'result' => $result === null ? new \stdClass() : $result,
		];
		$this->sendJson($data, $httpCode);
	}
}

