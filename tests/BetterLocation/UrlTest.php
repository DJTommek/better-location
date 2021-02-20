<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;


final class UrlTest extends TestCase
{
	public function testIsShortUrlTrue(): void
	{
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://bit.ly/3hFN12b'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://bit.ly/3hFN12b'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://bit.ly/BetterLocationTest'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://bit.ly/BetterLocationTest'));

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://tinyurl.com/q4e74we'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://tinyurl.com/q4e74we'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://tinyurl.com/BetterLocationTest')); // custom URL
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://tinyurl.com/BetterLocationTest')); // custom URL

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://t.co/F9s19A9pU2?amp=1'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://t.co/F9s19A9pU2'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://t.co/F9s19A9pU2'));

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://rb.gy/yjoqrj'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://rb.gy/yjoqrj'));

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://tiny.cc/ji2ysz'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://tiny.cc/ji2ysz'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://tiny.cc/BetterLocationTest')); // custom URL
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://tiny.cc/BetterLocationTest')); // custom URL

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://jdem.cz/fguwx2'));
		// @TODO add support for URL with custom subdomain
//		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://better-location-test.jdem.cz/')); // custom permanent link
	}

	public function testIsShortUrlFalse(): void
	{
		$this->assertFalse(\App\BetterLocation\Url::isShortUrl('Some invalid text'));
		$this->assertFalse(\App\BetterLocation\Url::isShortUrl('https://en.wikipedia.org/wiki/Prague'));
		$this->assertFalse(\App\BetterLocation\Url::isShortUrl('https://mapy.cz'));
	}

	/** @throws Exception */
	public function testGetRedirectUrl(): void
	{
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://bit.ly/3hFN12b'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://bit.ly/3hFN12b'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://bit.ly/BetterLocationTest')); // custom URL
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://bit.ly/BetterLocationTest')); // custom URL

		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://tinyurl.com/q4e74we'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://tinyurl.com/q4e74we'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://tinyurl.com/BetterLocationTest')); // custom URL
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://tinyurl.com/BetterLocationTest')); // custom URL

		// Twitter URLs are not returning 'location' header if provided browser useragent
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://t.co/F9s19A9pU2?amp=1'));
		$this->assertSame('https://t.co/F9s19A9pU2?amp=1', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://t.co/F9s19A9pU2?amp=1'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://t.co/F9s19A9pU2'));
		$this->assertSame('https://t.co/F9s19A9pU2', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://t.co/F9s19A9pU2'));

		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://rb.gy/yjoqrj'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://rb.gy/yjoqrj'));

		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://tiny.cc/ji2ysz'));
		$this->assertSame('https://tiny.cc/ji2ysz', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://tiny.cc/ji2ysz'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('https://tiny.cc/BetterLocationTest')); // custom URL
		$this->assertSame('https://tiny.cc/BetterLocationTest', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://tiny.cc/BetterLocationTest')); // custom URL

		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://jdem.cz/fguwx2'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://better-location-test.jdem.cz/'));
		$this->assertSame('https://en.wikipedia.org/wiki/Prague', \App\MiniCurl\MiniCurl::loadRedirectUrl('http://better-location-test.jdem.cz'));
	}

}
