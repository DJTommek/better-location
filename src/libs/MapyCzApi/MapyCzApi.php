<?php declare(strict_types=1);

namespace MapyCzApi;

use MapyCzApi\Types\LookupBoxPlaceType;
use MapyCzApi\Types\PanoramaNeighbourType;
use MapyCzApi\Types\PanoramaType;
use MapyCzApi\Types\PlaceType;

class MapyCzApi
{
	const API_URL = 'https://pro.mapy.cz';

	private const API_ENDPOINT_POI = '/poiagg';
	private const API_ENDPOINT_PANORAMA = '/panorpc';

	private const API_METHOD_DETAIL = 'detail';
	private const API_METHOD_LOOKUP_BOX = 'lookupbox';
	private const API_METHOD_GET_NEIGHBOURS = 'getneighbours';

	/** @throws MapyCzApiException|\JsonException */
	public function loadPoiDetails(string $source, int $id): PlaceType
	{
		$xmlBody = $this->generateXmlRequest(self::API_METHOD_DETAIL, $source, $id);
		$response = $this->makeApiRequest(self::API_ENDPOINT_POI, $xmlBody);
		return PlaceType::cast($response->poi);
	}

	/** @throws MapyCzApiException|\JsonException */
	public function loadPanoramaDetails(int $id): PanoramaType
	{
		$body = $this->generateXmlRequest(self::API_METHOD_DETAIL, $id);
		$response = $this->makeApiRequest(self::API_ENDPOINT_PANORAMA, $body);
		return PanoramaType::cast($response->result);
	}

	/**
	 * @return PanoramaNeighbourType[]
	 * @throws MapyCzApiException|\JsonException
	 */
	public function loadPanoramaNeighbours(int $id): array
	{
		$body = $this->generateXmlRequest(self::API_METHOD_GET_NEIGHBOURS, $id);
		$response = $this->makeApiRequest(self::API_ENDPOINT_PANORAMA, $body);
		return PanoramaNeighbourType::createFromResponse($response);
	}

	/**
	 * @return LookupBoxPlaceType[]
	 * @throws MapyCzApiException|\JsonException
	 */
	public function loadLookupBox(float $lon1, float $lat1, float $lon2, float $lat2, $options): array
	{
		$xmlBody = $this->generateXmlRequest(self::API_METHOD_LOOKUP_BOX, $lon1, $lat1, $lon2, $lat2, $options);
		dump($xmlBody);
		$response = $this->makeApiRequest(self::API_ENDPOINT_POI, $xmlBody);
		$places = [];
		foreach ($response->poi as $poi) {
			$places[] = LookupBoxPlaceType::cast($poi);
		}
		return $places;
	}

	/** @throws MapyCzApiException */
	private function makeApiRequest(string $endpoint, \SimpleXMLElement $rawPostContent): \stdClass
	{
		$response = \Utils\General::fileGetContents(self::API_URL . $endpoint, [
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $rawPostContent->asXML(),
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Content-Type: text/xml',
			],
		]);
		$content = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
		if ($content->status === 200 && mb_strtolower($content->statusMessage) === 'ok') {
			return $content;
		} else {
			throw new MapyCzApiException($content->statusMessage, $content->status);
		}
	}

	private function generateXmlRequest(string $methodName, ...$params): \SimpleXMLElement
	{
		/**
		 * Workaround to create XML without root element.
		 * @see https://stackoverflow.com/questions/486757/how-to-generate-xml-file-dynamically-using-php#comment22868318_487282
		 * @see https://www.php.net/manual/en/simplexmlelement.construct.php#119447
		 */
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><methodCall></methodCall>');
		$xml->addChild('methodName', $methodName);
		$methodParams = $xml->addChild('params');
		foreach ($params as $param) {
			$xmlParam = $methodParams->addChild('param');
			$xmlValue = $xmlParam->addChild('value');
			if (is_int($param)) {
				$xmlValue->addChild('int', strval($param));
			} else if (is_double($param)) {
				$xmlValue->addChild('double', strval($param));
			} else if (is_string($param)) {
				$xmlValue->addChild('string', $param);
			} else if ($param instanceof \stdClass) {
				$xmlStruct = $xmlValue->addChild('struct');
				foreach ($param as $structName => $structValue) {
					$xmlStructMember = $xmlStruct->addChild('member');
					$xmlStructMember->addChild('name', $structName);
					$xmlStructMemberValue = $xmlStructMember->addChild('value');
					if (is_int($structValue)) {
						$xmlStructMemberValue->addChild('int', strval($structValue));
					} else if (is_string($structValue)) {
						$xmlStructMemberValue->addChild('string', $structValue);
					} else {
						throw new \InvalidArgumentException(sprintf('Unexpected struct type "%s" of parameter.', gettype($param)));
					}
				}
			} else {
				throw new \InvalidArgumentException(sprintf('Unexpected type "%s" of parameter.', gettype($param)));
			}
		}
		return $xml;
	}
}
