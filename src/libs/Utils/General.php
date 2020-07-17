<?php

declare(strict_types=1);

namespace Utils;

class General
{

	/**
	 * Format seconds into human readable format
	 *
	 * @param int $seconds (1386203)
	 * @return string human readable formatted (16d 1h 3m 23s)
	 */
	public static function sToHuman(int $seconds): string {
		$s = floor(($seconds % 60));
		$m = floor(($seconds / (60)) % 60);
		$h = floor(($seconds / (60 * 60)) % 24);
		$d = floor(($seconds / (60 * 60 * 24)));

		$result = '';
		$result .= ($d > 0 ? ' ' . $d . 'd' : '');
		$result .= ($h > 0 ? ' ' . $h . 'h' : '');
		$result .= ($m > 0 ? ' ' . $m . 'm' : '');
		$result .= ($s > 0 ? ' ' . $s . 's' : '');

		return trim($result);
	}

	/*
	 * Edit given size in bytes to human-read
	 * @author http://stackoverflow.com/a/5502088/3334403
	 */

	public static function sizeFormatted($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
		return number_format($bytes / pow(1024, $power), 2, '.', ' ') . ' ' . $units[$power];
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
		$curlInfo = curl_getinfo($curl);
		list($header, $body) = explode("\r\n\r\n", $curlResponse, 2);
		if ($curlInfo['http_code'] >= 500) {
			throw new \Exception(sprintf('Page responded with HTTP code %d: Text response: "%s"', $curlInfo['http_code'], $body));
		}
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
			$result[mb_strtolower($headerName)] = $headerValue;
		}
		return $result;
	}

	public static function permute($InArray, $InProcessedArray = []) {
		$ReturnArray = array();
		foreach ($InArray as $Key => $value) {
			$CopyArray = $InProcessedArray;
			$CopyArray[$Key] = $value;
			$TempArray = array_diff_key($InArray, $CopyArray);
			if (count($TempArray) == 0) {
				$ReturnArray[] = $CopyArray;
			} else {
				$ReturnArray = array_merge($ReturnArray, self::permute($TempArray, $CopyArray));
			}
		}
		return $ReturnArray;
	}


	/**
	 * Multi-byte replace text within a portion of a string
	 *
	 * @author https://www.php.net/manual/en/function.substr-replace.php#90146
	 * @see substr_replace()
	 * @param $string
	 * @param $replacement
	 * @param $start
	 * @param null $length
	 * @param null $encoding
	 * @return string|string[]
	 */
	public static function substrReplace(string $string, string $replacement, int $start = 0, $length = null, $encoding = null) {
		if (extension_loaded('mbstring') === true) {
			$string_length = (is_null($encoding) === true) ? mb_strlen($string) : mb_strlen($string, $encoding);

			if ($start < 0) {
				$start = max(0, $string_length + $start);
			} else if ($start > $string_length) {
				$start = $string_length;
			}

			if ($length < 0) {
				$length = max(0, $string_length - $start + $length);
			} else if ((is_null($length) === true) || ($length > $string_length)) {
				$length = $string_length;
			}

			if (($start + $length) > $string_length) {
				$length = $string_length - $start;
			}

			if (is_null($encoding) === true) {
				return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length);
			}

			return mb_substr($string, 0, $start, $encoding) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length, $encoding);
		}
		return (is_null($length) === true) ? substr_replace($string, $replacement, $start) : substr_replace($string, $replacement, $start, $length);
	}

	/**
	 * Get all available URLs from string
	 *
	 * @author https://stackoverflow.com/a/36564776/3334403
	 * @param string $string
	 * @return array list of URLs
	 */
	public static function getUrls(string $string): array {
		preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|(?:[^,[:punct:]\s]|/))#', $string, $matches);
		return $matches[0];
	}
}
