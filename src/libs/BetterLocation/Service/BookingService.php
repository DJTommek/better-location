<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\Address\Address;
use App\Address\Country;
use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Utils;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Http\Url;
use Nette\Utils\Json;

final class BookingService extends AbstractService
{
	const ID = 52;
	const NAME = 'Booking';

	const LINK = 'https://booking.com/';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		if ($this->url === null) {
			return false;
		}

		if ($this->url->getDomain(2) !== 'booking.com') {
			return false;
		}

		$path = $this->url->getPath();

		// https://www.booking.com/Share-6dioAU
		if (str_starts_with($path, '/Share-')) {
			return true;
		}

		return (bool)preg_match('/^\/hotel\/[a-z]{2}\/.+/i', $path);
	}

	public function process(): void
	{
		$responseBody = $this->loadUrl($this->url);
		$dom = Utils::domFromUTF8($responseBody);

		$finder = new \DOMXPath($dom);
		$jsonEl = $finder->query('//script[@type="application/ld+json"]')->item(0);
		$json = Json::decode($jsonEl->textContent);

		$coords = self::getCoordsFromDom($json);
		$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class);

		$location->appendToPrefixMessage(sprintf(
			' <a href="%s" target="_blank">%s</a>',
			$json->url,
			htmlspecialchars($json->name),
		));

		$country = $this->getCountryFromUrl($json->url);
		$address = new Address($json->address->streetAddress, $country);
		$location->setAddress($address);

		$location->addDescription(sprintf(
			'★%s %s',
			number_format($json->aggregateRating->ratingValue, 1, '.'),
			htmlspecialchars($json->description),
		));
		$this->collection->add($location);
	}

	private function loadUrl(Url $url): string
	{
		$cleanUrl = (new Url($url->getHostUrl()))
			->setPath($url->getPath());
		return $this->requestor->get($cleanUrl, Config::CACHE_TTL_BOOKING);
	}

	private static function getCoordsFromDom(\stdClass $ldJson): CoordinatesInterface
	{
		$url = new Url($ldJson->hasMap);
		$coordsRaw = $url->getQueryParameter('center');
		return Coordinates::fromString($coordsRaw);
	}

	private static function getCountryFromUrl(string $url): ?Country
	{
		$url = new Url($url);
		$path = $url->getPath();
		if ((bool)preg_match('/^\/hotel\/([a-z]{2})\//i', $path, $matches) === false) {
			return null;
		}
		return Country::fromAlpha2Code($matches[1]);
	}
}
