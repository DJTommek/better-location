<?php declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class Container implements ContainerInterface
{
	public readonly ContainerBuilder $containerBuilder;

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

	public function get(string $id)
	{
		return $this->containerBuilder->get($id);
	}

	public function has(string $id): bool
	{
		return $this->containerBuilder->has($id);
	}
}
