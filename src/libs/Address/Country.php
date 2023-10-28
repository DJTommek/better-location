<?php declare(strict_types=1);

namespace App\Address;

use League\ISO3166\ISO3166;

class Country implements \Stringable
{
	private const EMOJI_COUNTRY_CODE_OFFSET = 127397;

	public readonly string $code;
	public readonly string $displayname;

	/**
	 * @param string $code in Alpha-2 code - two characters, for example CZ (Czechia), US (United States of America), CH (Switzerland)
	 */
	public function __construct(
		string $code,
		string $displayname = null,
	) {
		if (!preg_match('/^[a-z]{2}$/i', $code)) {
			throw new \InvalidArgumentException('Country code must be two characters within A-Z range.');
		}
		$this->code = strtoupper($code);

		$this->displayname = trim($displayname ?? $this->code);
	}

	public function __toString(): string
	{
		return $this->displayname;
	}

	/**
	 * Get emoji representing flag of that country
	 *
	 * @example 'CZ' => 🇨🇿 (https://emojipedia.org/flag-czechia/)
	 * @author https://dev.to/jorik/country-code-to-flag-emoji-a21 (in Javascript)
	 *
	 * @return string Multi-byte string representing flag, eg 🇨🇿
	 */
	public function flagEmoji(): string
	{
		$chars = str_split($this->code);

		$codesForFlag = array_map(
			fn($char) => ord($char) + self::EMOJI_COUNTRY_CODE_OFFSET,
			$chars,
		);

		$flagBytes = array_map(
			fn($charCode) => mb_chr($charCode),
			$codesForFlag,
		);

		return join('', $flagBytes);
	}

	public static function fromNumericCode(string|int $code): self
	{
		$data = (new ISO3166())->numeric((string)$code);
		return new self($data['alpha2'], $data['name']);
	}

	public static function fromAlpha2Code(string $code): self
	{
		$data = (new ISO3166())->alpha2($code);
		return new self($data['alpha2'], $data['name']);
	}

	public static function fromAlpha3Code(string $code): self
	{
		$data = (new ISO3166())->alpha3($code);
		return new self($data['alpha2'], $data['name']);
	}
}
