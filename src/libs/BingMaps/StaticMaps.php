<?php declare(strict_types=1);

namespace App\BingMaps;

class StaticMaps
{
	const LINK = 'https://dev.virtualearth.net';
	const LINK_PATH = '/REST/V1/Imagery/Map/Road';

	/**
	 * @var int default icon as described in docs
	 * @see https://docs.microsoft.com/en-us/bingmaps/rest-services/common-parameters-and-types/pushpin-syntax-and-icon-styles#icon-styles
	 */
	public const PUSHPIN_DEFAULT_ICON = 1;
	public const PUSHPIN_RED_DOT_ICON = 22;

	/** @var string */
	private $apiKey;
	private $pushPinsStr = [];

	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	/**
	 * @param int|null $iconStyle null to use default or int, @see https://docs.microsoft.com/en-us/bingmaps/rest-services/common-parameters-and-types/pushpin-syntax-and-icon-styles#icon-styles
	 * @param string $label up to three characters
	 */
	public function addPushpin(float $lat, float $lon, ?int $iconStyle = self::PUSHPIN_DEFAULT_ICON, string $label = ''): self
	{
		if (mb_strlen($label) > 3) {
			throw new \InvalidArgumentException(sprintf('Pushpin label can be up to three characters long, but provided "%s" (%d characters)', $label, mb_strlen($label)));
		}
		if (is_null($iconStyle)) {
			$iconStyle = self::PUSHPIN_DEFAULT_ICON;
		}
		$this->pushPinsStr[] = sprintf('%F,%F;%d;%s', $lat, $lon, $iconStyle, $label);
		return $this;
	}

	/**
	 * @param array $params Query parameters to URL, see https://docs.microsoft.com/en-us/bingmaps/rest-services/imagery/get-a-static-map#map-parameters
	 */
	public function generateLink(array $params = []): string
	{
		if (count($this->pushPinsStr) === 0) {
			throw new \BadMethodCallException('Must add at least one pushpin to proper render map.');
		}

		$url = self::LINK . self::LINK_PATH;

		if (isset($params['centerPoint']) && isset($params['zoomLevel'])) {
			$url .= sprintf('/%s/%d', $params['centerPoint'], $params['zoomLevel']);
		}

		$defaultParams = [
			'key' => $this->apiKey,
			'mapSize' => '600,600',
		];
		$paramsStr = http_build_query(array_merge($defaultParams, $params));

		foreach ($this->pushPinsStr as $pushPin) {
			$paramsStr .= '&pp=' . $pushPin;
		}

		return $url . '?' . $paramsStr;
	}
}
