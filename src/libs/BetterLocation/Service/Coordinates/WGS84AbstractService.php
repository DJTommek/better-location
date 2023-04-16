<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use Tracy\Debugger;
use Tracy\ILogger;

abstract class WGS84AbstractService extends AbstractService
{
	private const RE_OPTIONAL_HEMISPHERE = '([-+NSWE])?';

	/**
	 * Loose version, migh be buggy, eg:
	 * N52.1111 E12.2222 S53.1111 W13.2222
	 */
	private const RE_BETWEEN_COORDS = '[;.,\\s]{1,3}';

	/**
	 * Must be used Unicode version instead of ° and regex has to contain modifier "u", eg: /someRegex/u
	 * @see https://stackoverflow.com/questions/7211541/having-trouble-with-a-preg-match-all-and-a-degree-symbol/20429497
	 */
//	private const RE_OPTIONAL_DEGREE_SIGN = '(?:\x{00B0})?';
	private const RE_OPTIONAL_DEGREE_SIGN = '°?';

	/**
	 * Strict less-buggy version
	 * N52.1111 E12.2222 S53.1111 W13.2222
	 */
//	private const RE_SPACE_BETWEEN_COORDS = ', ?';

	private const RE_OPTIONAL_SPACE = ' {0,4}';

	private const RE_OPTIONAL_SEMICOLON = ':?';

	abstract protected static function getReCoords(): string;

	public static function getRegex(): string
	{
		return
			self::RE_OPTIONAL_HEMISPHERE .
			self::RE_OPTIONAL_SEMICOLON .
			self::RE_OPTIONAL_SPACE . self::RE_OPTIONAL_DEGREE_SIGN . self::RE_OPTIONAL_SPACE .
			static::getReCoords() .
			self::RE_OPTIONAL_SPACE . self::RE_OPTIONAL_DEGREE_SIGN . self::RE_OPTIONAL_SPACE .
			self::RE_OPTIONAL_HEMISPHERE .

			self::RE_BETWEEN_COORDS .

			self::RE_OPTIONAL_HEMISPHERE .
			self::RE_OPTIONAL_SEMICOLON .
			self::RE_OPTIONAL_SPACE . self::RE_OPTIONAL_DEGREE_SIGN . self::RE_OPTIONAL_SPACE .
			static::getReCoords() .
			self::RE_OPTIONAL_SPACE . self::RE_OPTIONAL_DEGREE_SIGN . self::RE_OPTIONAL_SPACE .
			self::RE_OPTIONAL_HEMISPHERE;
	}

	public function isValid(): bool
	{
		$input = str_replace('\'\'', '"', $this->input); // Replace two quotes as one doublequote
		if (preg_match('/^' . static::getRegex() . '$/iu', $input, $matches)) {
			$this->data->matches = $matches;
			return true;
		}
		return false;
	}


	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$text = str_replace('\'\'', '"', $text); // Replace two quotes as one doublequote
		if (preg_match_all('/' . self::getRegex() . '/iu', $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$coordsRaw = $matches[0][$i];
				$service = new static($coordsRaw);
				try {
					if ($service->isValid()) {
						$service->process();
						$collection->add($service->getCollection());
					} else {
						Debugger::log(sprintf('Coordinate input "%s" was findInText() but not validated', $coordsRaw), Debugger::ERROR);
					}
				} catch (InvalidLocationException $exception) {
					Debugger::log($exception, ILogger::DEBUG);
				}
			}
		}
		return $collection;
	}
}
