<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\Strict;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;

abstract class AbstractService
{
	/** @var bool */
	private $processed = false;

	/**
	 * Raw input as-is
	 *
	 * @readonly
	 * @var string
	 */
	protected $input;

	/**
	 * URL generated from input (if possible) and after passing constructor it will never change.
	 *
	 * @readonly
	 * @var ?UrlImmutable
	 */
	protected $inputUrl;

	/**
	 * URL initially generated from inputUrl, but can be changed, eg. if input URL is alias or redirecting to another URL.
	 * Hostname part of URL is lowercased
	 *
	 * Example URL https://www.geocaching.com/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4
	 * will be replaced with https://www.geocaching.com/geocache/GC85BTR_antivirova-cache?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4
	 *
	 * @var ?Url
	 */
	protected $url;
	/** @var BetterLocationCollection */
	protected $collection;

	/** @var \stdClass Helper to store data between methods, eg. isValid() and process() */
	protected $data;

	public final function __construct(string $input)
	{
		$this->input = $input;
		if (Strict::isUrl($input)) {
			$this->inputUrl = Strict::urlImmutable($input);
			$this->url = Strict::url($this->inputUrl);
			$this->url->setHost(mb_strtolower($this->url->getHost())); // Convert host to lowercase
		}
		$this->collection = new BetterLocationCollection();
		$this->data = new \stdClass();

		if (method_exists($this, 'beforeStart')) {
			$this->beforeStart();
		}
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
	 * @throws NotSupportedException
	 * @deprecated use GetShareLink() or getDriveLink()
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	/**
	 * Return link to open location in specific app with highlighted location if possible
	 *
	 * @throws NotSupportedException
	 */
	public static function getShareLink(float $lat, float $lon): string
	{
		throw new NotSupportedException('Share link is not supported.');
	}

	/**
	 * Return link to open location optimized for quicker navigation (eg. autostart)
	 *
	 * @throws NotSupportedException
	 */
	public static function getDriveLink(float $lat, float $lon): string
	{
		throw new NotSupportedException('Drive link is not supported.');
	}

	/**
	 * @throws NotSupportedException
	 */
	static public function getShareText(float $lat, float $lon): string
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
		if ($short && defined(sprintf('%s::%s', static::class, 'NAME_SHORT'))) {
			return static::NAME_SHORT;
		} else {
			return static::NAME;
		}
	}

	public function getData(): \stdClass
	{
		return $this->data;
	}

	public static function isValidStatic(string $input): bool
	{
		$instance = new static($input);
		return $instance->isValid();
	}

	public static function processStatic(string $input): self
	{
		$instance = new static($input);
		if ($instance->isValid() === false) {
			throw new \InvalidArgumentException('Input is not valid.');
		}
		$instance->process();
		return $instance;
	}
}
