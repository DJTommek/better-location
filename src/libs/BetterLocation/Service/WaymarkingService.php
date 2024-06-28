<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Utils\Requestor;
use App\Utils\StringUtils;
use App\Utils\Utils;
use Nette\Http\Url;
use Tracy\Debugger;
use Tracy\ILogger;

final class WaymarkingService extends AbstractService
{
	const ID = 41;
	const NAME = 'Waymarking';

	const WAYMARK_REGEX = 'WM[A-Z0-9]{1,5}'; // keep limit as low as possible to best match and eliminate false positive

	/**
	 * https://www.waymarking.com/waymarks/wm16APD_Dane_Gregg_Boonville_MO
	 * https://www.waymarking.com/waymarks/wm16APD
	 */
	const URL_PATH_WAYMARK_REGEX = '/^\/waymarks\/(' . self::WAYMARK_REGEX . ')($|_)/i'; // end or character "_"

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return sprintf('https://www.waymarking.com/wm/search.aspx?lat=%1$F&lon=%2$F', $lat, $lon);
		}
	}

	public function validate(): bool
	{
		return $this->isUrl();
	}

	public function isUrl(): bool
	{
		return (
			$this->url
			&& $this->url->getDomain(2) === 'waymarking.com'
			&& ($this->isUrlWaymark() || $this->isUrlImage() || $this->isUrlGallery())
		);
	}

	public function isUrlWaymark(): bool
	{
		if (preg_match(self::URL_PATH_WAYMARK_REGEX, $this->url->getPath(), $matches)) {
			$this->data->waymarkId = mb_strtoupper($matches[1]);
			return true;
		} else {
			return false;
		}
	}

	public function isUrlImage(): bool
	{
		if (
			$this->url->getPath() === '/gallery/image.aspx'
			&& $this->url->getQueryParameter('f') === '1'
			&& StringUtils::isGuid($this->url->getQueryParameter('guid'))
		) {
			$this->data->waymarkIsImage = true;
			return true;
		} else {
			return false;
		}
	}

	public function isUrlGallery(): bool
	{
		if (
			$this->url->getPath() === '/gallery/default.aspx'
			&& $this->url->getQueryParameter('f') === '1'
			&& $this->url->getQueryParameter('gid') === '2'
			&& StringUtils::isGuid($this->url->getQueryParameter('guid'))
		) {
			$this->data->waymarkIsGallery = true;
			return true;
		} else {
			return false;
		}
	}

	public function process(): void
	{
		if ($this->data->waymarkIsImage ?? false) {
			$this->processImage();
		} else if ($this->data->waymarkIsGallery ?? false) {
			$this->processGallery();
		}

		if ($this->data->waymarkId ?? false) {
			$this->processWaymark($this->data->waymarkId);
		}

		Debugger::log(sprintf('Unprocessable input: "%s"', $this->input), ILogger::ERROR);
	}

	private function processWaymark(string $waymarkId): void
	{
		$waymarkPage = $this->loadWaymark($waymarkId);
		$dom = Utils::domFromUTF8($waymarkPage);
		$xpath = new \DOMXPath($dom);

		$coordsRaw = trim($xpath->query('//div[@id="wm_coordinates"]')->item(0)->textContent);
		$coords = WGS84DegreesMinutesService::processStatic($coordsRaw)->getFirst()->getCoordinates();

		$location = new BetterLocation($this->input, $coords->getLat(), $coords->getLon(), self::class);
		$location->setPrefixMessage(sprintf(
			'<a href="%s">Waymark %s %s</a>',
			$this->inputUrl,
			$waymarkId,
			$xpath->query('//div[@id="wm_name"]/text()')->item(0)->textContent,
		));

		$this->collection->add($location);
	}

	private function processImage(): void
	{
		$imagePage = $this->requestor->get($this->url, Config::CACHE_TTL_WAYMARKING);
		$dom = Utils::domFromUTF8($imagePage);
		$xpath = new \DOMXPath($dom);
		$urlPath = $xpath->query('//a[@id="ctl00_ContentBody_LargePhotoControl1_lnkWaymark"]/@href')->item(0)->textContent;
		$this->url = new Url($this->url->getHostUrl());
		$this->url->setPath($urlPath);
		if ($this->isUrlWaymark() === false) {
			Debugger::log(sprintf('Invalid Waymark path "%s" on image page.', $urlPath));
		}
	}

	private function processGallery(): void
	{
		$galleryUrl = $this->url;
		$imagePage = $this->requestor->get($galleryUrl, Config::CACHE_TTL_WAYMARKING);
		$dom = Utils::domFromUTF8($imagePage);
		$xpath = new \DOMXPath($dom);
		$urlsPathDom = $xpath->query('//p[@id="breadcrumb"]/a/@href');
		foreach ($urlsPathDom as $urlPathDom) {
			$urlPath = $urlPathDom->textContent;
			if (str_starts_with(mb_strtolower($urlPath), '/waymarks/wm')) {
				$this->url = new Url($galleryUrl->getHostUrl());
				$this->url->setPath($urlPath);
				if ($this->isUrlWaymark() === false) {
					Debugger::log(sprintf('Invalid Waymark path "%s" on gallery page.', $urlPath), Debugger::ERROR);
				}
				return;
			}
		}
		Debugger::log(sprintf('Unable to link gallery URL "%s" to Waymark.', $galleryUrl), Debugger::ERROR);
	}

	private function buildWaymarkUrl(string $waymarkId): Url
	{
		return new Url('https://www.waymarking.com/waymarks/' . $waymarkId);
	}

	private function loadWaymark(string $waymarkId): string
	{
		$waymarkUrl = (string)$this->buildWaymarkUrl($waymarkId);
		return $this->requestor->get($waymarkUrl, Config::CACHE_TTL_WAYMARKING);
	}
}
