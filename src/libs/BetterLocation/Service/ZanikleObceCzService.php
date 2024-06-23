<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;

final class ZanikleObceCzService extends AbstractService
{
	const ID = 31;
	const NAME = 'ZanikleObce.cz';

	const LINK = 'http://zanikleobce.cz';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	/**@throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			// http://www.zanikleobce.cz/index.php?menu=222&mpx=14.23444&mpy=48.59027
			return self::LINK . sprintf('/index.php?menu=222&mpx=%2$F&mpy=%1$F', $lat, $lon);
		}
	}

	public function validate(): bool
	{
		if ($this->url && $this->url->getDomain(2) === 'zanikleobce.cz') {
			// if both query parameters ('detail' + 'obec') are available, 'detail' has higher priority (as of 2021.03.08)
			if (Strict::isPositiveInt($this->url->getQueryParameter('detail'))) {
				$this->data->isPageDetail = true;
				return true;
			} else if (Strict::isPositiveInt($this->url->getQueryParameter('obec'))) {
				$this->data->isPageObec = true;
				return true;
			}
		}
		return false;
	}

	public function process(): void
	{
		if ($this->data->isPageDetail ?? false) {
			$this->url = Strict::url($this->getObecUrlFromDetail());
			if ($this->validate() === false) {
				throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url, $this->inputUrl));
			}
		}
		$this->processPageObec();
	}

	private function getObecUrlFromDetail(): string
	{
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_ZANIKLE_OBCE_CZ);
//		if (!preg_match('/<DIV class="detail_popis"><BIG><B><A HREF="([^"]+)/', $response, $matches)) { // original matching but not matching all urls
		if (!preg_match('/HREF="([^"]+obec=[^"]+)"/', $response, $matches)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Detail page "%s" has no location.', $this->url));
		}
		return self::LINK . '/' . html_entity_decode($matches[1]);
	}

	private function processPageObec(): void
	{
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_ZANIKLE_OBCE_CZ);
		if (!preg_match('/<a href=\"(https:\/\/mapy\.cz\/[^"]+)/', $response, $matches)) {  // might be multiple matches, return first occured
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on obec page "%s" are missing.', $this->url));
		}
		$mapyCzService = new MapyCzService();
		$mapyCzService->setInput($matches[1]);
		if ($mapyCzService->validate()) {
			$mapyCzService->process();
			if ($mapyCzLocation = $mapyCzService->getCollection()->getFirst()) {
				$this->collection->add(new BetterLocation($this->inputUrl, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class));
			}
		} else {
			throw new InvalidLocationException(sprintf('Parsed Mapy.cz URL from "%s" is not valid.', $this->url));
		}
	}
}
