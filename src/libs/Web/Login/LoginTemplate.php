<?php declare(strict_types=1);

namespace App\Web\Login;

class LoginTemplate
{
	public $logged = false;

	public $loginWrapper;

	public $errors = [];

	public function setError(string $errorText): void
	{
		$this->errors[] = $errorText;
	}
}

