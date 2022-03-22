<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FevGamesService;
use PHPUnit\Framework\TestCase;

final class FevGamesServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		FevGamesService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
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
		$this->markTestSkipped('No events are available.');

		$collection = FevGamesService::processStatic('https://fevgames.net/ifs/event/?e=15677')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('56.144046,10.198955', $collection[0]->__toString());

		$collection = FevGamesService::processStatic('https://fevgames.net/ifs/event/?e=17739')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('39.754640,-104.994104', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testNoIntelLink(): void
	{
		$collection = FevGamesService::processStatic('https://fevgames.net/ifs/event/?e=12342')->getCollection();
		$this->assertCount(0, $collection);
	}
}
