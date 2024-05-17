<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\FavouriteNameGenerator;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Utils\DateImmutableUtils;
use App\Utils\Utils;
use App\Web\Flash;
use App\Web\MainPresenter;
use DJTommek\Coordinates\Coordinates;
use Nette\Utils\Json;
use Tracy\Debugger;

class LocationsPresenter extends MainPresenter
{
	private BetterLocationCollection $collection;
	/**
	 * Multidimensional array of all structures, where is possible to generate something (share link, drive link, ...)
	 *
	 * @var array<string,array<array{name: string, share?: string, drive?: string, text?: string, static?: string}>>
	 */
	private array $services = [];
	private string $format = 'html';
	private string $nowFileText;

	public function __construct(
		private readonly ServicesManager $servicesManager,
		private readonly FavouriteNameGenerator $favouriteNameGenerator,
		LocationsTemplate $template,
	)
	{
		$this->template = $template;
		$this->collection = new BetterLocationCollection();
		$this->nowFileText = DateImmutableUtils::nowUtc()->format(Config::DATETIME_FILE_FORMAT);
	}

	public function action(): void
	{
		$regex = '/^' . Coordinates::RE_BASIC . '(;' . Coordinates::RE_BASIC . ')*$/';
		$input = $_GET['coords'] ?? '';
		if ($input && preg_match($regex, $input)) {
			foreach (explode(';', $input) as $coords) {
				$coords = Coordinates::fromString($coords, ',');
				if ($coords === null) {
					continue;
				}
				$this->collection->add(BetterLocation::fromLatLon($coords->lat, $coords->lon));
			}
		}
		$this->collection->deduplicate();

		if ($this->collection->count() && isset($_GET['action'])) {
			if ($this->login->isLogged()) {
				switch ($_GET['action']) {
					case 'add':
						foreach ($this->collection as $location) {
							$name = $this->favouriteNameGenerator->generate($location);
							$favoriteLocation = $this->user->addFavourite($location, $name);
							$this->flashMessage(sprintf(
								'Location <b>%s</b> was saved to favorites as <b>%s</b>.',
								$favoriteLocation->key(),
								htmlentities($favoriteLocation->getPrefixMessage()),
							), Flash::SUCCESS);
						}
						break;
					case 'delete':
						foreach ($this->collection as $location) {
							$this->user->deleteFavourite($location);
							$this->flashMessage(sprintf('Location <b>%s</b> was removed from favorites.', $location->key()), Flash::INFO);
						}
						break;
				}
			}
			$this->redirect($this->collection->getLink());
		}

		if (Utils::globalGetToBool('address') === true) {
			$this->collection->fillAddresses();
		}
		if (Utils::globalGetToBool('datetimezone') === true) {
			$this->collection->fillDatetimeZone();
			$this->template->showingTimezoneData = true;
		}
		if (Utils::globalGetToBool('elevation') === true) {
			$this->collection->fillElevations();
			$this->template->showingElevation = true;
		}

		foreach ($this->collection as $location) {
			$services = [];
			foreach ($this->servicesManager->getServices() as $service) {
				try {
					$services[] = $this->website($service, $location->getLat(), $location->getLon());
				} catch (\Throwable $exception) {
					Debugger::log($exception, Debugger::EXCEPTION);
				}
			}
			$services = array_values(array_filter($services));
			$this->services[$location->key()] = $services;
		}
		$this->format = mb_strtolower($_GET['format'] ?? 'html');
	}

	public function render(): void
	{
		if ($this->collection->isEmpty()) {
			$this->setTemplateFilename('locations.latte');
		}

		$this->template->prepare($this->collection, $this->services);
		match ($this->format) {
			'json' => $this->renderJson(),
			'gpx' => $this->renderFileGpx(),
			'kml' => $this->renderFileKml(),
			default => $this->renderHtml()
		};
	}

	public function renderHtml(): void
	{
		$this->setTemplateFilename('locations.latte');
		parent::render();
	}

	public function renderJson(): void
	{
		$result = new \stdClass();

		// For backward compatbitility, loading address in JSON is enabled by default
		if (in_array(Utils::globalGetToBool('address'), [true, null], true)) {
			$this->collection->fillAddresses();
		}

		$result->locations = array_map(function (BetterLocation $location) {
			$resultLocation = new \stdClass();
			$resultLocation->lat = $location->getLat();
			$resultLocation->lon = $location->getLon();
			$resultLocation->hash = $location->getCoordinates()->hash();
			$resultLocation->elevation = $location->getCoordinates()->getElevation();
			$resultLocation->timezoneId = $location->getTimezoneData()?->timezoneId;
			$resultLocation->address = $location->getAddress();
			$resultLocation->services = $this->services[$location->key()];
			return $resultLocation;
		}, $this->collection->getLocations());
		header('Content-Type: application/json');
		header('Access-Control-Allow-Origin: *');
		die(Json::encode($result));
	}

	public function renderFileGpx(): void
	{
		header(sprintf('Content-Disposition: attachment; filename="BetterLocation_%d_locations_%s.gpx"', count($this->collection), $this->nowFileText));
		$this->setTemplateFilename('locationsGpx.latte');
		parent::render();
	}

	public function renderFileKml(): void
	{
		header(sprintf('Content-Disposition: attachment; filename="BetterLocation_%d_locations_%s.kml"', count($this->collection), $this->nowFileText));
		$this->setTemplateFilename('locationsKml.latte');
		parent::render();
	}

	/**
	 * @param class-string<AbstractService> $service
	 * @return array{share?: string, drive?: string, text?: string, 'static'?: string, name?: string}
	 */
	private function website(string $service, float $lat, float $lon): array
	{
		$result = [];
		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_LINK_SHARE)
			&& $output = $service::getShareLink($lat, $lon)
		) {
			$result['share'] = $output;
		}

		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_LINK_DRIVE)
			&& $output = $service::getDriveLink($lat, $lon)
		) {
			$result['drive'] = $output;
		}

		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_TEXT)
			&& $output = $service::getShareText($lat, $lon)
		) {
			$result['text'] = $output;
		}

		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_LINK_IMAGE)
			&& $output = $service::getScreenshotLink($lat, $lon)
		) {
			$result['static'] = $output;
		}

		if ($result !== []) {
			$result['name'] = $service::NAME;
		}
		return $result;
	}
}

