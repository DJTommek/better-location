<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\UniversalWebsite;

use App\BetterLocation\Service\UniversalWebsiteService\LdJsonProcessor;
use App\BetterLocation\Service\UniversalWebsiteService\UniversalWebsiteService;
use App\Cache\NetteCachePsr16;
use Nette\Caching\Storages\DevNullStorage;
use Psr\SimpleCache\CacheInterface;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\HttpTestClients;

final class UniversalWebsiteServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;
	private readonly LdJsonProcessor $ldJsonProcessor;
	private readonly CacheInterface $cache;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
		$storage = new DevNullStorage();
		$this->cache = new NetteCachePsr16($storage);
		$this->ldJsonProcessor = new LdJsonProcessor();
	}

	protected function getServiceClass(): string
	{
		return UniversalWebsiteService::class;
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
			[true, 'https://www.tripadvisor.com/Restaurant_Review-g274707-d4154273-Reviews-Tavern_U_Krale_Brabantskeho-Prague_Bohemia.html'],
			[true, 'https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-hradec-kralove/'],
			[true, 'https://www.pronto-ny.com/'],

			[false, 'something random'],
		];
	}

	public static function processLdJsonGeoProvider(): array
	{
		return [
			'hornbach.cz Hradec Králové' => [[[50.182083, 15.801783, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-hradec-kralove/">HORNBACH Hradec Králové</a>']], 'https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-hradec-kralove/'],
			'hornbach.cz Ostrava' => [[[49.826736, 18.208172, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-ostrava/">HORNBACH Ostrava</a>']], 'https://www.hornbach.cz/moje-prodejna/prodejna-hornbach-ostrava/'],
			'hornbach.sk Bratislava - Devínska Nová Ves' => [[[48.2056, 17.020494, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.sk/moja-predajna/predajna-hornbach-bratislava-devinska-nova-ves/">HORNBACH Bratislava - Devínska Nová Ves</a>']], 'https://www.hornbach.sk/moja-predajna/predajna-hornbach-bratislava-devinska-nova-ves/'],
			'hornbach.ch Luzern-Littau' => [[[47.057755, 8.257341, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.hornbach.ch/mein-markt/baumarkt-hornbach-luzern-littau/">HORNBACH Luzern-Littau</a>']], 'https://www.hornbach.ch/mein-markt/baumarkt-hornbach-luzern-littau/'],
			'pronto-ny.com' => [[[40.770766050505, -73.96467034232786, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://www.pronto-ny.com/">Restaurant Pronto</a>']], 'https://www.pronto-ny.com/'],
			'vietnamnet.vn' => [[[21.0140338034431, 105.83156603015266, UniversalWebsiteService::TYPE_SCHEMA_JSON_GEO, '🪄<a href="https://vietnamnet.vn/">VietNamNet</a>']], 'https://vietnamnet.vn/'],
			'JSON available but no location' => [[], 'https://restauraceoaza.cz'],
			'No json available' => [[], 'https://tomas.palider.cz'],
			'Big file (27 MB)' => [[[49.495514199971, 18.25647, UniversalWebsiteService::TYPE_EXIF, '<a href="https://pldr-gallery.palider.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDIwMDUwOF8xMzUzMTElMjAoYmlnJTIwZmlsZSkuanBn&amp;compress=false" target="_blank">EXIF</a>']], 'https://pldr-gallery.palider.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDIwMDUwOF8xMzUzMTElMjAoYmlnJTIwZmlsZSkuanBn&compress=false'],
			'Big file (286 MB) without any coordinates' => [[], 'https://download.gimp.org/gimp/v3.0/windows/gimp-3.0.4-setup.exe'],
		];
	}

	public static function processExifProvider(): array
	{
		return [
			'tomas.palider.cz profile photo' => [[[48.137297777778, 11.575583388889, UniversalWebsiteService::TYPE_EXIF, '<a href="https://tomas.palider.cz/profile-photo-original.jpg" target="_blank">EXIF</a>']], 'https://tomas.palider.cz/profile-photo-original.jpg'],
			'image in pldr-gallery.palider.cz' => [[[50.698351222222, 15.736727416667, UniversalWebsiteService::TYPE_EXIF, '<a href="https://pldr-gallery.palider.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMTE5MjEuanBn" target="_blank">EXIF</a>']], 'https://pldr-gallery.palider.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMTE5MjEuanBn'],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new UniversalWebsiteService($this->httpTestClients->mockedHttpClient, $this->cache, $this->ldJsonProcessor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processLdJsonGeoProvider
	 * @dataProvider processExifProvider
	 */
	public function testProcessReal(array $expectedResults, string $input): void
	{
		$service = new UniversalWebsiteService($this->httpTestClients->realHttpClient, $this->cache, $this->ldJsonProcessor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}

	/**
	 * @dataProvider processLdJsonGeoProvider
	 * @dataProvider processExifProvider
	 */
	public function testProcessOffline(array $expectedResults, string $input): void
	{
		$service = new UniversalWebsiteService($this->httpTestClients->offlineHttpClient, $this->cache, $this->ldJsonProcessor);
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
