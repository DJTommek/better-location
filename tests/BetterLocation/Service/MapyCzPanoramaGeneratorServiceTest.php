<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\MapyCzPanoramaGeneratorService;
use PHPUnit\Framework\TestCase;

final class MapyCzPanoramaGeneratorServiceTest extends TestCase
{
	/**
	 * @group request
	 */
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://mapy.cz/zakladni?x=14.420737808284&y=50.087475160816&pano=1&pid=70103886&yaw=4.2080702008141&source=coor&id=14.420671%2C50.087451', MapyCzPanoramaGeneratorService::getLink(50.087451, 14.420671));
		$this->assertSame('https://mapy.cz/zakladni?x=14.500013287616&y=50.100022780494&pano=1&pid=72498060&yaw=3.5072229520173&source=coor&id=14.5%2C50.1', MapyCzPanoramaGeneratorService::getLink(50.1, 14.5));
		$this->assertSame(null, MapyCzPanoramaGeneratorService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame(null, MapyCzPanoramaGeneratorService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame(null, MapyCzPanoramaGeneratorService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		MapyCzPanoramaGeneratorService::getLink(50.087451, 14.420671, true);
	}
}
