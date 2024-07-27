<?php declare(strict_types=1);

namespace App\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Interface representing CacheStorage, that is not deleted during deployment.
 */
interface StorageInterface extends CacheInterface
{
}
