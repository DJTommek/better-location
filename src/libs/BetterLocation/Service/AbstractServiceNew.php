<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use Nette\Http\UrlImmutable;

abstract class AbstractServiceNew
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
	 * URL initially generated from input, but can be changed, eg. if input URL is alias or redirecting to another URL
	 *
	 * Example URL https://www.geocaching.com/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4
	 * will be replaced with https://www.geocaching.com/geocache/GC85BTR_antivirova-cache?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4
	 *
	 * @var ?UrlImmutable
	 */
	protected $url;

	protected $collection;

	/** @var \stdClass Helper to store data between methods (eg isValid and process) */
	protected $data;

	public function __construct(string $input)
	{
		$this->input = $input;
		try {
			$url = new UrlImmutable($input);
			$this->inputUrl = $url->withHost(mb_strtolower($url->getHost())); // Convert host to lowercase
			$this->url = $this->inputUrl;
		} catch (\Nette\InvalidArgumentException $exception) {
			// Silent, probably is not URL
		}
		$this->collection = new BetterLocationCollection();
		$this->data = new \stdClass();

		if (method_exists($this, 'beforeStart')) {
			$this->beforeStart();
		}
	}

	abstract public function isValid(): bool;

	abstract public function process();

	abstract static public function getLink(float $lat, float $lon, bool $drive = false);

	final public function getCollection(): BetterLocationCollection
	{
		return $this->collection;
	}

	final public function getFirst(): BetterLocation
	{
		return $this->collection->getFirst();
	}

	public static function getConstants()
	{
		return [];
	}

	public static function getName(bool $short = false)
	{
		if ($short && defined(sprintf('%s::%s', static::class, 'NAME_SHORT'))) {
			return static::NAME_SHORT;
		} else {
			return static::NAME;
		}
	}

	public function getData() {
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
