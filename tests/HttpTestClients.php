<?php declare(strict_types=1);

namespace Tests;

use App\Cache\NetteCachePsr16;
use App\Factory\GuzzleClientFactory;
use App\Http\Guzzle\Middlewares\AlwaysRedirectMiddleware;
use App\Utils\Requestor;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Nette\Caching\Storages\DevNullStorage;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Collection of various HTTP clients usable in any test, just create instance in PHPUnit's setUp() method
 */
final readonly class HttpTestClients
{
	/**
	 * Some requests can contain sensitive information (credentials, login cookies), so hash must not be weak.
	 */
	private const REQUEST_FINGERPRINT_HASH_ALGORITHM = 'sha3-512';

	/** HTTP client making real requests */
	public ClientInterface $realHttpClient;
	/** HTTP client responding with previously saved responses (not using mocked HTTP client) */
	public ClientInterface $offlineHttpClient;
	/** HTTP client responding with mocked responses (using $mockHandler) */
	public ClientInterface $mockedHttpClient;

	public MockHandler $mockHandler;

	public Requestor $realRequestor;
	public Requestor $offlineRequestor;
	public Requestor $mockedRequestor;

	public function __construct()
	{
		$storage = new DevNullStorage();
		$cache = new NetteCachePsr16($storage);
		$guzzleClientFactory = new GuzzleClientFactory();

		$this->createRealHttpClient($guzzleClientFactory, $cache);
		$this->createOfflineHttpClient($guzzleClientFactory, $cache);
		$this->createMockedHttpClient($guzzleClientFactory, $cache);
	}

	private function createRealHttpClient(GuzzleClientFactory $guzzleClientFactory, CacheInterface $cache): void
	{
		$realHandlerStack = new HandlerStack();
		$realHandlerStack->setHandler(new CurlHandler());
        $realHandlerStack->push(Middleware::redirect(), 'allow_redirects');
		$realHandlerStack->push(new AlwaysRedirectMiddleware(), 'always_allow_redirects');
		$realHandlerStack->unshift($this->saveResponseBodyToFileMiddleware(...));

		$this->realHttpClient = $guzzleClientFactory->create([
			'handler' => $realHandlerStack,
		]);

		$this->realRequestor = new Requestor($this->realHttpClient, $cache);
	}

	private function createOfflineHttpClient(GuzzleClientFactory $guzzleClientFactory, CacheInterface $cache): void
	{
		$mockedHandlerStack = new HandlerStack();
		$dummyHandler = new class() {
			public function __invoke(RequestInterface $request, array $options): PromiseInterface
			{
				throw new \Exception('This invoker should not be called');
			}
		};
		$mockedHandlerStack->setHandler($dummyHandler);
		$mockedHandlerStack->push($this->loadResponseBodyFromFileMiddleware(...));
		$this->offlineHttpClient = $guzzleClientFactory->create([
			'handler' => $mockedHandlerStack,
		]);

		$this->offlineRequestor = new Requestor($this->offlineHttpClient, $cache);
	}

	private function createMockedHttpClient(GuzzleClientFactory $guzzleClientFactory, CacheInterface $cache): void
	{
		$this->mockHandler = new MockHandler();
		$handlerStack = HandlerStack::create($this->mockHandler);
		$this->mockedHttpClient = $guzzleClientFactory->create([
			'handler' => $handlerStack,
		]);

		$this->mockedRequestor = new Requestor($this->mockedHttpClient, $cache);
	}

	public function saveResponseBodyToFileMiddleware(callable $handler): \Closure
	{
		return function (RequestInterface $request, array $options) use ($handler) {
			$filepath = $this->requestFileFingerprint($request);

			$promise = $handler($request, $options);
			return $promise->then(
				function (ResponseInterface $response) use ($filepath) {
					if ($this->isRedirect($response)) {
						throw new \RuntimeException('Redirect should be handled by RedirectMiddleware');
					}

					FileSystem::write($filepath, (string)$response->getBody());

					return $response;
				},
			);
		};
	}

	public function loadResponseBodyFromFileMiddleware(callable $handler): \Closure
	{
		return function (RequestInterface $request, array $options) {
			$filepath = $this->requestFileFingerprint($request);

			try {
				$offlineResponse = FileSystem::read($filepath);
			} catch (IOException $exception) {
				throw new \Exception(sprintf('Mocked response "%s" is not available. Did you run real request test first?', $filepath), previous: $exception);
			}

			return new \GuzzleHttp\Psr7\Response(200, body: $offlineResponse);
		};
	}

	/**
	 * @return string Absolute path to file, which name is generated based on given request (ignoring HTTP header user-agent)
	 */
	private function requestFileFingerprint(RequestInterface $request): string
	{
		// User agents might be randomized, ignore them for fingerprint
		$requestForFingerprint = $request->withoutHeader('User-Agent')
			->withoutHeader('Cookie')
			->withoutHeader('Authorization');

		// Cleanup URI for nicer filename
		$uri = $requestForFingerprint->getUri();
		$urlString = $uri->getAuthority() . $uri->getPath() . $uri->getQuery();

		$authoritySafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $uri->getAuthority());

		$urlSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $urlString);
		$urlSafeShort = substr($urlSafe, 0, 100);
		$serialized = serialize($requestForFingerprint);
		$requestFingerprint = hash(self::REQUEST_FINGERPRINT_HASH_ALGORITHM, $serialized);
		$requestFingerprintShort = substr($requestFingerprint, 0, 32);

		return sprintf(
			'%s/fixtures/httpTestClient/%s/%s_%s.response',
			__DIR__,
			$authoritySafe,
			$urlSafeShort,
			$requestFingerprintShort,
		);
	}

	private function isRedirect(ResponseInterface $response): bool
	{
		$code = $response->getStatusCode();
		return $code >= 300 && $code < 400;
	}
}
