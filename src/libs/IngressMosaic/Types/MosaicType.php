<?php declare(strict_types=1);

namespace App\IngressMosaic\Types;

use App\IngressMosaic\Client;
use App\Utils\StringUtils;
use Tracy\Debugger;

class MosaicType
{
	/** @var string */
	public $responseRaw;
	/** @var array */
	public $attributesRaw;
	/** @var array */
	public $mosaicInfoVariableRaw;

	/** @var string */
	public $name;
	/** @var string */
	public $image;
	/** @var int */
	public $id;
	/** @var float */
	public $startLat;
	/** @var float */
	public $startLon;

	/** @var string City name */
	public $locationName;
	public $type;
	/** @var ?bool */
	public $nonstop;
	/** @var int */
	public $status;
	/** @var \DateTimeImmutable */
	public $lastCheck;
	/** @var int */
	public $missionsTotal;
	/** @var int */
	public $distanceTotal;
	/** @var ?\DateInterval total time for mosaic */
	public $byFootTotal;
	/** @var ?\DateInterval average time per mission */
	public $byFootAvg;
	/** @var ?\DateInterval total time for mosaic */
	public $byBicycleTotal;
	/** @var ?\DateInterval average time per mission */
	public $byBicycleAvg;
	/** @var int */
	public $portalsTotal;
	/** @var float */
	public $portalsAvgPerMission;
	/** @var int */
	public $portalsUnique;
	/** @var int */
	public $distanceStartEndPortal;
	/** @var int */
	public $actions;
	/** @var string */
	public $url;

	public function __construct(string $response)
	{
		$this->responseRaw = $response;
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$this->parseMissionsJson($response);
		$this->parseDomValues($dom);
	}

	private function parseMissionsJson(string $response)
	{
		if (preg_match('/ {8}var lang_txt_M = (\[(?:.+) {8}]);/s', $response, $matches)) {
			$langTxtM = StringUtils::replaceLimit('\'', '"', $matches[1], 4);
			$this->mosaicInfoVariableRaw = json_decode($langTxtM, false, 512, JSON_THROW_ON_ERROR);

			$this->id = (int)$this->mosaicInfoVariableRaw[1];
			$this->url = Client::LINK_MOSAIC . $this->id;
			list($lat, $lon) = $this->mosaicInfoVariableRaw[3]->latLng;
			$this->startLat = $lat;
			$this->startLon = $lon;
		} else {
			throw new \Exception('Couldn\'t find mission JSON, check response for more info.');
		}
	}

	private function parseDomValues(\DOMDocument $dom)
	{
		$finder = new \DOMXPath($dom);
		$nodes = $finder->query('//*[@id="mo_img"]/div/div[@class="col-xs-12 non-padding"]');
		$this->name = trim($finder->query('//*[@id="mosaik-id"]')->item(0)->textContent);
		$this->image = trim($finder->query('//*[@id="mo_img"]/div/div[1]/img')->item(0)->getAttribute('src'));

		$this->attributesRaw = [];
		foreach ($nodes as $node) {
			/** @var $node \DOMElement */
			$content = preg_split('/ {4,}/', trim($node->textContent));
			if (count($content) === 1) {
				if ($content[0] === '24 / 7') {
					$this->attributesRaw['24 / 7'] = $this->parseIsNonstop($node);
				}
			} else if (count($content) === 2) {
				$this->attributesRaw[$content[0]] = $content[1];
			}
		}
		$this->mapAttributes();
		$this->portalsAvgPerMission = (float) $this->portalsTotal / $this->missionsTotal;
	}

	private function mapAttributes()
	{
		$this->actions = new ActionsType();
		foreach ($this->attributesRaw as $key => $value) {
			switch ($key) {
				case 'Location':
					$this->locationName = $value;
					break;
				case 'Type':
					$this->type = $value;
					break;
				case '24 / 7':
					$this->nonstop = $value;
					break;
				case 'Last check':
					$this->lastCheck = \DateTimeImmutable::createFromFormat('d/m/Y', $value);
					break;
				case 'Missions':
					$this->missionsTotal = (int)$value;
					break;
				case 'Total distance':
					$this->distanceTotal = $this->parseDistance($value);
					break;
				case 'By foot':
					if ($parsed = $this->parseTime($value)) {
						$this->byFootTotal = $parsed[0];
						$this->byFootAvg = $parsed[1];
					}
					break;
				case 'By bicycle':
					if ($parsed = $this->parseTime($value)) {
						$this->byBicycleTotal = $parsed[0];
						$this->byBicycleAvg = $parsed[1];
					}
					break;
				case 'Portals':
					$this->portalsTotal = $this->parsePortals($value);
					break;
				case 'Unique portals':
					$this->portalsUnique = (int)$value;
					break;
				case 'Distance start / end portal:':
					$this->distanceStartEndPortal = $this->parseDistance($value);
					break;
				case 'Status':
					$this->status = (int)str_replace('% Online', '', $value);
					break;
				case 'Hacks':
					$this->actions->hacks = (int)$value;
					break;
				case 'Waypoint':
					$this->actions->waypoints = (int)$value;
					break;
				case 'Passphrases':
					$this->actions->passhrases = (int)$value;
					break;
				case 'Link':
					$this->actions->links = (int)$value;
					break;
				case 'Field':
					$this->actions->fields = (int)$value;
					break;
				case 'Deploy Mod':
					$this->actions->deployMods = (int)$value;
					break;
				case 'Capture':
					$this->actions->captures = (int)$value;
					break;
				default:
					Debugger::log(sprintf('Unknown attribute "%s" with value "%s".', $key, $value), Debugger::WARNING);
					break;
			}
		}
	}

	private function parseDistance(string $input): ?int
	{
		if ($input === '0 m') {
			return 0;
		} else if (preg_match('/^([0-9]+\.[0-9]+) ?km$/', $input, $matches)) {
			return (int)((float)$matches[1] * 1000);
		} else if (preg_match('/^([0-9]+\.[0-9]+) ?m$/', $input, $matches)) {
			return (int)$matches[1];
		} else {
			Debugger::log(sprintf('Unable to correctly extract distance from "%s".', $input), Debugger::ERROR);
			return null;
		}
	}

	private function parsePortals(string $input): int
	{
		$data = explode(' / ', $input);
		return (int)$data[0];
	}

	/** @return ?\DateInterval[] */
	private function parseTime(string $input): ?array
	{
		$input = str_replace("\xc2\xa0", ' ', $input);
		$re = '/^(?:([0-9]+)St\. )?(?:([0-9]+)m )?(?:([0-9]+)s )?\/ (?:([0-9]+)St\. )?(?:([0-9]+)m )?(?:([0-9]+)s)/';
		if (preg_match($re, $input, $matches)) {
			$total = new \DateInterval(sprintf('PT%dH%dM%dS',
				(int)$matches[1],
				(int)$matches[2],
				(int)$matches[3],
			));
			$average = new \DateInterval(sprintf('PT%dH%dM%dS',
				(int)$matches[4],
				(int)$matches[5],
				(int)$matches[6],
			));
			return [$total, $average];
		} else {
			Debugger::log(sprintf('Unable to correctly extract time from "%s".', $input), Debugger::ERROR);
			return null;
		}
	}


	private function parseIsNonstop(\DOMElement $node): ?bool
	{
		$classes = $node->childNodes[1]->childNodes[0]->getAttribute('class');
		switch ($classes) {
			case 'glyphicon glyphicon-ok':
				return true;
			case 'glyphicon glyphicon-remove':
				return false;
			case 'glyphicon glyphicon-question-sign':
				return null;
			default:
				Debugger::log('Couldn\'t detect if mosaic is 24/7. Check response for more info.');
				return null;
		}
	}
}
