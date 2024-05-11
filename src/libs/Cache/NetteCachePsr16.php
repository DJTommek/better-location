<?php declare(strict_types=1);

namespace App\Cache;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Psr\SimpleCache\CacheInterface;

readonly class NetteCachePsr16 implements CacheInterface
{
	public function __construct(
		private Storage $storage,
	) {
	}

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->storage->read($key) ?? $default;
	}

	public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
	{
		$ttl = $this->ttlToSeconds($ttl);

		$dependencies = [
			Cache::Expire => $ttl,
		];
		$this->storage->write($key, $value, $dependencies);
		return true;
	}

	public function delete(string $key): bool
	{
		$this->storage->remove($key);
		return true;
	}

	public function clear(): bool
	{
		$this->storage->clean([Cache::All => true]);
		return true;
	}

	/**
	 * @param iterable<string> $keys
	 */
	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		foreach ($keys as $key) {
			yield $key => $this->get($key, $default);
		}
	}

	/**
	 * @param iterable<string, mixed> $values
	 */
	public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
	{
		foreach ($values as $key => $value) {
			$this->set($key, $value, $ttl);
		}
		return true;
	}

	/**
	 * @param iterable<string> $keys
	 */
	public function deleteMultiple(iterable $keys): bool
	{
		foreach ($keys as $key) {
			$this->storage->remove($key);
		}
		return true;
	}

	public function has(string $key): bool
	{
		return $this->storage->read($key) !== null;
	}

	private function ttlToSeconds(\DateInterval|int|null $ttl): ?int
	{
		if ($ttl === null || is_int($ttl)) {
			return $ttl;
		}

		$start = new \DateTimeImmutable();
		$end = $start->add($ttl);
		return $end->getTimestamp() - $start->getTimestamp();
	}
}
