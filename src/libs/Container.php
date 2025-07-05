<?php declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

readonly class Container implements ContainerInterface
{
	public ContainerBuilder $containerBuilder;

	public function __construct()
	{
		$this->containerBuilder = new ContainerBuilder();
	}

	public function register(): void
	{
		$loader = new PhpFileLoader($this->containerBuilder, new FileLocator(__DIR__));
		$loader->load(__DIR__ . '/../services.php');
		$this->containerBuilder->compile();
	}

	/**
	 * @template T of object
	 * @param class-string<T> $id
	 * @return T|null
	 */
	public function get(string $id)
	{
		return $this->containerBuilder->get($id);
	}

	/**
	 * @param class-string $id
	 */
	public function has(string $id): bool
	{
		return $this->containerBuilder->has($id);
	}
}
