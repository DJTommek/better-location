<?php

namespace App\Pluginer;

use App\BetterLocation\BetterLocationCollection;
use App\MiniCurl\Exceptions\TimeoutException;
use App\MiniCurl\MiniCurl;
use Nette\Http\UrlImmutable;
use Nette\Utils\Json;
use Tracy\Debugger;

class Pluginer
{
	private const CACHE_TTL = 10; // in seconds

	public function __construct(private UrlImmutable $pluginUrl)
	{
	}

	public function process(BetterLocationCollection $collection): void
	{
		$dataOriginal = ['locations' => []];
		foreach ($collection as $key => $location) {
			$dataOriginal['locations'][$key] = new InputOutputLocation($location);
		}

		// call external API to process these values and return updated data
		try {
			$dataNew = $this->callApi($dataOriginal);
		} catch (TimeoutException $exception) {
			Debugger::log('Plugin API url "%s" timeouted', Debugger::INFO);
			// @TODO warn chat admin(s)
			return;
		} catch (\Exception $exception) {
			Debugger::log('Error on plugin API url "%s": ' . $exception->getMessage(), Debugger::WARNING);
			// @TODO warn chat admin(s)
			return;
		}

		foreach ($dataNew['locations'] as $key => $location) {
			$collection[$key]->setPrefixMessage($location['prefix']);
		}
	}

	private function callApi(array|\stdClass|\JsonSerializable $requestBody): array
	{
		$miniCurl = new MiniCurl($this->pluginUrl);
		$miniCurl->allowCache(self::CACHE_TTL);
		$miniCurl->setPostJson($requestBody);
		$response = $miniCurl->run();
		return Json::decode($response->getBody(), Json::FORCE_ARRAY);
	}
}
