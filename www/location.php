<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$presenter = new \App\Web\Location\LocationPresenter();
$presenter->prepare();

$format = filter_input(INPUT_GET, 'format') ?? 'HTML';
switch ($format) {
	case 'JSON';
		$presenter->json();
		break;
	case 'HTML';
		$presenter->render();
		break;
	default;
		$presenter->template->setError('Invalid output format, showing HTML');
		$presenter->render();
		break;
}
