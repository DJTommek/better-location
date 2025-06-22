<?php declare(strict_types=1);

namespace App\Http\Guzzle\Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Download only first X bytes of body of the response.
 */
class TruncateResponseBodyMiddleware
{
	public function __construct(
		private readonly int $maxBodySizeBytes,
	) {
	}

	public function __invoke(callable $handler): \Closure
	{
		return function (RequestInterface $request, array $options) use ($handler) {
			$promise = $handler($request, $options);
			return $promise->then(function (ResponseInterface $response) {
				$responseBody = $response->getBody();
				assert($responseBody->tell() === 0);
				$bodyText = '';
				while ($responseBody->eof() === false && strlen($bodyText) < $this->maxBodySizeBytes) {
					$bodyText .= $responseBody->read($this->maxBodySizeBytes);
				}
				return $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($bodyText));
			});
		};
	}
}
