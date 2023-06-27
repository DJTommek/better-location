<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\GooglePlaceApi;
use PHPUnit\Framework\TestCase;

final class GooglePlaceApiTest extends TestCase
{
	/**
	 * @return array<array{string, array<mixed>}>
	 */
	public function addressComponentsProvider(): array
	{
		return [
			['NZ', $this->file(__DIR__ . '/fixtures/address-component-1.json')],
			[null, $this->file(__DIR__ . '/fixtures/address-component-2-no-country.json')],
			['CZ', $this->file(__DIR__ . '/fixtures/address-component-3.json')],
		];
	}

	/**
	 * @dataProvider addressComponentsProvider
	 * @param array<mixed> $addressComponents
	 */
	public function testGetCountryCodeFromAddressComponents(?string $expectedCode, array $addressComponents): void
	{
		$this->assertSame($expectedCode, GooglePlaceApi::getCountryCodeFromAddressComponents($addressComponents));
	}

	/**
	 * @return array<mixed>
	 */
	private function file(string $filename): array
	{
		$content = file_get_contents($filename);
		return json_decode($content, false);
	}
}
