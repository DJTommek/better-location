<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/bootstrap.php';


final class UrlTest extends TestCase
{
	public function testIsShortUrlTrue(): void {
		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://bit.ly/3hFN12b'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://bit.ly/3hFN12b'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://bit.ly/BetterLocationTest'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://bit.ly/BetterLocationTest'));

		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://tinyurl.com/q4e74we'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://tinyurl.com/q4e74we'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://tinyurl.com/BetterLocationTest')); // custom URL
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://tinyurl.com/BetterLocationTest')); // custom URL

		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://t.co/F9s19A9pU2?amp=1'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://t.co/F9s19A9pU2'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://t.co/F9s19A9pU2'));

		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://rb.gy/yjoqrj'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://rb.gy/yjoqrj'));

		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://tiny.cc/ji2ysz'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://tiny.cc/ji2ysz'));
		$this->assertTrue(\BetterLocation\Url::isShortUrl('https://tiny.cc/BetterLocationTest')); // custom URL
		$this->assertTrue(\BetterLocation\Url::isShortUrl('http://tiny.cc/BetterLocationTest')); // custom URL
	}

	public function testIsShortUrlFalse(): void {
		$this->assertFalse(\BetterLocation\Url::isShortUrl('Some invalid text'));
		$this->assertFalse(\BetterLocation\Url::isShortUrl('https://en.wikipedia.org/wiki/Prague'));
		$this->assertFalse(\BetterLocation\Url::isShortUrl('https://mapy.cz'));
	}

	/** @throws Exception */
	public function testGetRedirectUrl(): void {
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://bit.ly/3hFN12b'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('http://bit.ly/3hFN12b'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://bit.ly/BetterLocationTest')); // custom URL
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('http://bit.ly/BetterLocationTest')); // custom URL

		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://tinyurl.com/q4e74we'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('http://tinyurl.com/q4e74we'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://tinyurl.com/BetterLocationTest')); // custom URL
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('http://tinyurl.com/BetterLocationTest')); // custom URL

		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://t.co/F9s19A9pU2?amp=1'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://t.co/F9s19A9pU2'));
		$this->assertEquals('https://t.co/F9s19A9pU2', \BetterLocation\Url::getRedirectUrl('http://t.co/F9s19A9pU2'));

		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://rb.gy/yjoqrj'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('http://rb.gy/yjoqrj'));

		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://tiny.cc/ji2ysz'));
		$this->assertEquals('https://tiny.cc/ji2ysz', \BetterLocation\Url::getRedirectUrl('http://tiny.cc/ji2ysz'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://tiny.cc/BetterLocationTest')); // custom URL
		$this->assertEquals('https://tiny.cc/BetterLocationTest', \BetterLocation\Url::getRedirectUrl('http://tiny.cc/BetterLocationTest')); // custom URL
	}

}
