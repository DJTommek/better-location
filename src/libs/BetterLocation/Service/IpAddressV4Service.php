<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\MiniCurl\MiniCurl;

final class IpAddressV4Service extends AbstractService
{
    public const ID = 45;
    public const NAME = 'IP address';
    private const SEPARATOR = [' ', "\t", PHP_EOL, ',', ';', '=', '-'];
    private const IP_V4_MASK = '/(?:^|_)(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:$|_)/';

    public static function findInText(string $input): BetterLocationCollection
    {
        // replace all potential delimiters with identifiers pair
        // to correctly retrieve closely adjacent addresses
        // use  delimiter as identifier multiplies them 4 times :)

        $collection = new BetterLocationCollection();
        $input = str_replace(self::SEPARATOR, '__', $input);

        if (preg_match_all(self::IP_V4_MASK, $input, $matches)) {
            foreach ($matches[1] as $ipAddress) {
                if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    $response = self::loadApi($ipAddress);
                    $collection->add(new BetterLocation('$this->inputUrl', $response->lat, $response->lon, self::class));
                }
            }
        }
        return $collection;
    }

    private static function loadApi(string $ipAddress): \stdClass
    {
        $response = (new MiniCurl('http://ip-api.com/json/' . $ipAddress)) // 24.48.0.1
            ->allowCache(Config::CACHE_TTL_BANNERGRESS)
            ->run()
            ->getBody();
        return json_decode($response);
    }

}
