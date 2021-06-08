<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

if (\App\Utils\Coordinates::isLat($_GET['lat'] ?? null) && \App\Utils\Coordinates::isLon($_GET['lon'] ?? null)) {
	$lat = \App\Utils\Strict::floatval($_GET['lat']);
	$lon = \App\Utils\Strict::floatval($_GET['lon']);
	$presenter = new \App\Web\Location\LocationPresenter($lat, $lon);
	$presenter->render();
} else {
	\App\Factory::Latte('locationError.latte');
}
