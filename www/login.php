<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$presenter = new \App\Web\Login\LoginPresenter();
$presenter->render();
