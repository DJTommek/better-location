<?php

namespace App\Pluginer;

use App\BetterLocation\BetterLocationCollection;
use App\MiniCurl\Exceptions\TimeoutException;
use App\MiniCurl\MiniCurl;
use Nette\Http\UrlImmutable;
use Nette\Utils\Json;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

class Pluginer
{
	private const CACHE_TTL = 5; // in seconds

	public function __construct(
		private UrlImmutable        $pluginUrl,
		private int                 $updateId,
		private int                 $messageId,
		private int                 $messageDate,
		private Telegram\Types\Chat $chat,
		private Telegram\Types\User $user,
	)
	{
	}

	public function process(BetterLocationCollection $collection): void
	{
		$dataOriginal = [
			'meta' => [
				'date' => time(),
			],
			'telegram' => [
				'update_id' => $this->updateId,
				'message_id' => $this->messageId,
				'date' => $this->messageDate,
				'chat' => [
					'id' => $this->chat->id,
					'type' => $this->chat->type,
				],
				'from' => [
					'id' => $this->user->id,
				],
			],
			'locations' => $this->collectionToData($collection),
		];

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

		// @TODO add JSON incoming JSON schema validation
		// @TODO save original prefix so can be restored (eg if new prefix is too long, has invalid HTML, etc)
		foreach ($dataNew['locations'] as $key => $location) {
			$collection[$key]->setPrefixMessage($location['prefix']);
		}
	}

	private function callApi(array|\stdClass|\JsonSerializable $requestBody): array
	{
		$miniCurl = new MiniCurl($this->pluginUrl);
		$miniCurl->allowCache(self::CACHE_TTL);
		$miniCurl->allowAutoConvertEncoding(false);
		$miniCurl->setPostJson($requestBody);
		$response = $miniCurl->run();
		return Json::decode($response->getBody(), Json::FORCE_ARRAY);
	}

	private function collectionToData(BetterLocationCollection $collection): array
	{
		$result = [];
		foreach ($collection as $location) {
			$result[] = [
				'latitude' => $location->getLat(),
				'longitude' => $location->getLon(),
				'address' => $location->getAddress(),
				'prefix' => $location->getPrefixMessage(),
			];
		}
		return $result;
	}
}
