<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\IpApiCom;
use Tracy\Debugger;
use Tracy\ILogger;

final class IpAddressService extends AbstractService
{
	const ID = 37;
	const NAME = 'IP Address';

	const RE_IP_V4_SIMPLE = '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}';

	public function isValid(): bool
	{
		return filter_var($this->input, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
	}

	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		if (preg_match_all('/' . self::RE_IP_V4_SIMPLE . '/u', $text, $matches)) {
			foreach ($matches[0] as $ipAddress) {
				$service = new self($ipAddress);
				try {
					if ($service->isValid()) {
						$service->process();
						$collection->add($service->getCollection());
					}
				} catch (InvalidLocationException $exception) {
					Debugger::log($exception, ILogger::DEBUG);
				}
			}
		}
		return $collection;
	}

	public function process(): void
	{
		$request = new IpApiCom\Request($this->input);
		$response = $request->send();
		if ($response->ok()) {
			$coords = $response->coordinates();
			$location = new BetterLocation($this->input, $coords->getLat(), $coords->getLon(), self::class);
			$location->setDescription(sprintf('ISP: %s', $response->json('isp')));
			$location->setAddress($response->address());
			$location->setPrefixMessage(sprintf('<a href="http://%s/">%s</a>', $this->input, $this->input));
			$this->collection->add($location);
		}
	}
}
