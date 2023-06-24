<?php declare(strict_types=1);

namespace App\Http;

use GuzzleHttp\Exception\GuzzleException;

class HttpException extends \Exception implements GuzzleException
{
}
