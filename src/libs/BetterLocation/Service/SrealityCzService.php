<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Config;
use App\Icons;
use App\Utils\Requestor;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Czech real estate portal. Supports estates and development projects.
 */
final class SrealityCzService extends AbstractService
{
	const int ID = 70;
	const string NAME = 'Sreality.cz';
	public const array TAGS = [];

	const string LINK = 'https://www.sreality.cz';

	/**
	 * @example /detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108
	 */
	const string PATH_ESTATE_REGEX = '/^\/(?:en\/)?detail\/(?:[^\/]+\/)+([0-9]+)\/?$/';
	/**
	 * @example /projekt-detail/rd-chvalkovice/39860
	 */
	const string PATH_PROJECT_REGEX = '/^\/(?:en\/)?(?:projekt|project)-detail\/[^\/]+\/([0-9]+)\/?$/';

	const string TYPE_ESTATE = 'estate';
	const string TYPE_PROJECT = 'project';

	/**
	 * Value of locality.inaccuracy_type if location is exact. Other values (eg. 'street', 'ward', 'municipality')
	 * means, that location is just approximate and real estate is somewhere in that area.
	 */
	const string INACCURACY_TYPE_EXACT = 'gps';

	const array INACCURACY_TYPES = [
		'street' => 'street',
		'ward' => 'city district',
		'municipality' => 'municipality',
	];

	private ?string $type = null;
	private ?int $id = null;

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	#[\Override]
	public function validate(): bool
	{
		if (
			$this->url === null
			|| $this->url->getDomain(2) !== 'sreality.cz'
		) {
			return false;
		}

		$path = $this->url->getPath();
		if (preg_match(self::PATH_ESTATE_REGEX, $path, $matches)) {
			$this->type = self::TYPE_ESTATE;
		} elseif (preg_match(self::PATH_PROJECT_REGEX, $path, $matches)) {
			$this->type = self::TYPE_PROJECT;
		} else {
			return false;
		}

		$this->id = Strict::intval($matches[1]);
		return true;
	}

	#[\Override]
	public function process(): void
	{
		$apiUrl = sprintf('%s/api/v1/%ss/%d', self::LINK, $this->type, $this->id);
		$response = $this->requestor->getJson($apiUrl, Config::CACHE_TTL_SREALITY_CZ);

		$locality = $response->result->locality ?? null;
		if (!isset($locality->gps_lat, $locality->gps_lon)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}

		$location = new BetterLocation($this->input, Strict::floatval($locality->gps_lat), Strict::floatval($locality->gps_lon), self::class);

		$name = $this->type === self::TYPE_PROJECT
			? ($response->result->project_name ?? null)
			: ($response->result->advert_name ?? null);
		$location->setPrefixTextInLink(htmlspecialchars($name ?: ('#' . $this->id)));

		$inaccuracyType = $locality->inaccuracy_type ?? self::INACCURACY_TYPE_EXACT;
		if ($inaccuracyType !== self::INACCURACY_TYPE_EXACT) {
			$location->addDescription(sprintf(
				'%s Location is only approximate (%s level).',
				Icons::WARNING,
				htmlspecialchars(self::INACCURACY_TYPES[$inaccuracyType] ?? $inaccuracyType),
			));
		}

		$this->collection->add($location);
	}
}
