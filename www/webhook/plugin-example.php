<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
\Tracy\Debugger::$showBar = false;

use App\Utils\Coordinates;
use App\Utils\Formatter;
use Nette\Utils\Json;

$request = (new \Nette\Http\RequestFactory())->fromGlobals();

if ($request->isMethod('POST') === false) {
	http_response_code(400);
	die('Error: This page requested only using POST with correct JSON structure as request body.');
}

$input = $request->getRawBody();
if (trim($input) === '') {
	http_response_code(400);
	die('Error: Structure data is missing! This page should be requested only using POST with correct JSON structure as request body.');
}

$data = Json::decode($input, Json::FORCE_ARRAY);

// little offset of usually example coordinates so calculated distance is not 0 meters for default example
$mapCenterPrague = new Coordinates(50.0874, 14.4206);
// random emojis that will be prefixed to the message
$emojis = ['🙂', '☀️', '💪', '😜', '🐶', '🐱', '❄️', '🍎', '🍓', '🍌', '🚦', '🔋', '🇨🇿'];

foreach ($data['locations'] as $key => &$location) {
	$coords = new Coordinates($location['coordinates']['lat'], $location['coordinates']['lon']);

	$location['prefix'] = sprintf(
		'%s %s (%s from Prague)',
		$emojis[array_rand($emojis)],
		$location['prefix'],
		htmlspecialchars(Formatter::distance($mapCenterPrague->distance($coords)))
	);
}

die(Json::encode($data));
