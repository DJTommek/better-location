<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Bannergress;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\Config;
use App\Icons;
use App\Utils\Ingress;
use App\Utils\Requestor;

abstract class BannergressAbstractService extends AbstractService
{
	public const TAGS = [];

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	abstract protected function isValidDomain(): bool;

	abstract protected function mosaicUrl(string $mosaicId): string;

	public function isValid(): bool
	{
		if (
			$this->url
			&& $this->isValidDomain()
			&& preg_match('/^\/banner\/(.+)$/', $this->url->getPath(), $matches)
		) {
			$this->data->mosaicId = $matches[1];
			return true;
		}
		return false;
	}

	public function process(): void
	{
		$mosaic = $this->loadApi($this->data->mosaicId);
		$mosaicPicture = 'https://api.bannergress.com' . $mosaic->picture;
		$location = new BetterLocation($this->inputUrl, $mosaic->startLatitude, $mosaic->startLongitude, static::class);
		$location->setInlinePrefixMessage(sprintf('%s %s', static::getName(), $mosaic->title));
		$location->setPrefixMessage(
			sprintf(
				'<a href="%s">%s %s</a> <a href="%s">%s</a>',
				static::mosaicUrl($mosaic->id),
				static::getName(),
				$mosaic->title,
				$mosaicPicture,
				Icons::PICTURE,
			),
		);

		$location->addDescription(sprintf('%d missions, %.1F km', $mosaic->numberOfMissions, $mosaic->lengthMeters / 1000));

		$location->addDescription(
			sprintf(
				'First mission: <a href="%s">%s %s</a> <a href="%s">%s</a> <a href="%s">%s</a>',
				Ingress::generatePrimeMissionLink($mosaic->missions->{0}->id),
				htmlspecialchars($mosaic->missions->{0}->title),
				Icons::INGRESS_PRIME,
				Ingress::generateIntelMissionLink($mosaic->missions->{0}->id),
				Icons::INGRESS_INTEL,
				$mosaic->missions->{0}->picture,
				Icons::PICTURE,
			),
		);

		$firstPortal = $mosaic->missions->{0}->steps[0]->poi;
		if ($firstPortal->type === 'portal') {
			$location->addDescription(
				sprintf(
					'First portal: <a href="%s">%s %s</a> <a href="%s">%s</a>',
					Ingress::generatePrimePortalLink($firstPortal->id, $firstPortal->latitude, $firstPortal->longitude),
					htmlspecialchars($firstPortal->title),
					Icons::INGRESS_PRIME,
					Ingress::generateIntelPortalLink($firstPortal->latitude, $firstPortal->longitude),
					Icons::INGRESS_INTEL,
				),
				Ingress::BETTER_LOCATION_KEY_PORTAL,
			);
		}
		$this->collection->add($location);
	}

	private function loadApi(string $mosaicId): ?\stdClass
	{
		$url = 'https://api.bannergress.com/bnrs/' . $mosaicId;

		return $this->requestor->getJson($url, Config::CACHE_TTL_BANNERGRESS);
	}
}
