<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

use App\BetterLocation\StaticMapProxy;
use Tracy\Debugger;

if (!isset($_GET['id'])) {
	printf('Error: Provided ID is not valid.');
	exit;
}

$id = $_GET['id'];

$mapProxyFactory = \App\Factory::staticMapProxyFactory();
$mapProxy = $mapProxyFactory->fromCacheId($id);
if ($mapProxy === null) {
	printf('Error: Static map image doesn\'t exists for this ID.');
	exit;
}

$mapProxy->download();
$file = $mapProxy->cachePath();
Debugger::$showBar = false;
header('Content-Description: File Transfer');
header('Content-Type: image/jpeg');
header('Cache-Control: public, immutable');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
