<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\ZniceneKostelyCzService;
use Tests\HttpTestClients;

final class ZniceneKostelyCzServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return ZniceneKostelyCzService::class;
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
			[true, 'http://www.znicenekostely.cz/?load=detail&id=18231'],
			[true, 'http://www.znicenekostely.cz/?id=18231&load=detail'],
			[true, 'http://znicenekostely.cz/?load=detail&id=18231'],
			[true, 'http://www.znicenekostely.cz/?load=detail&id=18231#obsah'],
			[true, 'http://znicenekostely.cz/?load=detail&id=18231#obsah'],
			[true, 'http://www.znicenekostely.cz/index.php?load=detail&id=18231#obsah'],
			[true, 'http://znicenekostely.cz/index.php?load=detail&id=18231#obsah'],
			[true, 'http://www.znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'],
			[true, 'http://znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'],
			[true, 'https://www.znicenekostely.cz/objekt/detail/8139#object'],
			[true, 'https://znicenekostely.cz/objekt/detail/8139#object'],
			[true, 'http://znicenekostely.cz/objekt/detail/8139'],

			[false, 'http://www.znicenekostely.cz/?load=detail&id=18231aaaa'],
			[false, 'http://www.znicenekostely.cz/?load=detail&id=aaaa'],
			[false, 'http://znicenekostely.cz/index.php?load=detail&id='],
			[false, 'http://znicenekostely.cz/?load=detail&id='],
			[false, 'http://www.znicenekostely.cz/?load=detail&id='],
			[false, 'http://znicenekostely.cz/?load=blabla&id=18231'],
			[false, 'http://znicenekostely.cz/?load=detail'],
			[false, 'http://znicenekostely.cz/?load=blabla&ID=18231'],
			[false, 'http://znicenekostely.cz/'],
			[false, 'http://znicenekostely.cz/index.php'],
			[false, 'https://www.znicenekostely.cz/objekt/detail/aa8139'],
			[false, 'https://www.znicenekostely.cz/something/detail/8139'],

			[false, 'some invalid url'],
		];
	}

	public function processProvider(): array
	{
		return [
			[49.885617, 14.044381, 'http://www.znicenekostely.cz/?load=detail&id=18231#obsah'],
			[49.885617, 14.044381, 'https://www.znicenekostely.cz/objekt/detail/18231#object'],
			[48.944638, 15.697070, 'http://www.znicenekostely.cz/index.php?load=detail&id=13727'],
			[50.636144, 14.337469, 'http://www.znicenekostely.cz/index.php?load=detail&id=4233&search_result_index=0&nej=1#obsah'],
			[50.042461, 14.375072, 'http://www.znicenekostely.cz/index.php?load=detail&id=14039&search_result_index=17&stav[]=Z&stav[]=T&stav[]=R&stav[]=O&stav[]=k&stav[]=n&stav[]=e&znamka[]=500&znamka[]=600&znamka_old[]=501&znamka_old[]=601&zanik=5&subtyp[]=kostely#obsah'],
			[50.7829488494, 14.3684555436, 'http://www.znicenekostely.cz/index.php?load=detail&id=6656&search_result_index=12&nej=3#obsah'],
		];
	}

	public function processNoLocationProvider(): array
	{
		return [
			['http://znicenekostely.cz/index.php?load=detail&id=99999999'],
			['https://www.znicenekostely.cz/objekt/detail/99999999'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new ZniceneKostelyCzService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new ZniceneKostelyCzService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new ZniceneKostelyCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processNoLocationProvider
	 */
	public function testProcessNoLocationReal(string $input): void
	{
		$service = new ZniceneKostelyCzService($this->httpTestClients->realRequestor);
		$this->assertServiceNoLocation($service, $input);
	}

	/**
	 * @dataProvider processNoLocationProvider
	 */
	public function testProcessNoLocationOffline(string $input): void
	{
		$service = new ZniceneKostelyCzService($this->httpTestClients->offlineRequestor);
		$this->assertServiceNoLocation($service, $input);
	}
}
