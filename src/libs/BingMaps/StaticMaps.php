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
	private const PUSHPIN_DEFAULT_ICON = 1;

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
		$this->pushPinsStr[] = sprintf('%F,%F;%d;%s', $lat, $lon, $iconStyle, $label ?? '');
		return $this;
	}

	public function generateLink(): string
	{
		if (count($this->pushPinsStr) === 0) {
			throw new \BadMethodCallException('Must add at least one pushpin to proper render map.');
		}

		$url = self::LINK . self::LINK_PATH;

		$params = [
			'key' => $this->apiKey,
			'mapSize' => '600,600',
		];
		$paramsStr = http_build_query($params);

		foreach ($this->pushPinsStr as $pushPin) {
			$paramsStr .= '&pp=' . $pushPin;
		}

		return $url . '?' . $paramsStr;
	}
}
