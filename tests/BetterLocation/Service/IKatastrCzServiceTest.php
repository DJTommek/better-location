<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\IKatastrCzService;
use Tests\HttpTestClients;

final class IKatastrCzServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return IKatastrCzService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://www.ikatastr.cz/#kde=50.087451,14.420671,17&info=50.087451,14.420671',
			'https://www.ikatastr.cz/#kde=50.100000,14.500000,17&info=50.100000,14.500000',
			'https://www.ikatastr.cz/#kde=-50.200000,14.600000,17&info=-50.200000,14.600000', // round down
			'https://www.ikatastr.cz/#kde=50.300000,-14.700001,17&info=50.300000,-14.700001', // round up
			'https://www.ikatastr.cz/#kde=-50.400000,-14.800008,17&info=-50.400000,-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			// Only 'kde' parameter
			[true, 'https://www.ikatastr.cz/#kde=50.067277,14.432601,13'],
			[true, 'https://ikatastr.cz/#kde=50.067277,14.432601,13'],
			[true, 'https://www.ikatastr.cz/#kde=50.067277,14.432601'],
			[true, 'https://www.ikatastr.cz/#kde=-50.067277,14.432601'],
			[true, 'https://www.ikatastr.cz/#kde=50.067277,-14.432601'],

			// Only 'info' parameter
			[true, 'https://www.ikatastr.cz/#info=50.067277,14.432601'],
			[true, 'https://ikatastr.cz/#info=50.067277,14.432601'],
			[true, 'https://www.ikatastr.cz/#info=50.067277,14.432601'],
			[true, 'https://www.ikatastr.cz/#info=50.067277,14.432601,10'],

			// 'kde' and 'info' parameters at once
			[true, 'https://www.ikatastr.cz/#kde=50.084463,14.428683,17&info=50.085175,14.430284'],
			[true, 'https://ikatastr.cz/#kde=50.084463,14.428683,17&info=50.085175,14.430284'],
			[true, 'https://www.ikatastr.cz/#info=50.085175,14.430284&kde=50.084463,14.428683,17'],

			[false, 'non url'],
			[false, 'https://example.com/?ll=50.087451,14.420671'],
			[false, 'https://www.ikatastr.cz/'],
			[false, 'https://www.ikatastr.cz/#kde=99.067277,14.432601,13'],
			[false, 'https://www.ikatastr.cz/#kde=49.067277,814.432601,13'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				[
					[50.085702, 14.42698, IKatastrCzService::TYPE_MAP],
				],
				'https://www.ikatastr.cz/#kde=50.085702,14.42698,17',
			],
			[
				[
					[50.085702, 14.42698, IKatastrCzService::TYPE_MAP],
				],
				'https://www.ikatastr.cz/#kde=50.085702,14.42698',
			],
			[
				[
					[50.085702, 14.42698, IKatastrCzService::TYPE_INFO],
				],
				'https://www.ikatastr.cz/#info=50.085702,14.42698,17',
			],
			[
				[
					[50.085702, 14.42698, IKatastrCzService::TYPE_INFO],
				],
				'https://ikatastr.cz/#info=50.085702,14.42698',
			],
			[
				[
					[50.085702, 14.42698, IKatastrCzService::TYPE_MAP],
					[50.085702, 14.42698, IKatastrCzService::TYPE_INFO],
				],
				'https://www.ikatastr.cz/#kde=50.085702,14.42698,17&info=50.085702,14.42698',
			],
			[
				[
					[50.085702, 14.42698, IKatastrCzService::TYPE_MAP],
					[50.085702, 14.42698, IKatastrCzService::TYPE_INFO],
				],
				'https://ikatastr.cz/#info=50.085702,14.42698&kde=50.085702,14.42698,17',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new IKatastrCzService();
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @dataProvider processProvider
	 * @group request
	 */
	public function testProcess(array $expectedResults, string $input): void
	{

		$service = new IKatastrCzService();
		$this->assertServiceLocations($service, $input, $expectedResults);
	}
}
