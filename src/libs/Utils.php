<?php

class Utils
{
	static function requestJson(string $url, int $timeout = 10) {
		return json_decode(self::fileGetContents($url, [
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT => $timeout,
		]));
	}

	/**
	 * CURL request with very simple settings
	 *
	 * @param string $url URL to be loaded
	 * @param array $curlOpts indexed array of options to curl_setopt()
	 * @return string content of requested page
	 * @throws \Exception if error occured or page returns no content
	 * @author https://gist.github.com/DJTommek/97048e875a91b67123b0c544bc46c116
	 */
	public static function fileGetContents(string $url, array $curlOpts = []) {
		$curl = curl_init($url);
		$curlOpts[CURLOPT_RETURNTRANSFER] = true;
		$curlOpts[CURLOPT_HEADER] = true;
		curl_setopt_array($curl, $curlOpts);
		$curlResponse = curl_exec($curl);
		if ($curlResponse === false) {
			$curlErrno = curl_errno($curl);
			throw new \Exception(sprintf('CURL request error %s: "%s"', $curlErrno, curl_error($curl)));
		}
		list($header, $body) = explode("\r\n\r\n", $curlResponse, 2);
		if (!$body) {
			$responseCode = trim(explode(PHP_EOL, $header)[0]);
			throw new \Exception(sprintf('Bad response from CURL request from URL "%s": "%s".', $url, $responseCode));
		}
		return $body;

	}

	/**
	 * CURL version of vanilla PHP get_headers()
	 *
	 * @TODO cleanup and possibly merge with https://gist.github.com/DJTommek/97048e875a91b67123b0c544bc46c116
	 * @param string $url URL to be loaded
	 * @param array $curlOpts indexed array of options to curl_setopt()
	 * @return array indexed array of all headers
	 * @throws \Exception if CURL error occured
	 * @author https://gist.github.com/DJTommek/79ff19857847360d6c6a45618d7bac1d
	 */
	public static function getHeaders(string $url, array $curlOpts = []) {
		$curl = curl_init($url);
		$curlOpts[CURLOPT_RETURNTRANSFER] = true;
		$curlOpts[CURLOPT_HEADER] = true;
		curl_setopt_array($curl, $curlOpts);
		$curlResponse = curl_exec($curl);
		if ($curlResponse === false) {
			$curlErrno = curl_errno($curl);
			throw new \Exception(sprintf('CURL request error %s: "%s"', $curlErrno, curl_error($curl)));
		}
		list($headerStr, $body) = explode("\r\n\r\n", $curlResponse, 2);
		$headers = explode("\r\n", $headerStr);
		$result = [
			array_shift($headers),
		];
		foreach ($headers as $header) {
			list($headerName, $headerValue) = explode(': ', $header, 2);
			$result[$headerName] = $headerValue;
		}
		return $result;
	}
}