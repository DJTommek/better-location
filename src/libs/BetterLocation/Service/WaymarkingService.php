<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\MiniCurl\MiniCurl;
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

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return sprintf('https://www.waymarking.com/wm/search.aspx?lat=%1$F&lon=%2$F', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		return $this->isUrl();
	}

	public function isUrl(): bool
	{
		return $this->isUrlWaymark();
	}

	public function isUrlWaymark(): bool
	{
		if (
			$this->url
			&& preg_match(self::URL_PATH_WAYMARK_REGEX, $this->url->getPath(), $matches)
		) {
			$this->data->waymarkId = mb_strtoupper($matches[1]);
			return true;
		} else {
			return false;
		}
	}

	public function process(): void
	{
		if ($this->data?->waymarkId) {
			$waymarkPage = $this->loadWaymark($this->data->waymarkId);
			$coords = Utils::findLeafletApiCoords($waymarkPage);
			$location = new BetterLocation($this->input, $coords->getLat(), $coords->getLon(), self::class);

			$dom = new \DOMDocument();
			// @HACK to force UTF-8 encoding. Page itself is in UTF-8 encoding but it is not saying explicitely so parser is confused.
			// @Author: https://stackoverflow.com/a/18721144/3334403
			@$dom->loadHTML('<?xml encoding="utf-8"?>' . $waymarkPage);
			$xpath = new \DOMXPath($dom);
			$location->setPrefixMessage(sprintf(
				'<a href="%s">Waymark %s %s</a>',
				$this->inputUrl,
				$this->data->waymarkId,
				$xpath->query('//div[@id="wm_name"]/text()')->item(0)->textContent,
			));

			$this->collection->add($location);

		} else {
			Debugger::log(sprintf('Unprocessable input: "%s"', $this->input), ILogger::ERROR);
		}
	}

	private function buildWaymarkUrl(string $waymarkId): Url
	{
		return new Url('https://www.waymarking.com/waymarks/' . $waymarkId);
	}

	private function loadWaymark($waymarkId): string
	{
		$waymarkUrl = (string)$this->buildWaymarkUrl($waymarkId);
		return (new MiniCurl($waymarkUrl))->allowCache(Config::CACHE_TTL_WAYMARKING)->run()->getBody();
	}
}
