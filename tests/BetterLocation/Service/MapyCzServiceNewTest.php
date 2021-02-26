<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\MapyCzServiceNew;
use PHPUnit\Framework\TestCase;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;

final class MapyCzServiceNewTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://mapy.cz/zakladni?y=50.087451&x=14.420671&source=coor&id=14.420671%2C50.087451', MapyCzServiceNew::getLink(50.087451, 14.420671));
		$this->assertSame('https://mapy.cz/zakladni?y=50.100000&x=14.500000&source=coor&id=14.500000%2C50.100000', MapyCzServiceNew::getLink(50.1, 14.5));
		$this->assertSame('https://mapy.cz/zakladni?y=-50.200000&x=14.600000&source=coor&id=14.600000%2C-50.200000', MapyCzServiceNew::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://mapy.cz/zakladni?y=50.300000&x=-14.700001&source=coor&id=-14.700001%2C50.300000', MapyCzServiceNew::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://mapy.cz/zakladni?y=-50.400000&x=-14.800008&source=coor&id=-14.800008%2C-50.400000', MapyCzServiceNew::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		MapyCzServiceNew::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidMap(): void
	{
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=50.0695244&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=-50.0695244&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=-50.0695244&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://mapy.cz/zakladni?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/textova?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14&y=50'));

		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?xx=14.4508239&y=50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?y=50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508.239&y=50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695.244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.4508239a&y=50.0695244a'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.a4508239&y=50.a0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zemepisna?x=14.a4508239&y=50.a0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zakladni?x=114.4508239&y=50.0695244&'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('http://mapy.cz/zakladni?x=14.4508239&y=250.0695244'));
	}

	public function testCoordsMap(): void
	{
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,-14.450824', MapyCzServiceNew::processStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('-50.069524,14.450824', MapyCzServiceNew::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=-50.0695244')->getFirst()->__toString());
		$this->assertSame('-50.069524,-14.450824', MapyCzServiceNew::processStatic('https://en.mapy.cz/zakladni?x=-14.4508239&y=-50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('https://en.mapy.cz/zakladni?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('https://mapy.cz/zakladni?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/textova?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.069524,14.450824', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14.4508239&y=50.0695244')->getFirst()->__toString());
		$this->assertSame('50.000000,14.000000', MapyCzServiceNew::processStatic('http://mapy.cz/zemepisna?x=14&y=50')->getFirst()->__toString());
	}

	public function testIsValidCoordId(): void
	{
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14,50.0695244&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50&z=15'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14,50'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,-50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=-14.4508239,50.0695244'));
		$this->assertTrue(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=-14.4508239,-50.0695244'));

		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.0695244a'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239a,50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.450.8239,50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,50.06.95244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,150.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239,-150.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=514.4508239,15.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=-514.4508239,15.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239-50.0695244'));
		$this->assertFalse(MapyCzServiceNew::isValidStatic('https://en.mapy.cz/zakladni?source=coor&id=14.4508239'));
	}

	/**
	 * ID parameter is in coordinates format
	 */
	public function testValidCoordinatesMapyCzId(): void
	{
		$collection = MapyCzServiceNew::processStatic('https://en.mapy.cz/zemepisna?x=14.6666666666&y=48.222&z=16&source=coor&id=14.33333333333333%2C48.77777777777')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('48.777778,14.333333', $collection[0]->__toString());
		$this->assertSame('48.222000,14.666667', $collection[1]->__toString());

		$collection = MapyCzServiceNew::processStatic('https://en.mapy.cz/zemepisna?source=coor&id=14.33333333333333%2C48.77777777777')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.777778,14.333333', $collection[0]->__toString());
	}

}
