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
$emojis = [
	'🙂', '☀️', '💪', '😜', '🐶', '🐱', '❄️', '🍎', '🍓', '🍌', '🚦', '🔋',
	// This safer way how to store emojis in code to prevent breaking if this file is saved as non multibyte UTF-8
	"\u{1f1e8}\u{1f1ff}" // https://emojipedia.org/flag-czechia/
];

foreach ($data['locations'] as $key => &$location) {
	$coords = new Coordinates($location['latitude'], $location['longitude']);

	$location['prefix'] = sprintf(
		'%s %s (<b>%s</b> from Prague)',
		$emojis[array_rand($emojis)],
		$location['prefix'],
		htmlspecialchars(Formatter::distance($mapCenterPrague->distance($coords)))
	);
}

die(Json::encode($data));
