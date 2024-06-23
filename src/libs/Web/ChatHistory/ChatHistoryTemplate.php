<?php declare(strict_types=1);

namespace App\Web\ChatHistory;

use App\Repository\ChatEntity;
use App\Repository\ChatLocationHistoryEntity;
use App\Web\ChatErrorTrait;
use App\Web\LayoutTemplate;
use unreal4u\TelegramAPI\Telegram;

class ChatHistoryTemplate extends LayoutTemplate
{
	use ChatErrorTrait;

	public ChatEntity $chatEntity;
	/**
	 * @var list<ChatLocationHistoryEntity>
	 */
	public array $chatHistoryLocations;

	/**
	 * @var array<string, \stdClass>
	 */
	public array $locationsJs = [];
	/**
	 * @var array<array{float, float}>
	 */
	public array $allCoords = [];

	/**
	 * @param list<ChatLocationHistoryEntity> $chatHistoryLocations
	 */
	public function prepareOk(ChatEntity $chatEntity, array $chatHistoryLocations): void
	{
		$this->chatEntity = $chatEntity;
		$this->chatHistoryLocations = $chatHistoryLocations;

		$this->locationsJs = [];
		$this->allCoords = [];
		foreach ($this->chatHistoryLocations as $historyLocationEntity) {
			assert($historyLocationEntity instanceof ChatLocationHistoryEntity);
			$this->locationsJs[] = (object)[
				'lat' => $historyLocationEntity->latitude,
				'lon' => $historyLocationEntity->longitude,
				'coords' => [$historyLocationEntity->latitude, $historyLocationEntity->longitude],
				'hash' => md5((string)$historyLocationEntity->coordinates),
				'key' => $historyLocationEntity->coordinates->getLatLon(),
				'timestamp' => $historyLocationEntity->timestamp->getTimestamp() * 1000,
				'input' => $historyLocationEntity->input,
				'address' => $historyLocationEntity->address,
			];
			$this->allCoords[] = [$historyLocationEntity->getLat(), $historyLocationEntity->getLon()];
		}
	}
}

