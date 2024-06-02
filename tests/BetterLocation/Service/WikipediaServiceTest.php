<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\WikipediaService;

final class WikipediaServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return WikipediaService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function isValidProvider(): array
	{
		return [
			[true, 'https://en.wikipedia.org/wiki/Conneaut_High_School'],
			[true, 'https://cs.wikipedia.org/wiki/City_Tower'],
			// mobile URLs
			[true, 'https://en.m.wikipedia.org/wiki/Conneaut_High_School'],
			[true, 'https://cs.m.wikipedia.org/wiki/City_Tower'],
			// permanent URLs
			[true, 'https://cs.wikipedia.org/w/index.php?title=Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky&oldid=18532372'],
			[true, 'https://cs.wikipedia.org/w/index.php?oldid=18532372'],
			[true, 'https://cs.wikipedia.org/w/?oldid=18532372'],

			[false, 'https://wikipedia.org/'],
			[false, 'https://en.wikipedia.org/'],
			[false, 'https://cs.wikipedia.org/'],

			[false, 'some invalid url'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.050278, 14.436111, 'https://cs.wikipedia.org/wiki/City_Tower'],
			[50.087500, 14.421389, 'https://cs.wikipedia.org/wiki/Praha'],
			[49.205193, 16.602196, 'https://cs.wikipedia.org/wiki/Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky'],

			// @TODO skipped, for some reason it is requesting with invalid character: "https://cs.wikipedia.org/wiki/Nejvyšš�__soud_České_republiky". But it works fine via Telegram or web Tester...
			// same as above just urldecoded
			[49.205193,16.602196, 'https://cs.wikipedia.org/wiki/Nejvyšší_soud_České_republiky'],

			[41.947222, -80.560833, 'https://en.wikipedia.org/wiki/Conneaut_High_School'],
			[50.431697, -120.185119, 'https://en.wikipedia.org/wiki/Birken_Forest_Buddhist_Monastery'],
			[50.772800, -1.817600, 'https://en.wikipedia.org/wiki/Christchurch_F.C.'],
			[9.600000, 0.883333, 'https://en.wikipedia.org/wiki/Samba,_Togo'],
			[-23.550000, -46.633333, 'https://en.wikipedia.org/wiki/S%C3%A3o_Paulo'],
			[50.050278, 14.436111, 'https://cs.m.wikipedia.org/wiki/City_Tower'],
			[41.947222, -80.560833, 'https://en.m.wikipedia.org/wiki/Conneaut_High_School'],

			// Same page in different languages results in different coordinates
			[50.056400,14.434900, 'https://cs.wikipedia.org/wiki/Pankr%C3%A1c_(Praha)'],
			[50.056394,14.434878, 'https://en.wikipedia.org/wiki/Pankr%C3%A1c'],
		];
	}

	public static function permanentUrlProvider(): array
	{
		return [
			[49.205194,16.602194, 'https://cs.wikipedia.org/w/index.php?title=Nejvy%C5%A1%C5%A1%C3%AD_soud_%C4%8Cesk%C3%A9_republiky&oldid=18532372'],
			[49.205194,16.602194, 'https://cs.wikipedia.org/w/index.php?oldid=18532372'],
			[49.205194,16.602194, 'https://cs.wikipedia.org/w/?oldid=18532372'],
		];
	}

	public static function noLocationProvider(): array
	{
		return [
			['https://be.wikipedia.org/wiki/%D0%9F%D0%B0%D0%BD%D0%BA%D1%80%D0%B0%D1%86'],
			['https://ka.wikipedia.org/wiki/%E1%83%9E%E1%83%90%E1%83%9C%E1%83%99%E1%83%A0%E1%83%90%E1%83%AA%E1%83%98'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new WikipediaService();
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 * @dataProvider permanentUrlProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new WikipediaService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider noLocationProvider
	 */
	public function testNoLocation(string $input): void
	{
		$service = new WikipediaService();
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();
		$this->assertCount(0, $service->getCollection());
	}
}
