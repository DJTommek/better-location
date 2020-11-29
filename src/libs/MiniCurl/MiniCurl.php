<?php declare(strict_types=1);

namespace App\MiniCurl;

use App\Config;
use App\MiniCurl\Exceptions\ExecException;
use App\MiniCurl\Exceptions\InitException;
use App\MiniCurl\Exceptions\InvalidResponseException;
use Tracy\Debugger;

class MiniCurl
{
    const CACHE_FOLDER = Config::FOLDER_TEMP . '/mini-curl/cached-responses';
    private const EXPECTED_ENCODING = 'UTF-8';

    private $cacheAllowed = false;
    private $cacheTtl = 0;
    private $url;
    private $curl;
    /** @var array<int,mixed> Options to CURL method (predefined, can be updated) */
    private $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
    ];

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->curl = curl_init($url);
        if ($this->curl === false) {
            throw new InitException('CURL can\'t be initialited.');
        }
    }

    public function setCurlOption(int $optionKey, $optionValue): self
    {
        $this->options[$optionKey] = $optionValue;
        return $this;
    }

    /** @param array $curlOpts indexed array of options to curl_setopt() */
    public function setCurlOptions(array $curlOpts): self
    {
        foreach ($curlOpts as $curlOptionKey => $curlOptionValue) {
            $this->setCurlOption($curlOptionKey, $curlOptionValue);
        }
        return $this;
    }

    /**
     * Allow response caching.
     * If caching is enabled, just before request is created unique identifier of request (URL, all CURL options including POST parameters).
     * If there already exists cache with this ID, instead of making request, this cached response is returned.
     * If cache is missing, it will perform real request and save it as this ID for future usage.
     *
     * @param int $ttl How old in seconds from now cache can be to load. Even if cache is found but it's too old, it is not returned.<br>
     * Set 0 to completely disable caching (saving and loading) for this request.
     */
    public function allowCache(int $ttl): self
    {
        if ($ttl === 0) {
            $this->cacheAllowed = false;
        } else if ($ttl < 0) {
            throw new \InvalidArgumentException('Parameter $interval must be 0 or higher but "%d" provided.');
        } else {
            $this->cacheAllowed = true;
            if (is_dir(self::CACHE_FOLDER) === false && @mkdir(self::CACHE_FOLDER, 0755, true) === false) {
                throw new \Exception(sprintf('Error while creating folder for MiniCurl cached responses: "%s"', error_get_last()['message']));
            }
        }
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Perform request (or load cached response) and return Response
     *
     * @param int|null $requireResponseCode Throw exception if response code is different
     * @return Response
     * @throws ExecException
     * @throws InvalidResponseException
     */
    public function run(?int $requireResponseCode = 200): Response
    {
        if ($this->cacheAllowed) {
            $cacheId = $this->generateCacheId();
            if ($cachedResponse = $this->loadFromCache($cacheId)) {
            	Debugger::log(sprintf('Cache ID "%s" hit!', $cacheId), Debugger::DEBUG);
                return $cachedResponse;
            }
            Debugger::log(sprintf('Cache ID "%s" miss.', $cacheId), Debugger::DEBUG);
        }

        curl_setopt_array($this->curl, $this->options);
        $curlResponse = curl_exec($this->curl);
        if ($curlResponse === false) {
            $curlErrno = curl_errno($this->curl);
            throw new ExecException(sprintf('CURL request error %s: "%s"', $curlErrno, curl_error($this->curl)));
        }

		$detectedEncoding = mb_detect_encoding($curlResponse, [self::EXPECTED_ENCODING, 'ISO-8859-1']);
        if ($detectedEncoding !== self::EXPECTED_ENCODING) {
            $curlResponse = mb_convert_encoding($curlResponse, self::EXPECTED_ENCODING);
        }

        $curlInfo = curl_getinfo($this->curl);
        $response = new Response($curlResponse, $curlInfo);
        if (is_null($requireResponseCode) === false && $response->getCode() !== $requireResponseCode) {
            throw new InvalidResponseException(sprintf('Invalid response code "%d" but required "%d".', $response->getCode(), $requireResponseCode));
        }
	    if (isset($cacheId)) {
		    $this->saveToCache($cacheId, $curlResponse, $curlInfo);
	    }
        return $response;
    }

    private function generateCacheId(): string
    {
    	return Cache::generateId($this->url, $this->options);
    }

    private function getCachePath(string $cacheId)
    {
        return sprintf('%s/%s.json', self::CACHE_FOLDER, $cacheId);
    }

    private function loadFromCache(string $cacheId): ?Response
    {
    	$path = $this->getCachePath($cacheId);
        if (is_file($path)) {
            $cache = Cache::fromString(file_get_contents($path));
            if ($cache->datetime->getTimestamp() - time() + $this->cacheTtl > 0) { // check if cache is not expired
	            return new Response(
	                $cache->rawResponse,
	                $cache->curlInfo,
	                $cache->datetime
	            );
            }
        }
        return null;
    }

    private function saveToCache(string $cacheId, string $rawResponse, array $curlInfo): Cache
    {
    	$path = $this->getCachePath($cacheId);
    	$cache = new Cache($cacheId, $rawResponse, $curlInfo, time());
        if (@file_put_contents($path, $cache->__toString()) === false) {
            throw new \Exception(sprintf('Error while saving cache to file: "%s"', error_get_last()['message']));
        }
    	return $cache;
    }

}
