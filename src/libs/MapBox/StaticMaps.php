<?php declare(strict_types=1);

namespace App\MapBox;

use App\StaticMaps\StaticMapsProviderInterface;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Utils\Json;

class StaticMaps implements StaticMapsProviderInterface
{
	const LINK = 'https://api.mapbox.com';

	/**
	 * @var array<string,mixed>|null
	 */
	private ?array $geojson = null;

	public function __construct(
		#[\SensitiveParameter] private readonly string $apiKey,
	) {
	}

	private function generateLink(): string
	{
		if ($this->geojson === null) {
			throw new \BadMethodCallException('Must add at least one pushpin to proper render map.');
		}

		$url = self::LINK;
		$url .= '/styles/v1';
		$url .= '/mapbox'; // username
		$url .= '/streets-v12'; // style_id
		$url .= '/static';
		$url .= '/geojson(' . urlencode(Json::encode($this->geojson)) . ')';
		$url .= '/auto';
		$url .= '/600x600';

		$params = [
			'access_token' => $this->apiKey,
		];
		$paramsStr = http_build_query($params);

		return $url . '?' . $paramsStr;
	}

	private function reset(): void
	{
		$this->geojson = null;
	}

	public function generatePrivateUrl(array $markers): string
	{
		$this->reset();

		$coordinates = [];
		foreach ($markers as $marker) {
			assert($marker instanceof CoordinatesInterface);
			$coordinates[] = [
				round($marker->getLon(), 6),
				round($marker->getLat(), 6),
			];
		}

		$this->geojson = [
			'type' => 'Feature',
			'properties' => [
				'marker-label' => '12',
			],
			'geometry' => [
				'type' => 'MultiPoint',
				'coordinates' => $coordinates,
			],
		];

		return $this->generateLink();
	}
}
