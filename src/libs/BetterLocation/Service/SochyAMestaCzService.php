<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Czech art of 1950s - 1980s in public space.
 */
final class SochyAMestaCzService extends AbstractService
{
	const int ID = 69;
	const string NAME = 'SochyAMesta.cz';
	public const array TAGS = [];

	const string LINK = 'https://sochyamesta.cz';

	const string PATH_REGEX = '/^\/zaznam\/([0-9]+)\/?$/';

	/**
	 * Coordinates are available in settings of map widget, eg. "deltas":{"d_0":{"lat":"49.308789604289366","lng":"14.147263169288635"}}
	 */
	const string COORDS_REGEX = '/"deltas":\{"d_0":\{"lat":"(-?[0-9.]+)","lng":"(-?[0-9.]+)"\}/';
	const string NAME_REGEX = '/<span class="pinkText wrap">(.+?)<\/span>/s';

	private ?int $recordId = null;

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	#[\Override]
	public function validate(): bool
	{
		if (
			$this->url
			&& $this->url->getDomain(2) === 'sochyamesta.cz'
			&& preg_match(self::PATH_REGEX, $this->url->getPath(), $matches)
		) {
			$this->recordId = Strict::intval($matches[1]);
			return true;
		}
		return false;
	}

	#[\Override]
	public function process(): void
	{
		$body = $this->requestor->get($this->url, Config::CACHE_TTL_SOCHY_A_MESTA_CZ);

		if (!preg_match(self::COORDS_REGEX, $body, $matches)) {
			Debugger::log($body, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}

		$location = new BetterLocation($this->input, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class);

		$name = null;
		if (preg_match(self::NAME_REGEX, $body, $nameMatches)) {
			$name = trim(html_entity_decode(strip_tags($nameMatches[1])));
		}
		$location->setPrefixTextInLink(htmlspecialchars($name ?: ('#' . $this->recordId)));

		$this->collection->add($location);
	}
}
