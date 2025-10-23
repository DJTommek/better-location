<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\IngressIntelService;
use Tests\HttpTestClients;

final class FevGamesServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return FevGamesService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://fevgames.net/ifs/event/?e=15677'],
			[true, 'https://FEVgaMEs.net/ifs/event/?e=15677'],
			[true, 'http://fevgames.net/ifs/event/?e=15677'],
			[true, 'http://www.fevgames.net/ifs/event/?e=15677'],
			[true, 'https://www.fevgames.net/ifs/event/?e=15677'],
			[true, 'https://fevgames.net/ifs/event/?e=12342'],

			[false, 'non url'],
			[false, 'https://blabla.net/ifs/event/?e=15677'],
			[false, 'https://fevgames.net/ifs/event/'],
			[false, 'https://fevgames.net/ifs/event/?e=-15677'],
			[false, 'https://fevgames.net/ifs/event/?e=0'],
			[false, 'https://fevgames.cz/ifs/event/?e=15677'],
			[false, 'https://fevgames.net?e=15677'],
			[false, 'https://fevgames.net/ifs/event/?event=15677'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				-37.815226,
				144.963781,
				'<a href="https://fevgames.net/ifs/event/?e=23448">#IngressFS - Melbourne, Australia - July 2022</a>',
				[
					'Base portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F85cebd71bb544ed3bce9c530a4ad1ff3.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D-37.815226%2C144.963781">The City of Melbourne Building 📱</a> <a href="https://intel.ingress.com/intel?pll=-37.815226,144.963781">🖥</a> <a href="https://lh3.googleusercontent.com/iEurNi7d0gB7i1hmMyDWINus_wfCfQInSmRt5T6RUriWN_8q-sWE_togNvXie7Ff9vbniCwE8R_0qNyvDdiL6RR1OCw=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/-37.815226,144.963781,12.66,,85cebd71bb544ed3bce9c530a4ad1ff3.16">🎦</a>'
				],
				'https://fevgames.net/ifs/event/?e=23448',
			],

			[
				40.696302,
				14.481354,
				'<a href="https://fevgames.net/ifs/event/?e=27094">#IngressFS - Castellammare di Stabia, Italy - July 2024</a>',
				[
					// Base portal does not exists in API, so there is just simple link
					'Base portal: <a href="https://intel.ingress.com/intel?pll=40.696302,14.481354">Cassa Armonica 🖥</a>',
					// Restock portal exists in API, so message is richer
					'Restock portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fe05daf1be8794677a222c81892465e3d.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D40.699493%2C14.482077">Fontana del Vogatore 📱</a> <a href="https://intel.ingress.com/intel?pll=40.699493,14.482077">🖥</a> <a href="https://lh3.googleusercontent.com/6lBi53ikW9htcPclK00wMwvAULOLlJbmMVZPwT9Ttm_dcwNmnjVKsaC7NqGrf5X3D81T0KKMl1MCpLN8V6WuLh0VC-4=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/40.699493,14.482077,12.66,,e05daf1be8794677a222c81892465e3d.16">🎦</a>',
				],
				'https://fevgames.net/ifs/event/?e=27094',
			],

			[
				43.579854,
				39.72527,
				'<a href="https://fevgames.net/ifs/event/?e=27087">#IngressFS - Sochi, Russia - July 2024</a>',
				[
					'Base portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fd19d1f31724d3307a491e3d988bf63ae.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D43.579854%2C39.725270">Фреска музыкальная 📱</a> <a href="https://intel.ingress.com/intel?pll=43.579854,39.725270">🖥</a> <a href="https://lh3.googleusercontent.com/8st8o0klXFLt4U4gf3Z-4lOI8umBNti7H8oDhZQCmzBJ89GG7uSDVelKPfEYttcsz3Gom0xkXMLHAOdrg1PJIhDsKpv4JIAri6b7jEI=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/43.579854,39.72527,12.66,,d19d1f31724d3307a491e3d988bf63ae.16">🎦</a>',
				],
				'https://fevgames.net/ifs/event/?e=27087',
			],

			[
				49.68188,
				18.359514,
				'<a href="https://fevgames.net/ifs/event/?e=28084">#IngressFS - Frýdek-Místek, Czechia - June 2025</a>',
				[
					'Base portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fa75f266581c341b9b140ae8ef1cd2fae.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D49.681880%2C18.359514">Kobra 📱</a> <a href="https://intel.ingress.com/intel?pll=49.681880,18.359514">🖥</a> <a href="https://lh3.googleusercontent.com/dAPXWLMCxK0quXp7Pmi5T3Ca2qwUIN1y8USFKdH65OuhBmyZbs-Jq0wjnv77choSSPG2XfkqhzAuyh4WcksN4I78B599=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/49.68188,18.359514,12.66,,a75f266581c341b9b140ae8ef1cd2fae.16">🎦</a>',
					'Restock portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F92d47d8d1050305eabb7d6b7d20ec4bf.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D49.681438%2C18.359742">Pinpongové stoly 📱</a> <a href="https://intel.ingress.com/intel?pll=49.681438,18.359742">🖥</a> <a href="https://lh3.googleusercontent.com/aGvsse6ffNpehfj2ruOKRThgvsoxscbbTP9AM9bS2LRpDB33zpM_0kiqtfJ9vXMRG3hGjqzrHKBNVGDqiLKstpkGaLNyI5KEHIzN7TSOOg=s10000">🖼</a> <a href="https://lightship.dev/account/geospatial-browser/49.681438,18.359742,12.66,,92d47d8d1050305eabb7d6b7d20ec4bf.16">🎦</a>',
				],
				'https://fevgames.net/ifs/event/?e=28084',
			],
		];
	}

	public static function processNotValidProvider(): array
	{
		return [
			['https://fevgames.net/ifs/event/?e=12342'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedPrefix, array $expectedResults, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->realRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->realRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedResults, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedPrefix, array $expectedResults, string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->offlineRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->offlineRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedResults, $input);
	}

	private function testProcess(
		FevGamesService $service,
		float $expectedLat,
		float $expectedLon,
		string $expectedPrefix,
		array $expectedDescriptions,
		string $input,
	): void {
		$location = $this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$descriptions = $location->getDescriptions();
		$this->assertSame($expectedPrefix, $location->getPrefixMessage());

		$this->assertCount(count($expectedDescriptions), $descriptions);

		foreach ($expectedDescriptions as $key => $expectedDescriptionText) {
			$this->assertSame($expectedDescriptionText, $descriptions[$key]->content);
		}
	}

	/**
	 * @group request
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLinkReal(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processNotValidProvider
	 */
	public function testNoIntelLinkOffline(string $input): void
	{
		$ingressClient = new \App\IngressLanchedRu\Client($this->httpTestClients->mockedRequestor);
		$ingressIntelService = new IngressIntelService($ingressClient);

		$service = new FevGamesService($ingressClient, $ingressIntelService, $this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
