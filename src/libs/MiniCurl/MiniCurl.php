<?php declare(strict_types=1);

namespace App\MiniCurl;

use App\MiniCurl\Exceptions\ExecException;
use App\MiniCurl\Exceptions\InitException;
use App\MiniCurl\Exceptions\InvalidResponseException;

class MiniCurl
{

    private $curl;
    private $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
    ];

    public function __construct(string $url)
    {
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
     * Perform request and return Response
     *
     * @param int|null $requireResponseCode Throw exception if response code is different
     * @return Response
     * @throws ExecException
     * @throws InvalidResponseException
     */
    public function run(?int $requireResponseCode = 200): Response
    {
        curl_setopt_array($this->curl, $this->options);
        $curlResponse = curl_exec($this->curl);
        if ($curlResponse === false) {
            $curlErrno = curl_errno($this->curl);
            throw new ExecException(sprintf('CURL request error %s: "%s"', $curlErrno, curl_error($this->curl)));
        }
        $response = new Response($curlResponse, curl_getinfo($this->curl));
        if (is_null($requireResponseCode) === false && $response->getCode() !== $requireResponseCode) {
            throw new InvalidResponseException(sprintf('Invalid response code "%d" but required "%d".', $response->getCode(), $requireResponseCode));
        }
        return $response;
    }
}
