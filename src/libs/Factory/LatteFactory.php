<?php declare(strict_types=1);

namespace App\Factory;

class LatteFactory
{
	public function __construct(
		private readonly \Latte\Engine $engine,
	) {
	}

	/**
	 * @param string $templatePath Path to *.latte file
	 * @param array<string, mixed>|object $params
	 */
	public function render(string $templatePath, array|object $params = []): void
	{
		$this->engine->render($templatePath, $params);
	}
}
