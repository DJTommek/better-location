<?php declare(strict_types=1);

namespace App\Google;

/**
 * @link https://developers.google.com/maps/documentation/streetview/metadata#status-codes
 * @link https://developers.google.com/maps/documentation/places/web-service/search-find-place#PlacesSearchStatus
 */
enum ResponseCodes: string
{
	case ZERO_RESULTS = 'ZERO_RESULTS';
	case NOT_FOUND = 'NOT_FOUND';
	case OK = 'OK';
	case INVALID_REQUESTS = 'INVALID_REQUEST';
	case OVER_QUERY_LIMIT = 'OVER_QUERY_LIMIT';
	case REQUEST_DENIED = 'REQUEST_DENIED';
	case UNKNOWN_ERROR = 'UNKNOWN_ERROR';

	case DEFAULT = 'Default'; // Default if none of above matched

	public static function customFrom(string $value): self
	{
		$result = self::tryFrom($value);
		if ($result === null) {
			return self::DEFAULT;
		}
		return $result;
	}

	public function isEmpty(): bool
	{
		return match ($this) {
			self::ZERO_RESULTS, self::NOT_FOUND => true,
			default => false,
		};
	}

	public function isError(): bool
	{
		return match ($this) {
			self::INVALID_REQUESTS, self::OVER_QUERY_LIMIT, self::REQUEST_DENIED, self::UNKNOWN_ERROR => true,
			default => false,
		};
	}
}
