<?php declare(strict_types=1);

namespace App\IngressPortalnik;

/**
 * Error response from IvoPortalnik API (RFC 9457 problem details) or unexpected response.
 */
class PortalnikException extends \RuntimeException
{
	public function __construct(
		string $message,
		public readonly ?int $httpStatus = null,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, $httpStatus ?? 0, $previous);
	}
}
