<?php declare(strict_types=1);

namespace App\IpApiCom;

use Sammyjo20\Saloon\Http\SaloonConnector;

class Connector extends SaloonConnector
{
	public function defineBaseUrl(): string
	{
		// Intentionally HTTP, otherwise endpoints will fail: "SSL unavailable for this endpoint, order a key at https://members.ip-api.com/"
		return 'http://ip-api.com';
	}

	public function defaultConfig(): array
	{
		return [
			'timeout' => 5,
		];
	}
}
