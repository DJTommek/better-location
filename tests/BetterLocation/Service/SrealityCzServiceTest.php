<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\SrealityCzService;
use Tests\HttpTestClients;

final class SrealityCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	#[\Override]
	protected function getServiceClass(): string
	{
		return SrealityCzService::class;
	}

	#[\Override]
	protected function getShareLinks(): array
	{
		return [];
	}

	#[\Override]
	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'https://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108'],
			[true, 'http://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108'],
			[true, 'https://sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108'],
			[true, 'https://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108/'],
			[true, 'https://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108?foo=bar'],
			[true, 'https://www.sreality.cz/detail/pronajem/komercni/vyrobni-prostor/brno-brno-/2243441228'],
			[true, 'https://www.sreality.cz/detail/prodej/byt/3+kk/capdepera-capdepera-/298778700'],
			[true, 'https://www.sreality.cz/en/detail/prodej/byt/3+kk/capdepera-capdepera-/298778700'],
			[true, 'https://www.sreality.cz/projekt-detail/rd-chvalkovice/39860'],
			[true, 'https://www.sreality.cz/projekt-detail/rd-chvalkovice/39860/'],
			[true, 'https://www.sreality.cz/en/project-detail/rd-chvalkovice/39860'],

			[false, 'some invalid url'],
			[false, 'https://www.sreality.cz/'],
			[false, 'https://www.sreality.cz/detail'],
			[false, 'https://www.sreality.cz/detail/2194604108'],
			[false, 'https://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova'],
			[false, 'https://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/123abc'],
			[false, 'https://www.sreality.cz/hledani/pronajem/byty/praha'],
			[false, 'https://www.sreality.cz/projekt-detail/39860'],
			[false, 'https://www.sreality.cz/projekt-detail/rd-chvalkovice'],
			[false, 'https://sreality.example.com/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108'],
		];
	}

	public static function processProvider(): array
	{
		return [
			'estate exact' => [50.067172, 14.3696813, "Pronájem bytu 2+kk 56\u{00A0}m²", [], 'https://www.sreality.cz/detail/pronajem/byt/2+kk/praha-kosire-lovcenska/1423900748'],
			'estate exact abroad' => [39.701242, 3.438695, "Prodej bytu 3+kk 90\u{00A0}m²", [], 'https://www.sreality.cz/detail/prodej/byt/3+kk/capdepera-capdepera-/298778700'],
			'project exact' => [49.601105, 17.289061, 'RD Chválkovice', [], 'https://www.sreality.cz/projekt-detail/rd-chvalkovice/39860'],
			'estate approximate street (address)' => [
				50.0741947758128, 14.3590747424843, "Pronájem bytu 1+kk 48\u{00A0}m²",
				['⚠ Location is only approximate (street level).'],
				'https://www.sreality.cz/detail/pronajem/byt/1+kk/praha-motol-jerabinova/2194604108',
			],
			'estate approximate street' => [
				50.06309217853674, 14.361793540683493, "Pronájem bytu 2+kk 53\u{00A0}m²",
				['⚠ Location is only approximate (street level).'],
				'https://www.sreality.cz/detail/pronajem/byt/2+kk/praha-kosire-naskove/477704268',
			],
			'estate approximate ward' => [
				50.6604870323913, 14.0406919953821, "Prodej činžovního domu 696\u{00A0}m²",
				['⚠ Location is only approximate (city district level).'],
				'https://www.sreality.cz/detail/prodej/komercni/cinzovni-dum/usti-nad-labem-usti-nad-labem-centrum-/828162124',
			],
			'estate approximate municipality' => [
				49.200221, 16.607841, "Pronájem výrobní haly, prostoru 1100\u{00A0}m²",
				['⚠ Location is only approximate (municipality level).'],
				'https://www.sreality.cz/detail/pronajem/komercni/vyrobni-prostor/brno-brno-/2243441228',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new SrealityCzService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 * @param list<string> $expectedDescriptions
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedName, array $expectedDescriptions, string $input): void
	{
		$service = new SrealityCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $input, $service);
		$this->assertDescriptions($expectedDescriptions, $service);
	}

	/**
	 * @dataProvider processProvider
	 * @param list<string> $expectedDescriptions
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedName, array $expectedDescriptions, string $input): void
	{
		$service = new SrealityCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $input, $service);
		$this->assertDescriptions($expectedDescriptions, $service);
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinatesReal(): void
	{
		$service = new SrealityCzService($this->httpTestClients->realRequestor);
		$service->setInput('https://www.sreality.cz/detail/prodej/byt/3+kk/praha/1');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on Sreality.cz page are missing.');

		$service->process();
	}

	public function testMissingCoordinatesOffline(): void
	{
		$service = new SrealityCzService($this->httpTestClients->offlineRequestor);
		$service->setInput('https://www.sreality.cz/detail/prodej/byt/3+kk/praha/1');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Coordinates on Sreality.cz page are missing.');

		$service->process();
	}

	private function assertPrefix(string $expectedName, string $input, SrealityCzService $service): void
	{
		$this->assertSame(
			sprintf('<a href="%s">Sreality.cz %s</a>', $input, $expectedName),
			$service->getCollection()->getFirst()->getPrefixMessage(),
		);
	}

	/**
	 * @param list<string> $expectedDescriptions
	 */
	private function assertDescriptions(array $expectedDescriptions, SrealityCzService $service): void
	{
		$descriptions = array_map(
			fn($description) => (string)$description,
			$service->getCollection()->getFirst()->getDescriptions(),
		);
		$this->assertSame($expectedDescriptions, $descriptions);
	}
}
