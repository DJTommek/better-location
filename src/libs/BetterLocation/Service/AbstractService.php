<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Factory;
use App\Utils\Strict;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;

abstract class AbstractService
{
	/**
	 * Tags, that define each service. See TAG_* constants in ServicesManager
	 * Here are default tags for all services, that can be overriden.
	 */
	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
	];

	private bool $processed = false;

	/**
	 * Raw input as-is
	 *
	 * @readonly
	 */
	protected string $input;

	/**
	 * URL generated from input (if possible) and after passing constructor it will never change.
	 *
	 * @readonly
	 */
	protected ?UrlImmutable $inputUrl;

	/**
	 * URL initially generated from inputUrl, but can be changed, eg. if input URL is alias or redirecting to another URL.
	 *
	 * Example URL https://www.geocaching.com/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4
	 * will be replaced with https://www.geocaching.com/geocache/GC85BTR_antivirova-cache?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4
	 *
	 * Note: this is string representation of URL before passing it into Url() object. This is helpful if URL contains
	 * non-standard representation of data which would be dropped when parsed, eg. multiple query parameters with the same name,
	 * Example ('ut' and 'ud' are used multiple times): https://en.mapy.cz/turisticka?vlastni-body&x=13.9183152&y=49.9501554&z=11&ut=New%20%20POI&ut=New%20%20POI&ut=New%20%20POI&ut=New%20%20POI&uc=9fJgGxW.HqkQ0xWn3F9fWDGxX0wGlQ0xW9oq&ud=49%C2%B055%2710.378%22N%2C%2013%C2%B046%2749.078%22E&ud=13%C2%B048%2734.135%22E%2049%C2%B052%2746.280%22N&ud=Broumy%2C%20Beroun&ud=B%C5%99ezov%C3%A1%2C%20Beroun
	 */
	protected ?string $rawUrl = null;

	/**
	 * Parsed version of $rawUrl
	 * Hostname part of URL is lowercased
	 */
	protected ?Url $url = null;

	protected BetterLocationCollection $collection;

	/** Helper to store data between methods, eg. isValid() and process() */
	protected \stdClass $data;

	public function setInput(string $input): self
	{
		$this->input = $input;
		if (Strict::isUrl($input)) {
			$this->inputUrl = Strict::urlImmutable($input);
			$this->rawUrl = $this->input;
			$this->url = Strict::url($this->inputUrl);
			$this->url->setHost(mb_strtolower($this->url->getHost())); // Convert host to lowercase
		}
		$this->collection = new BetterLocationCollection();
		$this->data = new \stdClass();

		return $this;
	}

	public function isValid(): bool
	{
		return false;
	}

	/**
	 * @throws NotSupportedException
	 * @throws InvalidLocationException
	 */
	public function process(): void
	{
		throw new NotSupportedException('Processing is not implemented.');
	}

	/**
	 * @param array<mixed,mixed> $options
	 * @throws NotSupportedException
	 * @deprecated use GetShareLink() or getDriveLink()
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException(sprintf('%s (ID %d) does not support drive link.', static::getName(), static::getId()));
		} else {
			throw new NotSupportedException(sprintf('%s (ID %d) does not support share link.', static::getName(), static::getId()));
		}
	}

	/**
	 * Return link to open location in specific app with highlighted location if possible
	 *
	 * @param array<mixed,mixed> $options
	 * @throws NotSupportedException
	 */
	public static function getShareLink(float $lat, float $lon, array $options = []): ?string
	{
		return static::getLink($lat, $lon, false, $options);
	}

	/**
	 * Return link to open location optimized for quicker navigation (eg. autostart)
	 *
	 * @param array<mixed,mixed> $options
	 * @throws NotSupportedException
	 */
	public static function getDriveLink(float $lat, float $lon, array $options = []): ?string
	{
		return static::getLink($lat, $lon, true, $options);
	}

	/**
	 * Return link to generate static image
	 *
	 * @param array<mixed,mixed> $options
	 * @throws NotSupportedException
	 */
	public static function getScreenshotLink(float $lat, float $lon, array $options = []): ?string
	{
		throw new NotSupportedException('Static image link is not supported.');
	}

	/**
	 * Text representation of location in given format.
	 *
	 * @throws NotSupportedException
	 */
	static public function getShareText(float $lat, float $lon): ?string
	{
		throw new NotSupportedException('Share text is not supported.');
	}

	final public function getCollection(): BetterLocationCollection
	{
		return $this->collection;
	}

	final public function getFirst(): BetterLocation
	{
		return $this->collection->getFirst();
	}

	/** @return string[] */
	public static function getConstants(): array
	{
		return [];
	}

	public static function getName(bool $short = false): string
	{
		if ($short && defined('static::NAME_SHORT')) {
			return static::NAME_SHORT;
		}

		if (defined('static::NAME') === false) {
			throw new \RuntimeException(sprintf('%s is missing class constant NAME', static::class));
		}

		return static::NAME;
	}

	public static function getId(): int
	{
		if (defined('static::ID') === false) {
			throw new \RuntimeException(sprintf('%s is missing class constant ID', static::class));
		}

		return static::ID;
	}

	public function getData(): \stdClass
	{
		return $this->data;
	}

	public static function isValidStatic(string $input): bool
	{
		$instance = Factory::getContainer()->get(static::class);
		assert($instance instanceof static);
		$instance->setInput($input);
		return $instance->isValid();
	}

	public static function processStatic(string $input): self
	{
		$instance = Factory::getContainer()->get(static::class);
		assert($instance instanceof static);
		$instance->setInput($input);
		if ($instance->isValid() === false) {
			throw new \InvalidArgumentException('Input is not valid.');
		}
		$instance->process();
		return $instance;
	}

	/**
	 * Find locations in provided text.
	 *
	 * @throws NotSupportedException
	 */
	public static function findInText(string $text): BetterLocationCollection
	{
		throw new NotSupportedException(sprintf('%s is not available for "%s"', __METHOD__, static::class));
	}

	/**
	 * @param int-mask-of<ServicesManager::TAG_*> $tag
	 */
	public static function hasTag(int $tag): bool
	{
		return in_array($tag, static::TAGS, true);
	}
}
