<?php declare(strict_types=1);

namespace App\Web\Staticmap;

use App\BetterLocation\StaticMapProxyFactory;
use App\Web\MainPresenter;
use Tracy\Debugger;

class StaticmapPresenter extends MainPresenter
{
	public function __construct(
		private readonly StaticMapProxyFactory $staticMapProxyFactory,
	)
	{
	}

	public function action(): never
	{
		$id = $this->request->getQuery('id');
		if ($id === null) {
			$this->apiResponse(true, 'Static map ID is missing.', httpCode: self::HTTP_NOT_FOUND);
		}

		$mapProxy = $this->staticMapProxyFactory->fromCacheId($id);
		if ($mapProxy === null) {
			$this->apiResponse(true, 'Static map ID does not exists.', httpCode: self::HTTP_NOT_FOUND);
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
	}
}
