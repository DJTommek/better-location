<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

(new \App\Kernel($container))->runPresenter(\App\Web\Logout\LogoutPresenter::class);
