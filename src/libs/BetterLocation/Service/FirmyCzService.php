<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Utils\Strict;
use DJTommek\MapyCzApi\MapyCzApi;

final class FirmyCzService extends AbstractService
{
	const ID = 25;
	const NAME = 'Firmy.cz';

	const LINK = 'https://firmy.cz';

	const URL_PATH_REGEX = '/^\/detail\/([0-9]+)/';

	public function isValid(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'firmy.cz' &&
			preg_match(self::URL_PATH_REGEX, $this->url->getPath(), $matches)
		) {
			$this->data->firmId = Strict::intval($matches[1]);
			return true;
		}
		return false;
	}

	public function process(): void
	{
		$mapyCzApi = new MapyCzApi();
		$firmDetail = $mapyCzApi->loadPoiDetails('firm', $this->data->firmId);
		$location = new BetterLocation($this->input, $firmDetail->getLat(), $firmDetail->getLon(), self::class);
		$location->setPrefixMessage(sprintf('<a href="%s">%s %s</a>', $this->input, self::NAME, $firmDetail->title));
		$location->setAddress($firmDetail->titleVars->locationMain1);
		$this->collection->add($location);
	}
}
