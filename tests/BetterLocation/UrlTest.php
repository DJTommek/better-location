<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/bootstrap.php';


final class UrlTest extends TestCase
{
	/** @throws Exception */
	public function testGetRedirectUrl(): void {
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://bit.ly/3hFN12b'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://tinyurl.com/q4e74we'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://t.co/F9s19A9pU2?amp=1'));
		$this->assertEquals('https://en.wikipedia.org/wiki/Prague', \BetterLocation\Url::getRedirectUrl('https://t.co/F9s19A9pU2'));
		$this->assertEquals('https://t.co/F9s19A9pU2', \BetterLocation\Url::getRedirectUrl('http://t.co/F9s19A9pU2'));
	}

}
