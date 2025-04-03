<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;


final class UrlTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

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

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://1url.cz/tzmQs'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://1url.cz/tzmQs'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://1url.cz/@better-location-test'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://1url.cz/@better-location-test'));

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://ow.ly/cjiY50FjakQ'));

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://buff.ly/2IzTY3W'));

		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://cutt.ly/Rmrysek'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://cutt.ly/Rmrysek'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('https://cutt.ly/better-location-test'));
		$this->assertTrue(\App\BetterLocation\Url::isShortUrl('http://cutt.ly/better-location-test'));
	}

	public function testIsShortUrlFalse(): void
	{
		$this->assertFalse(\App\BetterLocation\Url::isShortUrl('Some invalid text'));
		$this->assertFalse(\App\BetterLocation\Url::isShortUrl('https://en.wikipedia.org/wiki/Prague'));
		$this->assertFalse(\App\BetterLocation\Url::isShortUrl('https://mapy.cz'));
	}

	public static function redirectUrlProvider(): array
	{
		return [
			['https://en.wikipedia.org/wiki/Prague', 'https://bit.ly/3hFN12b'],
			['https://en.wikipedia.org/wiki/Prague', 'http://bit.ly/3hFN12b'],
			['https://en.wikipedia.org/wiki/Prague', 'https://bit.ly/BetterLocationTest'], // custom URL
			['https://en.wikipedia.org/wiki/Prague', 'http://bit.ly/BetterLocationTest'], // custom URL

			['https://en.wikipedia.org/wiki/Prague', 'https://tinyurl.com/q4e74we'],
			['https://en.wikipedia.org/wiki/Prague', 'http://tinyurl.com/q4e74we'],
			['https://en.wikipedia.org/wiki/Prague', 'https://tinyurl.com/BetterLocationTest'], // custom URL
			['https://en.wikipedia.org/wiki/Prague', 'http://tinyurl.com/BetterLocationTest'], // custom URL

			['https://en.wikipedia.org/wiki/Prague', 'https://rb.gy/yjoqrj'],
			['https://en.wikipedia.org/wiki/Prague', 'http://rb.gy/yjoqrj'],

			['https://en.wikipedia.org/wiki/Prague', 'http://ow.ly/cjiY50FjakQ'],

			['https://en.wikipedia.org/wiki/Prague', 'https://buff.ly/2IzTY3W'],
			['https://en.wikipedia.org/wiki/Prague', 'http://buff.ly/2IzTY3W'],

			['https://mapy.cz/zakladni?source=base&id=1832651&x=14.4210031&y=50.0876166&z=18', 'https://mapy.cz/s/banafokecu'],
		];
	}

	/**
	 * Correct URL is not immediately after first redirect, but some request(s) later.
	 */
	public static function redirectUrlMultipleProvider(): array
	{
		return [
			// Twitter URLs are not returning 'location' header if browser useragent is provided
			['https://en.wikipedia.org/wiki/Prague', 'http://t.co/F9s19A9pU2'],
			['https://en.wikipedia.org/wiki/Prague', 'https://t.co/F9s19A9pU2'],

			// tiny.cc
			['https://en.wikipedia.org/wiki/Prague', 'http://tiny.cc/m5cf001'],
			['https://en.wikipedia.org/wiki/Prague', 'https://tiny.cc/m5cf001'],

			['https://en.wikipedia.org/wiki/Prague', 'http://tiny.cc/BetterLocationTest'], // custom URL
			['https://en.wikipedia.org/wiki/Prague', 'https://tiny.cc/BetterLocationTest'], // custom URL

			// 1url.cz
			['https://en.wikipedia.org/wiki/Prague', 'http://1url.cz/tzmQs'],
			['https://en.wikipedia.org/wiki/Prague', 'https://1url.cz/tzmQs'],

			['https://en.wikipedia.org/wiki/Prague', 'http://1url.cz/@better-location-test'], // custom URL
			['https://en.wikipedia.org/wiki/Prague', 'https://1url.cz/@better-location-test'], // custom URL

			// cutt.ly
			['https://en.wikipedia.org/wiki/Prague', 'http://cutt.ly/Rmrysek'],
			['https://en.wikipedia.org/wiki/Prague', 'https://cutt.ly/Rmrysek'],

			['https://en.wikipedia.org/wiki/Prague', 'http://cutt.ly/better-location-test'], // custom URL
			['https://en.wikipedia.org/wiki/Prague', 'https://cutt.ly/better-location-test'], // custom URL
		];
	}

	/**
	 * @group request
	 *
	 * @dataProvider redirectUrlProvider
	 * @dataProvider redirectUrlMultipleProvider
	 */
	public function testGetRedirectUrlRequestor(string $expectedUrl, string $inputUrl): void
	{
		$this->assertSame(
			$expectedUrl,
			$this->httpTestClients->realRequestor->loadFinalRedirectUrl($inputUrl),
		);
	}
}
