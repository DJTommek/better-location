<?php declare(strict_types=1);

namespace App\Http\Guzzle\Middlewares;

use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\RequestInterface;

/**
 * By default, Guzzle PSR client is following PSR-18 specification, including NOT automatically redirecting, if HTTP
 * response code is 3xx.
 *
 * This custom middleware is overriding this PSR-18 behavior back to Guzzle-like behavior.
 *
 * PSR Specification kind-of allows it in custom applications, but not in librariries, quote:
 * > The specification does not put any limitations on middleware or classes that want to wrap/decorate an HTTP
 * > client. If the decorating class also implements ClientInterface then it must also follow the specification.
 * >
 * > It is temping to allow configuration or add middleware to an HTTP client so it could i.e. follow redirects or
 * > throw exceptions. If that is a decision from an application developer, they have specifically said they want to
 * > break the specification. That is an issue (or feature) the application developer should handle. Third party
 * > libraries MUST NOT assume that a HTTP client breaks the specification.
 *
 * @link https://www.php-fig.org/psr/psr-18/meta/#middleware-and-wrapping-a-client
 */
class AlwaysRedirectMiddleware
{
	public function __invoke(callable $handler): \Closure
	{
		return function (RequestInterface $request, array $options) use ($handler) {
			$redirectMiddleware = new RedirectMiddleware($handler);
			$options['allow_redirects'] = true;
			return $redirectMiddleware($request, $options);
		};
	}
}
