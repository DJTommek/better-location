<?php declare(strict_types=1);

namespace App\IpApiCom;

use Sammyjo20\Saloon\Constants\Saloon;
use Sammyjo20\Saloon\Http\SaloonRequest;

class Request extends SaloonRequest
{
	protected ?string $method = Saloon::GET;

	protected ?string $connector = Connector::class;

	protected ?string $response = Response::class;

	public function defineEndpoint(): string
	{
		return '/json/' . $this->ipAddress;
	}

	public function defaultQuery(): array
	{
		return [
			'fields' => [
				implode(',',
					[
						'status',
						'message',
						'country',
						'regionName',
						'city',
						'zip',
						'lat',
						'lon',
						'timezone',
						'offset',
						'isp',
						'query'
					]),
			]
		];
	}

	public function __construct(
		public string $ipAddress
	)
	{
	}
}
