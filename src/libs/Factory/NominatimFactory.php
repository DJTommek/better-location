<?php declare(strict_types=1);

namespace App\Factory;

use App\Config;

class NominatimFactory
{
	public function __construct(
		private readonly GuzzleClientFactory $guzzleClientFactory,
		private readonly string $nominatimUrl,
		private readonly string $userAgent,
	) {
	}

	public function create(): \maxh\Nominatim\Nominatim
	{
		$client = $this->guzzleClientFactory->create([
			'base_uri' => $this->nominatimUrl,
		]);

		$headers = [
			'User-Agent' => $this->userAgent,
		];

		return new \maxh\Nominatim\Nominatim(Config::NOMINATIM_URL, $headers, $client);
	}
}
