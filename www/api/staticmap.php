<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

use App\BetterLocation\StaticMapProxy;
use Tracy\Debugger;

if (isset($_GET['id'])) {
	$id = $_GET['id'];
	$mapProxy = \App\Factory::StaticMapProxy();
	if ($mapProxy->loadById($id)) {
		$file = $mapProxy->generateCachePath();
		Debugger::$showBar = false;
		header('Content-Description: File Transfer');
		header('Content-Type: image/jpeg');
		header('Cache-Control: public, immutable');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		readfile($file);
		exit;
	} else {
		printf('Error: Static map image doesn\'t exists for this ID.');
	}
} else {
	printf('Error: Provided ID is not valid.');
}
