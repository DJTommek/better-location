<?php declare(strict_types=1);

namespace App\BetterLocation\Service\DeadDropsCom;

use App\Utils\Strict;
use App\Utils\Utils;
use DJTommek\Coordinates\CoordinatesImmutable;

/**
 * Detail of one dead drop parsed from HTML page, eg. https://deaddrops.com/db/?page=view&id=2413
 */
final readonly class DeadDrop
{
	public const string STATUS_WORKING = 'working';
	public const string STATUS_UNCONFIRMED = 'unconfirmed, may be broken';
	public const string STATUS_BROKEN = 'broken/dead/stolen/gone';

	private const string BASE_URL = 'https://deaddrops.com/db/';

	/**
	 * @param array<string, string> $pictures Label => absolute URL to fullsize picture
	 */
	public function __construct(
		public CoordinatesImmutable $coords,
		public ?string $name,
		public ?string $type,
		public ?string $size,
		public ?\DateTimeImmutable $dateCreated,
		public ?string $address,
		public ?string $status,
		public ?\DateTimeImmutable $statusChanged,
		public ?string $about,
		public array $pictures,
	) {
	}

	public function isWorking(): bool
	{
		return $this->status === self::STATUS_WORKING;
	}

	/**
	 * Status was not changed for too long, so it might not be reliable anymore.
	 */
	public function isStatusOutdated(\DateTimeInterface $now, \DateInterval $maxAge): bool
	{
		if ($this->statusChanged === null) {
			return false;
		}
		$threshold = \DateTimeImmutable::createFromInterface($now)->sub($maxAge);
		return $this->statusChanged < $threshold;
	}

	/**
	 * @return ?self Null if coordinates are not available (eg. dead drop does not exist)
	 */
	public static function fromHtml(string $html): ?self
	{
		$dom = Utils::domFromUTF8($html);
		$finder = new \DOMXPath($dom);

		$coords = self::parseCoords($finder);
		if ($coords === null) {
			return null;
		}

		$info = self::parseInformation($finder);
		[$status, $statusChanged] = self::parseStatus($finder);

		return new self(
			coords: $coords,
			name: $info['Name'] ?? null,
			type: $info['Drop-Type'] ?? null,
			size: $info['Size'] ?? null,
			dateCreated: self::parseDate($info['Date Created'] ?? null),
			address: $info['Address'] ?? null,
			status: $status,
			statusChanged: $statusChanged,
			about: self::parseAbout($finder),
			pictures: self::parsePictures($finder),
		);
	}

	/**
	 * Coordinates in link text are rounded and without sign, so use more precise coordinates from link to OpenStreetMap
	 */
	private static function parseCoords(\DOMXPath $finder): ?CoordinatesImmutable
	{
		$href = $finder->query('//a[@id="coord_link"]/@href')->item(0)?->textContent;
		if ($href === null) {
			return null;
		}
		parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
		if (!isset($query['mlat'], $query['mlon']) || !is_string($query['mlat']) || !is_string($query['mlon'])) {
			return null;
		}
		return new CoordinatesImmutable(Strict::floatval($query['mlat']), Strict::floatval($query['mlon']));
	}

	/**
	 * @return array<string, string> Non-empty values from "Information" table, indexed by label without colon
	 */
	private static function parseInformation(\DOMXPath $finder): array
	{
		$result = [];
		foreach ($finder->query('//tr[count(td) = 2][td[1]/b]') as $row) {
			assert($row instanceof \DOMElement);
			$cells = $row->getElementsByTagName('td');
			$label = trim($cells->item(0)->textContent);
			if (!str_ends_with($label, ':')) {
				continue;
			}
			$value = self::normalizeText($cells->item(1)->textContent);
			if ($value !== null) {
				$result[rtrim($label, ': ')] = $value;
			}
		}
		return $result;
	}

	/**
	 * @return array{?string, ?\DateTimeImmutable}
	 */
	private static function parseStatus(\DOMXPath $finder): array
	{
		$statusText = $finder->query('//td[starts-with(normalize-space(.), "Status was set to")]')->item(0)?->textContent;
		if ($statusText === null) {
			return [null, null];
		}
		$statusText = self::normalizeText($statusText) ?? '';
		if (!preg_match('/^Status was set to (.+?)(?: on ([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) \(UTC\))?$/', $statusText, $matches)) {
			return [null, null];
		}
		$changed = isset($matches[2]) ? new \DateTimeImmutable($matches[2], new \DateTimeZone('UTC')) : null;
		return [$matches[1], $changed];
	}

	private static function parseDate(?string $date): ?\DateTimeImmutable
	{
		if ($date === null) {
			return null;
		}
		$result = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone('UTC'));
		return $result === false ? null : $result;
	}

	private static function parseAbout(\DOMXPath $finder): ?string
	{
		$about = $finder->query('//td[@class="td_menu"][normalize-space(b) = "About"]/parent::tr/following-sibling::tr[1]/td')->item(0)?->textContent;
		return $about === null ? null : self::normalizeText($about);
	}

	/**
	 * @return array<string, string>
	 */
	private static function parsePictures(\DOMXPath $finder): array
	{
		$result = [];
		foreach ($finder->query('//div[@class="picture-card"]') as $card) {
			$label = self::normalizeText($finder->query('.//div[@class="picture-label"]', $card)->item(0)->textContent ?? '');
			$href = $finder->query('.//a/@href', $card)->item(0)?->textContent;
			if ($label === null || $href === null) {
				continue;
			}
			$result[$label] = preg_match('/^https?:\/\//', $href) ? $href : self::BASE_URL . ltrim($href, './');
		}
		return $result;
	}

	private static function normalizeText(string $text): ?string
	{
		$text = trim(preg_replace('/\s+/u', ' ', $text));
		return $text === '' ? null : $text;
	}
}
