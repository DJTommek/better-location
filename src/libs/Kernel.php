<?php declare(strict_types=1);

namespace App;

use App\Factory\LatteFactory;
use App\Repository\ChatRepository;
use App\Repository\FavouritesRepository;
use App\Repository\UserRepository;
use App\Web\Login\LoginFacade;
use App\Web\MainPresenter;
use Nette\Http\Request;
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
		$presenter->run(
			$this->container->get(UserRepository::class),
			$this->container->get(ChatRepository::class),
			$this->container->get(FavouritesRepository::class),
			$this->container->get(LatteFactory::class),
			$this->container->get(LoginFacade::class),
			$this->container->get(Request::class),
		);
	}
}
