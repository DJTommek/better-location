<?php declare(strict_types=1);

namespace App\Web\Api\v1;

use App\Web\MainPresenter;
use Tracy\Debugger;

abstract class AbstractPresenter extends MainPresenter
{
	/**
	 * @param list<string> $apiKeys
	 */
	public function __construct(#[\SensitiveParameter] private readonly array $apiKeys)
	{
	}

	protected final function assertApiKey(): void
	{
		$authorization = trim($this->request->getHeader('Authorization') ?? '');
		if ($authorization === '') {
			$this->apiResponse(true, 'API key is missing.', httpCode: self::HTTP_UNAUTHORIZED);
		}

		if (str_starts_with($authorization, 'Bearer ') === false) {
			$this->apiResponse(true, 'API key is in invalid format.', httpCode: self::HTTP_BAD_REQUEST);
		}

		[$_, $apiKey] = explode(' ', $authorization, 2);

		if (in_array($apiKey, $this->apiKeys, true) === false) {
			$this->apiResponse(true, 'API key is not valid.', httpCode: self::HTTP_FORBIDDEN);
		}
	}
}

