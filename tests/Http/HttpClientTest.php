<?php declare(strict_types=1);

namespace Tests\Pluginer;

use App\Http\HttpClient;
use App\Http\Response;
use Nette\Utils\Json;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
	private \GuzzleHttp\Handler\MockHandler $mock;
	private HttpClient $httpClient;

	public function setUp(): void
	{
		$this->mock = new \GuzzleHttp\Handler\MockHandler();
		$handlerStack = \GuzzleHttp\HandlerStack::create($this->mock);
		$this->httpClient = new HttpClient([
			'handler' => $handlerStack,
		]);
	}

	public function testBasic(): void
	{
		$body = 'Hello world';
		$this->setMockup($body);
		$response = $this->httpClient->get('http://tomas.palider.cz/');
		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame($body, $response->body());

		$bodyJson = ['valid' => 'json', 'hi' => 123987, 'foo' => null];
		$bodyString = Json::encode($bodyJson);
		$this->setMockup($bodyString);
		$response = $this->httpClient->get('http://tomas.palider.cz/');
		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame($bodyString, $response->body());
		$this->assertSame($bodyJson, $response->json(true));
		$this->assertSame($bodyJson, (array)$response->json(false));
	}

	/**
	 * @return array<array<mixed>>
	 */
	public function invalidUrlProvider(): array
	{
		return [
			['non url'],
			[''],
			['tomas.palider.cz'],
		];
	}

	/**
	 * @dataProvider invalidUrlProvider
	 */
	public function testInvalidUrl(string $invalidUrl): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->httpClient->get($invalidUrl);
	}

	private function setMockup(string $expectedBody): void
	{
		$mockedResponse = new \GuzzleHttp\Psr7\Response(200, body: $expectedBody);
		$this->mock->append($mockedResponse);
	}
}
