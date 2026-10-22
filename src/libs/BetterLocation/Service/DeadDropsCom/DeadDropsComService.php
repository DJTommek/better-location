<?php declare(strict_types=1);

namespace App\BetterLocation\Service\DeadDropsCom;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Formatter;
use App\Utils\Requestor;
use Tracy\Debugger;
use Tracy\ILogger;

final class DeadDropsComService extends AbstractService
{
	const int ID = 67;
	const string NAME = 'DeadDrops.com';
	public const array TAGS = [];

	const string LINK = 'https://deaddrops.com';

	const string PATH_REGEX = '/^\/db(\/(index\.php)?)?$/';
	const string ID_REGEX = '/^[0-9]+$/';

	/**
	 * Show warning if status of dead drop was not changed for longer than this.
	 */
	const string STATUS_OUTDATED_INTERVAL = 'P1Y';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	#[\Override]
	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'deaddrops.com' &&
			preg_match(self::PATH_REGEX, $this->url->getPath()) &&
			$this->url->getQueryParameter('page') === 'view' &&
			is_string($this->url->getQueryParameter('id')) &&
			preg_match(self::ID_REGEX, $this->url->getQueryParameter('id'))
		);
	}

	#[\Override]
	public function process(): void
	{
		$body = $this->requestor->get($this->url, Config::CACHE_TTL_DEAD_DROPS_COM);

		$deadDrop = DeadDrop::fromHtml($body);
		if ($deadDrop === null) {
			Debugger::log($body, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}

		$location = new BetterLocation($this->input, $deadDrop->coords->lat, $deadDrop->coords->lon, self::class);
		$location->setPrefixTextInLink(htmlspecialchars($deadDrop->name ?? ('#' . $this->url->getQueryParameter('id'))));

		$statusDescription = $this->statusDescription($deadDrop, new \DateTimeImmutable());
		if ($statusDescription !== null) {
			$location->addDescription($statusDescription);
		}

		if ($deadDrop->pictures !== []) {
			$pictureLinks = [];
			foreach ($deadDrop->pictures as $label => $pictureUrl) {
				$pictureLinks[] = Formatter::htmlLink($pictureUrl, htmlspecialchars($label));
			}
			$location->addDescription(sprintf(
				'Pictures: %s',
				implode(', ', $pictureLinks),
			));
		}

		$this->collection->add($location);
	}

	public function statusDescription(DeadDrop $deadDrop, \DateTimeInterface $now): ?string
	{
		if ($deadDrop->status === null) {
			return null;
		}

		$changedText = '';
		if ($deadDrop->statusChanged !== null) {
			$changedText = ' since ' . TelegramHelper::datetimeFormatSmart($deadDrop->statusChanged);
		}

		if ($deadDrop->isWorking() === false) {
			return sprintf('%s Status: <b>%s</b>%s', Icons::WARNING, htmlspecialchars($deadDrop->status), $changedText);
		}

		if ($deadDrop->isStatusOutdated($now, new \DateInterval(self::STATUS_OUTDATED_INTERVAL))) {
			return sprintf('%s Status: %s%s, might be outdated', Icons::WARNING, htmlspecialchars($deadDrop->status), $changedText);
		}

		return null;
	}
}
