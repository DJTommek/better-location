<?php declare(strict_types=1);

namespace App\IngressMosaic;

use App\IngressMosaic\Types\MosaicType;
use App\MiniCurl\MiniCurl;

class Client
{
	const LINK = 'https://ingressmosaic.com';
	const LINK_MOSAIC = self::LINK . '/mosaic/';

	/** @var string */
	private $cookieXsrf;
	/** @var string */
	private $cookieSession;
	/** @var int */
	private $cacheTtl = 0;

	public function __construct(string $cookieXsrf, string $cookieSession)
	{
		$this->cookieXsrf = $cookieXsrf;
		$this->cookieSession = $cookieSession;
	}


	public function setCache(int $ttl): self
	{
		$this->cacheTtl = $ttl;
		return $this;
	}

	public function loadMosaic(int $mosaicId): MosaicType
	{
		$response = (new MiniCurl(self::LINK_MOSAIC . $mosaicId))
			->allowCache($this->cacheTtl)
			->setHttpCookie('XSRF-TOKEN', $this->cookieXsrf)
			->setHttpCookie('ingressmosaik_session', $this->cookieSession)
			->setHttpCookie('lang', 'en')
			->run()
			->getBody();
		return new MosaicType($response);
	}
}
