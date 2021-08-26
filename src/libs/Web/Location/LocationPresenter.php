<?php declare(strict_types=1);

namespace App\Web\Location;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Factory;
use App\Web\MainPresenter;
use Nette\Utils\Json;

class LocationPresenter extends MainPresenter
{
	/** @var float */
	private $lat;
	/** @var float */
	private $lon;
	/** @var BetterLocation */
	private $location;
	/** @var array Multidimensional array of all structures, where is possible to generate something (share link, drive link, ...) */
	private $services = [];

	public function __construct()
	{
		$this->template = new LocationTemplate();
		parent::__construct();
	}

	public function action()
	{
		if (\App\Utils\Coordinates::isLat($_GET['lat'] ?? null) && \App\Utils\Coordinates::isLon($_GET['lon'] ?? null)) {
			$this->lat = \App\Utils\Strict::floatval($_GET['lat']);
			$this->lon = \App\Utils\Strict::floatval($_GET['lon']);
			$this->location = BetterLocation::fromLatLon($this->lat, $this->lon);
			$this->handleAction();
			$this->location->generateAddress();
			$this->location->generateDateTimeZone();

			$manager = new ServicesManager();
			foreach ($manager->getServices() as $service) {
				$this->services[] = $this->website($service, $this->lat, $this->lon);
			}
			$this->services = array_values(array_filter($this->services));
			if (mb_strtolower($_GET['format'] ?? 'html') === 'json') {
				$this->json();
			}
		}
	}

	private function handleAction()
	{
		if (isset($_GET['action'])) {
			if ($this->login->isLogged()) {
				switch ($_GET['action']) {
					case 'add':
						$this->user->addFavourite($this->location, BetterLocation::generateFavouriteName($this->lat, $this->lon));
						break;
					case 'delete':
						$this->user->deleteFavourite($this->location);
						break;
				}
			}
			$this->redirect(Config::getAppUrl('/' . $this->location->__toString()));
		}
	}

	public function render(): void
	{
		if ($this->location) {
			$this->template->prepare($this->location, $this->services);
			Factory::Latte('location.latte', $this->template);
		} else {
			Factory::Latte('locationError.latte', $this->template);
		}
	}

	public function json(): void
	{
		$result = new \stdClass();
		$result->lat = $this->lat;
		$result->lon = $this->lon;
		$result->address = $this->location->getAddress();
		$result->services = $this->services;
		header('Content-Type: application/json');
		die(Json::encode($result));
	}

	private function website($service, float $lat, float $lon)
	{
		/** @var $service AbstractService */
		$result = [];
		try {
			$result['share'] = $service::getShareLink($lat, $lon);
		} catch (NotSupportedException $exception) {
		}
		try {
			$result['drive'] = $service::getDriveLink($lat, $lon);
		} catch (NotSupportedException $exception) {
		}
		try {
			$result['text'] = $service::getShareText($lat, $lon);
		} catch (NotSupportedException $exception) {
		}
		if ($result !== []) {
			$result['name'] = $service::NAME;
		}
		return $result;
	}
}

