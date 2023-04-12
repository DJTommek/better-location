<?php

namespace App\Http;

use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;

class Response extends \GuzzleHttp\Psr7\Response implements ResponseInterface
{
	public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', string $reason = null)
	{
		parent::__construct($status, $headers, $body, $version, $reason);
	}

	public function getJson(bool $forceArray = false): mixed
	{
		$body = (string)$this->getBody();
		return Json::decode($body, $forceArray);
	}
}
