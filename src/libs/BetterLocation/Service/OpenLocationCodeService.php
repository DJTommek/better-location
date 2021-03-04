<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use OpenLocationCode\OpenLocationCode;

final class OpenLocationCodeService extends AbstractServiceNew
{
	const NAME = 'OLC';

	const LINK = 'https://plus.codes/';

	const DEFAULT_CODE_LENGTH = 12;

	const RE = '/^([23456789C][23456789CFGHJMPQRV][23456789CFGHJMPQRVWX]{6}\+[23456789CFGHJMPQRVWX]{2,3})$/i';
	const RE_IN_STRING = '/(^|\s)([23456789C][23456789CFGHJMPQRV][23456789CFGHJMPQRVWX]{6}\+[23456789CFGHJMPQRVWX]{2,3})(\s|$)/i';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws \Exception
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$plusCode = OpenLocationCode::encode($lat, $lon, self::DEFAULT_CODE_LENGTH);
			return self::LINK . $plusCode;
		}
	}

	public function isValid(): bool
	{
		return $this->isUrl() || $this->isPlusCode();
	}

	public function process()
	{
		$coords = OpenLocationCode::decode($this->data->plusCode);
		$betterLocation = new BetterLocation($this->input, $coords['latitudeCenter'], $coords['longitudeCenter'], self::class);
		$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s</a> <code>%s</code>: ',
			self::getLink($coords['latitudeCenter'], $coords['longitudeCenter']),
			self::NAME,
			$this->data->plusCode
		));
		$this->collection->add($betterLocation);
	}

	/** @example https://plus.codes/8FXP74WG+XHW */
	public function isUrl(): bool
	{
		if ($this->url->getDomain(2) === 'plus.codes') {
			$plusCode = ltrim($this->url->getPath(), '/');
			if (OpenLocationCode::isFull($plusCode)) {
				$this->data->plusCode = $plusCode;
				return true;
			}
		}
		return false;
	}

	/** @example 8FXP74WG+XHW */
	public function isPlusCode(): bool
	{
		if (OpenLocationCode::isFull($this->input)) {
			$this->data->plusCode = $this->input;
			return true;
		}
		return false;
	}
}
