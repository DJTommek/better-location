<?php declare(strict_types=1);

namespace App\Google;

use App\BetterLocation\GooglePlaceApi;
use App\Utils\Requestor;
use Tracy\Debugger;
use Tracy\ILogger;

trait RunGoogleApiRequestTrait
{
	public function __construct(
		private readonly Requestor $requestor,
		#[\SensitiveParameter] private readonly string $apiKey,
	) {
	}

	abstract function cacheTtl(): int;

	private function runGoogleApiRequest(string $url): ?\stdClass
	{
		$content = $this->requestor->getJson($url, $this->cacheTtl());
		$status = ResponseCodes::customFrom($content->status);

		if ($status->isEmpty()) {
			return null;
		}

		if ($status->isError()) {
			// @phpstan-ignore-next-line (Strict comparison using === between and non GooglePlaceApi class)
			if (self::class === GooglePlaceApi::class && $status === ResponseCodes::INVALID_REQUESTS) {
				// 2023-01-06: Ignore this error because it occures even for valid inputs such as:
				// - '25 11'N 064 39'E'
				// - 25 11N 064 39E
				// but apparently this input is valid:
				// - 2511 N 064 39E
				return null;
			}

			Debugger::log('Request URL: ' . $url, ILogger::DEBUG);
			Debugger::log($content, ILogger::DEBUG);
			throw new \Exception(sprintf(
				'Invalid status "%s" from %s. Error: "%s". See debug.log for more info.',
				self::class,
				$content->status,
				$content->error_message ?? 'Not provided',
			));
		}

		return $content;
	}
}
