<?php declare(strict_types=1);

namespace App\Factory;

use Nette\Utils\FileSystem;

class NetteCacheFileStorageFactory
{
	public function __construct(
		private readonly string $dir,
	) {
	}

	public function create(): \Nette\Caching\Storages\FileStorage
	{
		FileSystem::createDir($this->dir);
		return new \Nette\Caching\Storages\FileStorage($this->dir);
	}
}
