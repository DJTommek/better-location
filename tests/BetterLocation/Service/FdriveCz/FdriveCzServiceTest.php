<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\FdriveCz;

use App\BetterLocation\Description;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\FdriveCz\FdriveCzService;
use App\Icons;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\HttpTestClients;

final class FdriveCzServiceTest extends AbstractServiceTestCase
{
	/**
	 * Trimmed version of real response, containing only few stations
	 */
	private const string FIXTURE_DATA = __DIR__ . '/fixtures/charging-map-data.json';

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
		return FdriveCzService::class;
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
			[true, 'https://fdrive.cz/mapa-nabijecich-stanic/chargee-praha-10---vrsovice-ul-u-seradiste-autodraft-servis-34800'],
			[true, 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953'],
			[true, 'http://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953'],
			[true, 'https://www.fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953'],
			[true, 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953/'],
			[true, 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953?foo=bar'],
			[true, 'https://fdrive.cz/mapa-nabijecich-stanic/foo-14953'],
			[true, 'https://fdrive.cz/mapa-nabijecich-stanic/14953'],

			[false, 'some invalid url'],
			[false, 'https://fdrive.cz/'],
			[false, 'https://fdrive.cz/mapa-nabijecich-stanic'],
			[false, 'https://fdrive.cz/mapa-nabijecich-stanic/'],
			[false, 'https://fdrive.cz/mapa-nabijecich-stanic/api/charging-map-data'],
			[false, 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone'],
			[false, 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-14953/foo'],
			[false, 'https://fdrive.cz/clanky/ionity-14953'],
			[false, 'https://fdrive.example.com/mapa-nabijecich-stanic/ionity-14953'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[50.064435, 14.458689, 'Chargee, Praha 10 - Vršovice, ul. U Seřadiště, AutoDraft servis', 'https://fdrive.cz/mapa-nabijecich-stanic/chargee-praha-10---vrsovice-ul-u-seradiste-autodraft-servis-34800', 'https://fdrive.cz/mapa-nabijecich-stanic/chargee-praha-10---vrsovice-ul-u-seradiste-autodraft-servis-34800'],
			[45.877486, 12.315154, 'IONITY (I), Conegliano, Via S. Giuseppe, Centro Commerciale Cone', 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953', 'https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953'],
			[50.080963, 14.433104, 'PRE, Praha 2 - Vinohrady, ul. Wilsonova, Parking Centrum &quot;Garáže Slovan&quot;', 'https://fdrive.cz/mapa-nabijecich-stanic/pre-praha-2---vinohrady-ul-wilsonova-parking-centrum-garaze-slovan-5', 'https://fdrive.cz/mapa-nabijecich-stanic/pre-praha-2---vinohrady-ul-wilsonova-parking-centrum-garaze-slovan-5'],
			// Original input is kept, if slug is correct
			[45.877486, 12.315154, 'IONITY (I), Conegliano, Via S. Giuseppe, Centro Commerciale Cone', 'http://www.fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953', 'http://www.fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953'],
			// URL without slug or with invalid slug is not working on website, so it is replaced with full URL
			[50.072903, 14.402055, 'ČEZ, Praha 5 - Smíchov, Kartouzská ul., OC Nový Smíchov', 'https://fdrive.cz/mapa-nabijecich-stanic/cez-praha-5---smichov-kartouzska-ul-oc-novy-smichov-4', 'https://fdrive.cz/mapa-nabijecich-stanic/4'],
			[50.072903, 14.402055, 'ČEZ, Praha 5 - Smíchov, Kartouzská ul., OC Nový Smíchov', 'https://fdrive.cz/mapa-nabijecich-stanic/cez-praha-5---smichov-kartouzska-ul-oc-novy-smichov-4', 'https://fdrive.cz/mapa-nabijecich-stanic/4/'],
			[50.072903, 14.402055, 'ČEZ, Praha 5 - Smíchov, Kartouzská ul., OC Nový Smíchov', 'https://fdrive.cz/mapa-nabijecich-stanic/cez-praha-5---smichov-kartouzska-ul-oc-novy-smichov-4', 'https://fdrive.cz/mapa-nabijecich-stanic/some-invalid-slug-4'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedName, string $expectedLink, string $input): void
	{
		$service = new FdriveCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $expectedLink, $service);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessMocked(float $expectedLat, float $expectedLon, string $expectedName, string $expectedLink, string $input): void
	{
		$this->mockDataResponse();
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertPrefix($expectedName, $expectedLink, $service);
	}

	public function testDescriptionsOpenStationMocked(): void
	{
		$descriptions = $this->processDescriptions('https://fdrive.cz/mapa-nabijecich-stanic/ionity-i-conegliano-via-s-giuseppe-centro-commerciale-cone-14953');

		$this->assertSame([
			'Chargers: 6× CCS 350 kW',
		], $descriptions);
	}

	public function testDescriptionsMultipleChargersMocked(): void
	{
		$descriptions = $this->processDescriptions('https://fdrive.cz/mapa-nabijecich-stanic/4');

		$this->assertSame([
			'Chargers: 4× Typ 2 22 kW, 2× CHAdeMO 60 kW, 2× CCS 200 kW, 7× CCS 80 kW',
		], $descriptions);
	}

	public function testDescriptionsTemporarilyClosedMocked(): void
	{
		$descriptions = $this->processDescriptions('https://fdrive.cz/mapa-nabijecich-stanic/5');

		$this->assertSame([
			Icons::WARNING . ' Status: <b>Temporarily closed</b>',
			'Chargers: 1× Typ 2 22 kW, 1× 230V zásuvka 3.6 kW',
		], $descriptions);
	}

	public function testDescriptionsUnderConstructionMocked(): void
	{
		$descriptions = $this->processDescriptions('https://fdrive.cz/mapa-nabijecich-stanic/e-station-cerna-hora-cerpaci-stanice-unicorn-10525');

		$this->assertSame(Icons::WARNING . ' Status: <b>Under construction</b>', $descriptions[0]);
	}

	public function testAddressMocked(): void
	{
		$this->mockDataResponse();
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$service->setInput('https://fdrive.cz/mapa-nabijecich-stanic/chargee-praha-10---vrsovice-ul-u-seradiste-autodraft-servis-34800');
		$this->assertTrue($service->validate());
		$service->process();

		$this->assertSame('U seřadiště 65, 101 00 Praha; CZE', (string)$service->getCollection()->getFirst()->getAddress());
	}

	public static function statusDescriptionProvider(): array
	{
		return [
			[null, 'open'],
			[Icons::WARNING . ' Status: <b>Temporarily closed</b>', 'tempClosed'],
			[Icons::WARNING . ' Status: <b>Under construction</b>', 'inDev'],
			[Icons::WARNING . ' Status: <b>someNewStatus</b>', 'someNewStatus'],
		];
	}

	/**
	 * @dataProvider statusDescriptionProvider
	 */
	public function testStatusDescription(?string $expected, string $status): void
	{
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$this->assertSame($expected, $service->statusDescription($status));
	}

	public function testChargersDescriptionUnknownTypeAndNoOutput(): void
	{
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$chargers = [
			(object)['id' => '999', 'count' => '2', 'max_output' => '0.00'],
		];
		$this->assertSame('Chargers: 2× #999', $service->chargersDescription($chargers, new \stdClass()));
		$this->assertNull($service->chargersDescription([], new \stdClass()));
	}

	public function testNonExistingStationMocked(): void
	{
		$this->mockDataResponse();
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$this->assertServiceNoLocation($service, 'https://fdrive.cz/mapa-nabijecich-stanic/non-existing-station-99999999');
	}

	public function testInvalidResponseMocked(): void
	{
		$this->httpTestClients->mockHandler->append(new \GuzzleHttp\Psr7\Response(500));
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$service->setInput('https://fdrive.cz/mapa-nabijecich-stanic/14953');
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Unable to load list of charging stations from fdrive.cz.');

		$service->process();
	}

	private function mockDataResponse(): void
	{
		$this->httpTestClients->mockHandler->append(new \GuzzleHttp\Psr7\Response(200, body: file_get_contents(self::FIXTURE_DATA)));
	}

	private function assertPrefix(string $expectedName, string $expectedLink, FdriveCzService $service): void
	{
		$this->assertSame(
			sprintf('<a href="%s">fdrive.cz %s</a>', $expectedLink, $expectedName),
			$service->getCollection()->getFirst()->getPrefixMessage(),
		);
	}

	/**
	 * @return list<string>
	 */
	private function processDescriptions(string $input): array
	{
		$this->mockDataResponse();
		$service = new FdriveCzService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$this->assertTrue($service->validate());
		$service->process();
		return array_map(
			fn(Description $description): string => $description->content,
			$service->getCollection()->getFirst()->getDescriptions(),
		);
	}
}
