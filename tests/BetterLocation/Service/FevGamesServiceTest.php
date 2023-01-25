<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FevGamesService;
use PHPUnit\Framework\TestCase;

final class FevGamesServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		FevGamesService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		FevGamesService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(FevGamesService::isValidStatic('https://fevgames.net/ifs/event/?e=15677'));
		$this->assertTrue(FevGamesService::isValidStatic('https://FEVgaMEs.net/ifs/event/?e=15677'));
		$this->assertTrue(FevGamesService::isValidStatic('http://fevgames.net/ifs/event/?e=15677'));
		$this->assertTrue(FevGamesService::isValidStatic('http://www.fevgames.net/ifs/event/?e=15677'));
		$this->assertTrue(FevGamesService::isValidStatic('https://www.fevgames.net/ifs/event/?e=15677'));
		$this->assertTrue(FevGamesService::isValidStatic('https://fevgames.net/ifs/event/?e=12342'));

		$this->assertFalse(FevGamesService::isValidStatic('non url'));
		$this->assertFalse(FevGamesService::isValidStatic('https://blabla.net/ifs/event/?e=15677'));
		$this->assertFalse(FevGamesService::isValidStatic('https://fevgames.net/ifs/event/'));
		$this->assertFalse(FevGamesService::isValidStatic('https://fevgames.net/ifs/event/?e=-15677'));
		$this->assertFalse(FevGamesService::isValidStatic('https://fevgames.net/ifs/event/?e=0'));
		$this->assertFalse(FevGamesService::isValidStatic('https://fevgames.cz/ifs/event/?e=15677'));
		$this->assertFalse(FevGamesService::isValidStatic('https://fevgames.net?e=15677'));
		$this->assertFalse(FevGamesService::isValidStatic('https://fevgames.net/ifs/event/?event=15677'));
	}

	/**
	 * @group request
	 */
	public function testUrl(): void
	{
		$collection = FevGamesService::processStatic('https://fevgames.net/ifs/event/?e=23415')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.963966,14.073212', (string)$collection->getFirst());

		$collection = FevGamesService::processStatic('https://fevgames.net/ifs/event/?e=23448')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-37.815226,144.963781', (string)$collection->getFirst());
	}

	/**
	 * @group request
	 */
	public function testNoIntelLink(): void
	{
		$this->markTestSkipped('Unable to find event, that does not have filled Intel link');

		$collection = FevGamesService::processStatic('https://fevgames.net/ifs/event/?e=12342')->getCollection();
		$this->assertCount(0, $collection);
	}
}
