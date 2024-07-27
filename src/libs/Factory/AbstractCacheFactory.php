<?php declare(strict_types=1);

namespace App\Factory;

use App\Cache\NetteCachePsr16;
use Psr\SimpleCache\CacheInterface;

readonly class AbstractCacheFactory
{
	public function __construct(
		private string $dir,
	) {
	}

	public function create(): CacheInterface
	{
		$storage = (new NetteCacheFileStorageFactory($this->dir))->create();
		return new NetteCachePsr16($storage);
	}
}
