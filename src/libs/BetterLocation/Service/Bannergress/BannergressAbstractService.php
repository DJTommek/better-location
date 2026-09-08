<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Bannergress;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\DatetimeFormat;
use App\TelegramCustomWrapper\TelegramHelper;
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

	#[\Override]
 public function validate(): bool
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

	#[\Override]
 public function process(): void
	{
		$mosaic = $this->loadApi($this->data->mosaicId);
		if ($mosaic === null) {
			return;
		}

		$mosaicPicture = 'https://api.bannergress.com' . $mosaic->picture;
		$location = new BetterLocation($this->inputUrl, $mosaic->startLatitude, $mosaic->startLongitude, static::class);
		$location->setInlinePrefixMessage(sprintf('%s %s', static::getName(), $mosaic->title));
		$location->setPrefixMessage(
			sprintf(
				'<a href="%s">%s %s</a> <a href="%s">%s</a>',
				static::mosaicUrl($mosaic->id),
				static::getName(),
				htmlspecialchars($mosaic->title),
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

		if (isset($mosaic->warning)) {
			$location->addDescription(sprintf(
				'%s %s',
				Icons::WARNING,
				htmlspecialchars($mosaic->warning),
			));
		}

		if (isset($mosaic->plannedOfflineDate)) {
			$plannedOfflineDate = new \DateTimeImmutable($mosaic->plannedOfflineDate, new \DateTimeZone('UTC'));
			$offlineDateFormatted = TelegramHelper::datetimeFormat($plannedOfflineDate, [DatetimeFormat::DATE_LONG]);
			if ($plannedOfflineDate <= new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
				$location->addDescription(sprintf('%s Offline since %s', Icons::WARNING, $offlineDateFormatted));
			} else {
				$location->addDescription(sprintf('%s Planned to go offline on %s', Icons::WARNING, $offlineDateFormatted));
			}
		}

		if ($mosaic->numberOfDisabledMissions > 0) {
			$location->addDescription(sprintf(
				'%s %d %s disabled.',
				Icons::WARNING,
				$mosaic->numberOfDisabledMissions,
				$mosaic->numberOfDisabledMissions === 1 ? 'mission is' : 'missions are',
			));
		}
	}

	private function loadApi(string $mosaicId): ?\stdClass
	{
		$url = 'https://api.bannergress.com/bnrs/' . $mosaicId;

		return $this->requestor->getJson($url, Config::CACHE_TTL_BANNERGRESS);
	}
}
