<?php declare(strict_types=1);

namespace App;

use App\Factory\LatteFactory;
use App\Web\MainPresenter;
use Psr\Container\ContainerInterface;

final readonly class Kernel
{
	public function __construct(
		private ContainerInterface $container,
	) {
	}

	/**
	 * @param class-string<MainPresenter> $presenter
	 */
	public function runPresenter(string $presenter): void
	{
		if ($this->container->has($presenter) === false) {
			throw new \RuntimeException(sprintf('Unknown presenter "%s"', $presenter));
		}

		$presenter = $this->container->get($presenter);
		assert($presenter instanceof MainPresenter);
		$presenter->run($this->container->get(LatteFactory::class));
	}
}
