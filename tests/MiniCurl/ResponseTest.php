<?php declare(strict_types=1);

namespace Tests\MiniCurl;

use App\MiniCurl\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
	private const FIXTURES_PATH = __DIR__ . '/fixtures';
	private const FIXTURES_VALID_PATH = self::FIXTURES_PATH . '/valid';

	/**
	 * @dataProvider responseProvider
	 */
	public function testValid(string $dataName, string $rawResponse): void
	{
		$response = new Response($rawResponse, []);

		$this->assertNotSame([], $response->getHeaders(), sprintf('Check if line ending in "%s" fixture are LF', $dataName));
		$this->assertNotSame('', $response->getBody(), sprintf('Check if line ending in "%s" fixture are LF', $dataName));

		$this->assertSame('Apache', $response->getHeader('server'));
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));

		$body = $response->getBody();
		$this->assertTrue(str_starts_with($body, '{"meta":{"date":1234567890}'));
		$json = $response->getBodyAsJson();
		$this->assertSame(1_234_567_890, $json->meta->date);
		$this->assertSame('ingressPortal', $json->locations[0]->descriptions[2]->key);
	}

	public static function responseProvider(): \Iterator
	{
		$pattern = self::FIXTURES_VALID_PATH . '/*.txt';
		$files = glob($pattern);
		foreach ($files as $file) {
			$basename = basename($file);
			$rawResponse = file_get_contents($file);
			// @HACK Unable to properly save files, to have CRLF line ends (\r\n), so it must be converted manually here
			$rawResponse2 = str_replace("\n", Response::LINE_SEPARATORS, $rawResponse);
			yield $basename => [$basename, $rawResponse2];
		}
	}
}
