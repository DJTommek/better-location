<?php declare(strict_types=1);

namespace App\IpApiCom;

use App\Utils\Coordinates;
use Sammyjo20\Saloon\Http\SaloonResponse;
use Tracy\Debugger;

class Response extends SaloonResponse
{
	public const STATUS_SUCCESS = 'success';
	public const STATUS_FAIL = 'fail';

	public const MESSAGE_PRIVATE_RANGE = 'private range';
	public const MESSAGE_RESERVED_RANGE = 'reserved range';
	public const MESSAGE_INVALID_QUERY = 'invalid query';

	private ?Coordinates $coordinates = null;

	public function ok(): bool
	{
		if (parent::ok() === false) {
			return false;
		}
		// API is responding HTTP 200 even if there is some error
		if ($this->json('status') !== self::STATUS_SUCCESS) {
			Debugger::log(sprintf('Invalid response from ip-api.com: %s', $this->response->getBody()), Debugger::ERROR);
			return false;
		}
		return true;
	}

	public function coordinates(): Coordinates
	{
		if ($this->coordinates === null) {
			$this->coordinates = new Coordinates($this->json('lat'), $this->json('lon'));
		}
		return $this->coordinates;
	}

	public function address(): string
	{
		return sprintf('%s, %s, %s, %s',
			$this->json('city'),
			$this->json('zip'),
			$this->json('regionName'),
			$this->json('country'),
		);
	}
}
