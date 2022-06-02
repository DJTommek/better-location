<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Factory;
use App\Utils\Ingress;
use App\Utils\Strict;
use Nette\Http\UrlImmutable;

/**
 * Parsing Ingress links generated in Ingress Prime application
 */
final class IngressPrimeService extends AbstractService
{
	const ID = 40;
	const NAME = 'Ingress';

	const TYPE_PORTAL = 'portal';
	const TYPE_MISSION = 'mission';

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public function isValid(): bool
	{
		if (
			$this->url
			&& $this->url->getDomain(0) === 'link.ingress.com'
			&& $this->url->getPath() === '/'
			&& Strict::isUrl($this->url->getQueryParameter('link'))
		) {
			$realPortalLink = new UrlImmutable($this->url->getQueryParameter('link'));
			if ($realPortalLink->getDomain(0) === 'intel.ingress.com') {
				if (preg_match('/^\/portal\/([0-9a-z]{32}\.[0-9a-f]{1,2})$/', $realPortalLink->getPath(), $matches)) {
					$this->data->portalGuid = $matches[1];
					return true;
				} elseif (preg_match('/^\/mission\/([0-9a-z]{32}\.[0-9a-f]{1,2})$/', $realPortalLink->getPath(), $matches)) {
					$this->data->missionGuid = $matches[1];
					return true;
				}
			}
		}
		return false;
	}

	public function process(): void
	{
		$lanchedApi = Factory::IngressLanchedRu();
		if (isset($this->data->portalGuid) && $portal = $lanchedApi->getPortalByGUID($this->data->portalGuid)) {
			$location = new BetterLocation($this->input, $portal->lat, $portal->lng, self::class, self::TYPE_PORTAL);
			Ingress::addPortalData($location, $portal);
			$this->collection->add($location);
		}
		if (isset($this->data->missionGuid)) {
			// @TODO load mission info and probably generate BetterLocation for mission start (first portal)
		}
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_PORTAL,
			self::TYPE_MISSION,
		];
	}
}
