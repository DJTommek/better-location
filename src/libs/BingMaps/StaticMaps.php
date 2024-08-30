<?php declare(strict_types=1);

namespace App\BingMaps;

use App\StaticMaps\StaticMapsProviderInterface;
use DJTommek\Coordinates\CoordinatesInterface;

class StaticMaps implements StaticMapsProviderInterface
{
	const LINK = 'https://dev.virtualearth.net';
	const LINK_PATH = '/REST/V1/Imagery/Map/Road';

	/**
	 * @var int default icon as described in docs
	 * @see https://docs.microsoft.com/en-us/bingmaps/rest-services/common-parameters-and-types/pushpin-syntax-and-icon-styles#icon-styles
	 */
	public const PUSHPIN_DEFAULT_ICON = 1;
	public const PUSHPIN_RED_DOT_ICON = 22;

	/**
	 * @var list<string>
	 */
	private array $pushPinsStr = [];

	public function __construct(
		#[\SensitiveParameter] private readonly string $apiKey,
	) {
	}

	/**
	 * @param int|null $iconStyle null to use default or int, @see https://docs.microsoft.com/en-us/bingmaps/rest-services/common-parameters-and-types/pushpin-syntax-and-icon-styles#icon-styles
	 * @param string $label up to three characters
	 */
	private function addPushpin(CoordinatesInterface $coordinates, ?int $iconStyle = self::PUSHPIN_DEFAULT_ICON, string $label = ''): void
	{
		if (mb_strlen($label) > 3) {
			throw new \InvalidArgumentException(sprintf('Pushpin label can be up to three characters long, but provided "%s" (%d characters)', $label, mb_strlen($label)));
		}
		if (is_null($iconStyle)) {
			$iconStyle = self::PUSHPIN_DEFAULT_ICON;
		}
		$this->pushPinsStr[] = sprintf('%F,%F;%d;%s', $coordinates->getLat(), $coordinates->getLon(), $iconStyle, $label);
	}

	private function generateLink(): string
	{
		if (count($this->pushPinsStr) === 0) {
			throw new \BadMethodCallException('Must add at least one pushpin to proper render map.');
		}

		$url = self::LINK . self::LINK_PATH;

		// Query parameters to URL, see https://docs.microsoft.com/en-us/bingmaps/rest-services/imagery/get-a-static-map#map-parameters
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

	private function reset(): void
	{
		$this->pushPinsStr = [];
	}

	/**
	 * @param array<CoordinatesInterface> $markers
	 */
	public function generatePrivateUrl(array $markers): string
	{
		$this->reset();

		foreach ($markers as $key => $marker) {
			assert($marker instanceof CoordinatesInterface);
			$this->addPushpin($marker, null, (string)($key + 1));
		}

		return $this->generateLink();
	}
}
