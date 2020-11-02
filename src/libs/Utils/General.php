<?php declare(strict_types=1);

namespace App\Utils;

class General
{
	/**
	 * Format seconds into human readable format
	 *
	 * @param int $seconds (1386203)
	 * @return string human readable formatted (16d 1h 3m 23s)
	 */
	public static function sToHuman(int $seconds): string
	{
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

	/**
	 * CURL request with very simple settings
	 *
	 * @param string $url URL to be loaded
	 * @param array<int, mixed> $curlOpts indexed array of options to curl_setopt()
	 * @return string content of requested page
	 * @throws \Exception if error occured or page returns no content
	 * @author https://gist.github.com/DJTommek/97048e875a91b67123b0c544bc46c116
	 */
	public static function fileGetContents(string $url, array $curlOpts = []): string
	{
		$curl = curl_init($url);
		if ($curl === false) {
			throw new \Exception('CURL can\'t be initialited.');
		}
		$curlOpts[CURLOPT_RETURNTRANSFER] = true;
		$curlOpts[CURLOPT_HEADER] = true;
		curl_setopt_array($curl, $curlOpts);
		/** @var string|false $curlResponse */
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
	 * @return array<string, string> indexed array of all headers
	 * @throws \Exception if CURL error occured
	 */
	public static function getHeaders(string $url, array $curlOpts = []): array
	{
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

	/** See tests for this method for detail info. */
	public static function checkIfValueInHeaderMatchArray(string $headerValue, array $haystack): bool
	{
		$values = explode(';', mb_strtolower($headerValue));
		foreach ($values as $value) {
			if (in_array($value, $haystack)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get all available URLs from string
	 *
	 * @author https://stackoverflow.com/a/36564776/3334403
	 */
	public static function getUrls(string $string): array
	{
		preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|(?:[^,[:punct:]\s]|/))#', $string, $matches);
		return $matches[0];
	}

	/**
	 * Swap content of two variables with each other
	 *
	 * @param $var1
	 * @param $var2
	 */
	public static function swap(&$var1, &$var2)
	{
		$tmp = $var1;
		$var1 = $var2;
		$var2 = $tmp;
	}

	/** Smarter parse_url() */
	public static function parseUrl(string $url): array
	{
		$parsedUrl = parse_url($url);
		if (isset($parsedUrl['query'])) {
			parse_str($parsedUrl['query'], $parsedUrl['query']);
		}
		return $parsedUrl;
	}

	/**
	 * @param ?string $prefix constant name must start with $prefix
	 * @return array indexed array of constantName => constantValue
	 * @throws \ReflectionException
	 */
	public static function getClassConstants(string $class, ?string $prefix = null): array
	{
		$reflection = new \ReflectionClass($class);
		$constants = $reflection->getConstants();

		if (is_null($prefix)) {
			return $constants;
		} else {
			return array_filter($constants, function ($constant) use ($prefix) {
				return StringUtils::startWith($constant, $prefix);
			}, ARRAY_FILTER_USE_KEY);
		}
	}

	/**
	 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
	 *
	 * @author Torleif Berger, Lorenzo Stanco
	 * @link http://stackoverflow.com/a/15025877/995958
	 * @link https://gist.github.com/lorenzos/1711e81a9162320fde20
	 * @license http://creativecommons.org/licenses/by/3.0/
	 */
	public static function tail($filepath, $lines = 1, $adaptive = true)
	{
		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;

		// Sets buffer size, according to the number of lines to retrieve.
		// This gives a performance boost when reading a few lines from the file.
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {

			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");

		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {

			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);

		}

		// Close file and return
		fclose($f);
		return trim($output);

	}
}
