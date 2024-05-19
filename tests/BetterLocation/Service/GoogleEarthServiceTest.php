<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\GoogleEarthService;

final class GoogleEarthServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return GoogleEarthService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://earth.google.com/web/@50.087451,14.420671,0a,100000.00d,35y,0h,0t,0r',
			'https://earth.google.com/web/@50.100000,14.500000,0a,100000.00d,35y,0h,0t,0r',
			'https://earth.google.com/web/@-50.200000,14.600000,0a,100000.00d,35y,0h,0t,0r',
			'https://earth.google.com/web/@50.300000,-14.700001,0a,100000.00d,35y,0h,0t,0r',
			'https://earth.google.com/web/@-50.400000,-14.800008,0a,100000.00d,35y,0h,0t,0r',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testIsValid(): void
	{
		$this->assertTrue(GoogleEarthService::validateStatic('https://earth.google.com/web/@44.26122114,-94.16696951,11001291.02957891a,0d,35y,4.9638h,0.0000t,0.0000r?utm_source=earth7&utm_campaign=vine&hl=en'));

		$this->assertFalse(GoogleEarthService::validateStatic('https://example.com/?ll=50.087451,14.420671'));
		$this->assertFalse(GoogleEarthService::validateStatic('non url'));
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValidUsingProvider(bool $expectedIsValid, string $link): void
	{
		$this->assertSame($expectedIsValid, GoogleEarthService::validateStatic($link));
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			[true, 'https://earth.google.com/web/@44.26122114,-94.16696951,11001291.02957891a,0d,35y,4.9638h,0.0000t,0.0000r?utm_source=earth7&utm_campaign=vine&hl=en'],
			[true, 'https://earth.google.com/web/@39.2813063,-101.65257194,1515.74837363a,1279689.66756418d,35y,334.9808h,0t,0r/data=MikKJwolCiExZ194VHl0bl9rZmEwc1FOWHVrOFotc3JrNGh2TnpRQmIgAToDCgEw'],
			[true, 'https://earth.google.com/web/@0,-0.784,0a,22251752.77375655d,35y,0h,0t,0r'],
			[true, 'https://earth.google.com/web/search/prague/@50.05966962,14.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r/data=CnAaRhJACiQweDQ3MGI5MzljMDk3MDc5OGI6MHg0MDBhZjBmNjYxNjQwOTAZDY2CO6sJSUAhS1gbYyfgLEAqBnByYWd1ZRgCIAEiJgokCe4DRmh3OkZAEfaDZsvzWiNAGfJ2xnsYWTjAIa33jaJjIWXAOgMKATA'],
			[true, 'https://earth.google.com/web/search/prague/@50.05966962,14.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r'],
			[true, 'https://earth.google.com/web/@50.05966962,14.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r'],
			[true, 'https://earth.google.com/web/@50.05966962,14.46562341'],
			[true, 'https://earth.google.com/web/@50.08710746,14.4738564,264.3849997a,69392.56062373d,1y,0h,0t,0r/data=CksaSRJDCiUweDQ3MGI5MTVjYzdjZDU0YmQ6MHg0NDYyMWRlMDNhMjdlZDUwKhpWZXRlcmluw6FybsOtIGtsaW5pa2EgSVZFVBgBIAE'],
			// Rotated and tilted
			[true, 'https://earth.google.com/web/@-51.61275669,-69.00007761,12.16397112a,14345.56485655d,5y,-108.38486268h,37.03507913t,0r/data=OgMKATA'],
			// Street view
			[true, 'https://earth.google.com/web/search/prague/@50.0880052,14.42140173,192.54037796a,0d,60y,197.53007094h,97.3817651t,0r/data=CigiJgokCe4DRmh3OkZAEfaDZsvzWiNAGfJ2xnsYWTjAIa33jaJjIWXAIhoKFkxvdjhZS01jMTZjNXU3Y0xJcFAwR0EQAjoDCgEw'],
			[true, 'https://earth.google.com/web/search/prague/@50.0880052,14.42140173,192.54037796a,0d,60y,197.53007094h,97.3817651t,0r'],

			[false, 'non url'],
			[false, 'https://example.com/?ll=50.087451,14.420671'],
			[false, 'https://earth.google.com/'],
			[false, 'https://earth.google.com/web/@500.05966962,14.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r'],
			[false, 'https://earth.google.com/web/@50.05966962,-940.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r'],
		];
	}

	public function testProcess(): void
	{
		$collection = GoogleEarthService::processStatic('https://earth.google.com/web/@44.26122114,-94.16696951,11001291.02957891a,0d,35y,4.9638h,0.0000t,0.0000r?utm_source=earth7&utm_campaign=vine&hl=en')->getCollection();
		$this->assertOneInCollection(44.26122114,-94.16696951, null, $collection);

		$collection = GoogleEarthService::processStatic('https://earth.google.com/web/@39.2813063,-101.65257194,1515.74837363a,1279689.66756418d,35y,334.9808h,0t,0r/data=MikKJwolCiExZ194VHl0bl9rZmEwc1FOWHVrOFotc3JrNGh2TnpRQmIgAToDCgEw')->getCollection();
		$this->assertOneInCollection(39.2813063,-101.65257194, null, $collection);

		$collection = GoogleEarthService::processStatic('https://earth.google.com/web/@39.2813063,-101.65257194,1515.74837363a,1279689.66756418d,35y,334.9808h,0t,0r/data=MikKJwolCiExZ194VHl0bl9rZmEwc1FOWHVrOFotc3JrNGh2TnpRQmIgAToDCgEw')->getCollection();
		$this->assertOneInCollection(39.2813063,-101.65257194, null, $collection);

		$collection = GoogleEarthService::processStatic('https://earth.google.com/web/search/prague/@50.05966962,14.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r/data=CnAaRhJACiQweDQ3MGI5MzljMDk3MDc5OGI6MHg0MDBhZjBmNjYxNjQwOTAZDY2CO6sJSUAhS1gbYyfgLEAqBnByYWd1ZRgCIAEiJgokCe4DRmh3OkZAEfaDZsvzWiNAGfJ2xnsYWTjAIa33jaJjIWXAOgMKATA')->getCollection();
		$this->assertOneInCollection(50.05966962,14.46562341, null, $collection);

		$collection = GoogleEarthService::processStatic('https://earth.google.com/web/@-51.61275669,-69.00007761,12.16397112a,14345.56485655d,5y,-108.38486268h,37.03507913t,0r/data=OgMKATA')->getCollection();
		$this->assertOneInCollection(-51.61275669,-69.00007761, null, $collection);
	}
}
