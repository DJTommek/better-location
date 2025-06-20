<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Utils\Formatter;
use App\Utils\Requestor;
use App\Utils\Utils;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesImmutable;
use Nette\Http\Url;

final class VetsCzService extends AbstractService
{
	const ID = 59;
	const NAME = 'vets.cz';

	const LINK = 'https://' . self::HOST;
	const HOST = 'www.vets.cz';

	const TYPE_MAP = 'map';
	const TYPE_PLACE = 'place';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	private ?int $placeId = null;
	private ?CoordinatesImmutable $mapCoords = null;

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		return sprintf(self::LINK . '/vpm/mapa/?lat=%1$F&lon=%2$F', $lat, $lon);
	}

	public function validate(): bool
	{
		if (
			$this->url === null
			|| $this->url->getDomain(2) !== 'vets.cz'
		) {
			return false;
		}
		$path = $this->url->getPath();
		if (preg_match('/^\/vpm\/(\d+)-[a-z]+/i', $path, $matches) === 1) {
			$this->placeId = (int)$matches[1];
		}

		$path = $this->url->getPath();
		if (str_starts_with($path, '/vpm/mapa')) {
			$this->mapCoords = CoordinatesImmutable::safe(
				$this->url->getQueryParameter('lat'),
				$this->url->getQueryParameter('lon'),
			);
		}

		return isset($this->placeId) || isset($this->mapCoords);
	}

	public function process(): void
	{
		$this->processMapCoords();
		$this->processPlace();
	}

	private function processMapCoords(): void
	{
		if (isset($this->mapCoords) === false) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $this->mapCoords->getLat(), $this->mapCoords->getLon(), self::class, self::TYPE_MAP);
		$this->collection->add($location);
	}

	public function processPlace(): void
	{
		if (isset($this->placeId) === false) {
			return;
		}

		$response = $this->requestor->get($this->url, Config::CACHE_TTL_KUDY_Z_NUDY_CZ);
		$dom = Utils::domFromUTF8($response);
		$selector = new \DOMXPath($dom);

		$objects = $selector->query('//*[starts-with(@id, "' . $this->placeId . '-")]');
		if (count($objects) === 0) {
			return;
		}
		$object = $objects->item(0);

		$objectLinks = $selector->query('.//a/@href', $object);
		$coords = null;
		foreach ($objectLinks as $objectLink) {
			// example: '/vpm/mapa/?lat=50.42686&lon=14.24216'
			$a = new \Nette\Http\UrlImmutable($objectLink->textContent);
			$coords = Coordinates::safe($a->getQueryParameter('lat'), $a->getQueryParameter('lon'));
			if ($coords !== null) {
				break;
			}
		}

		if ($coords === null) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_PLACE);

		$cleanUrlPath = $selector->query('h4/a/@href', $object)->item(0)->textContent;
		$cleanUrl = new Url($cleanUrlPath);
		if ($cleanUrl->host === '') {
			$cleanUrl->setScheme('https');
			$cleanUrl->setHost(self::HOST);
		}

		$title = $selector->query('h4/a/text()', $object)->item(0)->textContent;

		$htmlTag = Formatter::htmlLink((string)$cleanUrl, htmlspecialchars(self::NAME . ' ' . $title));
		$location->setPrefixMessage($htmlTag);

		$this->collection->add($location);
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_MAP,
			self::TYPE_PLACE,
		];
	}
}
