<?php declare(strict_types=1);

namespace App\MiniCurl;

use App\Utils\DateImmutableUtils;

/**
 * Class Cache to handle all attributes which are required
 */
class Cache
{
	const HASH_ALGORITHM = 'fnv1a64';

	public $id;
	public $rawResponse;
	public $curlInfo;
	public $datetime;

	public function __construct(string $id, string $rawResponse, array $curlInfo, int $timestamp)
	{
		$this->id = $id;
		$this->rawResponse = $rawResponse;
		$this->curlInfo = $curlInfo;
		$this->datetime = DateImmutableUtils::fromTimestamp($timestamp);
	}

	public function __toString()
	{
		return json_encode([
			'id' => $this->id,
			'rawResponse' => $this->rawResponse,
			'curlInfo' => $this->curlInfo,
			'timestamp' => $this->datetime->getTimestamp(),
		]);
	}

	public static function fromString(string $jsonString): self
	{
		$json = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
		if (empty($json['id']) || empty($json['rawResponse']) || empty($json['curlInfo']) || empty($json['timestamp'])) {
			throw new \InvalidArgumentException('Cannot create Cache object from JSON string: JSON is not containing all necessary values.');
		}
		return new Cache(
			$json['id'],
			$json['rawResponse'],
			$json['curlInfo'],
			$json['timestamp']
		);
	}

	public static function generateId(string $url, array $options): string
	{
		ksort($options); // order of options doesn't matter in cache
		// @TODO if some option has object or array, cache might not hit since there might be different order of elements.
		// For example input
		// $options = [
		//     CURLOPT_HTTPHEADER => [
		//         'x-header-1' => 'bla',
		//         'x-header-2' => 'bla',
		//     ],
		// ];
		// ... is not same as ...
		// $options = [
		//     CURLOPT_HTTPHEADER => [
		//         'x-header-2' => 'bla',
		//         'x-header-1' => 'bla',
		//     ],
		// ];
		return hash(self::HASH_ALGORITHM, $url . json_encode($options));
	}
}
