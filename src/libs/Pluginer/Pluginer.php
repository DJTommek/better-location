<?php

namespace App\Pluginer;

use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\MiniCurl\Exceptions\TimeoutException;
use App\MiniCurl\MiniCurl;
use App\Utils\SimpleLogger;
use Nette\Http\UrlImmutable;
use Nette\Utils\Json;
use unreal4u\TelegramAPI\Telegram;

class Pluginer
{
	public function __construct(
		private UrlImmutable         $pluginUrl,
		private int                  $updateId,
		private ?int                 $messageId,
		private ?Telegram\Types\Chat $chat,
		private Telegram\Types\User|Telegram\Types\Chat $user,
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
				'chat' => [
					'id' => $this->chat->id ?? null,
					'type' => $this->chat->type ?? null,
				],
				'from' => [
					'id' => $this->user->id,
				],
			],
			'locations' => $this->collectionToData($collection),
		];

		// call external API to process these values and return updated data
		try {
			$timerName = 'pluginerRequest';
			\Tracy\Debugger::timer($timerName);
			$dataNew = $this->callApi($dataOriginal);
			\Tracy\Debugger::log(sprintf(
				'Pluginer requesting %s took %F seconds. Log ID = %d',
				$this->pluginUrl->getDomain(0),
				\Tracy\Debugger::timer($timerName),
				LOG_ID
			), \Tracy\Debugger::DEBUG);

		} catch (TimeoutException) {
			throw new PluginerException('Request timeouted');
		} catch (\JsonException $exception) {
			throw new PluginerException(sprintf('Unable to parse response as JSON, error: "%s"', $exception->getMessage()));
		} catch (\Exception $exception) {
			throw new PluginerException(sprintf('General error "%s"', $exception->getMessage()));
		}

		$validator = new Validator();
		$validator->validate($dataNew);
		if ($validator->isValid() === false) {
			throw new PluginerException(sprintf(
				'Response JSON has some validation errors: "%s"',
				implode('", "', $validator->getErrors())
			));
		}

		// @TODO save original prefix so can be restored (eg if new prefix is too long, has invalid HTML, etc)
		foreach ($dataNew->locations as $locationKey => $locationNew) {
			$locationOld = $collection[$locationKey];
			$collection[$locationKey]->setPrefixMessage($locationNew->prefix);
			if (isset($locationNew->address)) {
				$locationOld->setAddress($locationNew->address);
			}

			if (isset($locationNew->descriptions)) {
				$locationOld->clearDescriptions();
				foreach ($locationNew->descriptions as $description) {
					$locationOld->addDescription($description->content, $description->key);
				}
			}
		}
	}

	private function callApi(array|\stdClass|\JsonSerializable $requestBody): \stdClass
	{
		$miniCurl = new MiniCurl($this->pluginUrl);
		$miniCurl->allowCache(Config::PLUGINER_CACHE_TTL);
		$miniCurl->allowAutoConvertEncoding(false);
		$miniCurl->setPostJson($requestBody);
		$response = $miniCurl->run();
		SimpleLogger::log(SimpleLogger::NAME_PLUGINER_REQUEST, $requestBody);
		$response = Json::decode($response->getBody());
		SimpleLogger::log(SimpleLogger::NAME_PLUGINER_RESPONSE, $response);
		return $response;
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
				'descriptions' => $location->getDescriptions(),
			];
		}
		return $result;
	}
}
