<?php declare(strict_types=1);

namespace App\BetterLocation\Service\FdriveCz;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Config;
use App\Icons;
use App\Utils\Requestor;
use App\Utils\Strict;

/**
 * Charging stations for electric vehicles from fdrive.cz map.
 *
 * Instead of loading detail of each station, database of all stations is downloaded and cached (it contains all
 * necessary data) and station is searched in it.
 */
final class FdriveCzService extends AbstractService
{
	const int ID = 68;
	const string NAME = 'fdrive.cz';
	public const array TAGS = [];

	const string LINK = 'https://fdrive.cz/mapa-nabijecich-stanic';

	/**
	 * Database of all charging stations including metadata (providers, charger types, ...)
	 * Warning: response is around 3 MB, so it must fit into Config::HTTP_MAX_DOWNLOAD_SIZE.
	 */
	const string DATA_URL = 'https://fdrive.cz/mapa-nabijecich-stanic/api/charging-map-data';

	/**
	 * Station URL is in format "/mapa-nabijecich-stanic/{slug}-{id}", where slug is optional
	 */
	const string PATH_REGEX = '/^\/mapa-nabijecich-stanic\/(?:([a-z0-9-]*)-)?([0-9]+)\/?$/';

	const string STATUS_OPEN = 'open';
	const array STATUSES = [
		self::STATUS_OPEN => 'Open',
		'tempClosed' => 'Temporarily closed',
		'inDev' => 'Under construction',
	];

	private ?int $stationId = null;
	private ?string $stationSlug = null;

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	#[\Override]
	public function validate(): bool
	{
		if (
			$this->url
			&& $this->url->getDomain(2) === 'fdrive.cz'
			&& preg_match(self::PATH_REGEX, $this->url->getPath(), $matches)
		) {
			$this->stationSlug = $matches[1] === '' ? null : $matches[1];
			$this->stationId = Strict::intval($matches[2]);
			return true;
		}
		return false;
	}

	#[\Override]
	public function process(): void
	{
		$data = $this->requestor->getJson(self::DATA_URL, Config::CACHE_TTL_FDRIVE_CZ);
		if (!$data instanceof \stdClass || !isset($data->features) || !is_array($data->features)) {
			throw new InvalidLocationException(sprintf('Unable to load list of charging stations from %s.', self::NAME));
		}

		assert($this->stationId !== null);
		$station = $this->findStation($data->features, $this->stationId);
		if ($station === null) {
			return;
		}

		$properties = $station->properties;
		// URL without slug or with different slug (eg. https://fdrive.cz/mapa-nabijecich-stanic/4) is valid but it is
		// not working on website, so use full URL instead.
		$input = $this->stationSlug === $properties->url ? $this->input : self::stationUrl($properties->url, $this->stationId);
		$location = new BetterLocation($input, $properties->location->lat, $properties->location->long, self::class);
		$location->setPrefixTextInLink(htmlspecialchars($properties->name));
		$location->setAddress(trim($properties->location->address));

		$statusDescription = $this->statusDescription($properties->status);
		if ($statusDescription !== null) {
			$location->addDescription($statusDescription);
		}

		$chargersDescription = $this->chargersDescription($properties->chargers, $data->charger_types ?? new \stdClass());
		if ($chargersDescription !== null) {
			$location->addDescription($chargersDescription);
		}

		$this->collection->add($location);
	}

	public static function stationUrl(string $slug, int $id): string
	{
		return sprintf('%s/%s-%d', self::LINK, $slug, $id);
	}

	/**
	 * @param array<\stdClass> $features
	 */
	private function findStation(array $features, int $id): ?\stdClass
	{
		return array_find($features, fn($feature) => $feature->properties->id === $id);
	}

	public function statusDescription(string $status): ?string
	{
		if ($status === self::STATUS_OPEN) {
			return null;
		}

		return sprintf('%s Status: <b>%s</b>', Icons::WARNING, htmlspecialchars(self::STATUSES[$status] ?? $status));
	}

	/**
	 * @param array<\stdClass> $chargers
	 * @param \stdClass $chargerTypes Object of charger types metadata, where key is charger type ID
	 */
	public function chargersDescription(array $chargers, \stdClass $chargerTypes): ?string
	{
		$chargersTexts = [];
		foreach ($chargers as $charger) {
			$typeName = $chargerTypes->{$charger->id}->name ?? ('#' . $charger->id);
			$text = sprintf('%d× %s', $charger->count, htmlspecialchars($typeName));
			$maxOutput = (float)$charger->max_output;
			if ($maxOutput > 0) {
				$text .= sprintf(' (%s kW)',$maxOutput);
			}
			$chargersTexts[] = $text;
		}

		if ($chargersTexts === []) {
			return null;
		}

		return 'Chargers: ' . implode(', ', $chargersTexts);
	}
}
