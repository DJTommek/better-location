<?php declare(strict_types=1);

namespace Tests\Pluginer;

use App\Pluginer\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
	private const FIXTURES_PATH = __DIR__ . '/fixtures';
	private const FIXTURES_VALID_PATH = self::FIXTURES_PATH . '/valid';
	private const FIXTURES_INVALID_PATH = self::FIXTURES_PATH . '/invalid';

	/**
	 * @dataProvider validJsonProvider
	 */
	public function testValid(string $dataName, \stdClass $data): void
	{
		$validator = new Validator();
		$validator->validate($data);
		if ($validator->isValid() === false) {
			$this->fail(sprintf(
				'JSON fixture "%s" should be valid but returned errors: "%s"',
				$dataName,
				implode('", "', $validator->getErrors()),
			));
		}

		$this->assertEmpty($validator->getErrors(), 'Inconsistent behavior - validator correctly returned is valid but incorrectly returned list of errors.');
	}

	/**
	 * @dataProvider invalidJsonProvider
	 */
	public function testInvalid(string $dataName, \stdClass $data): void
	{
		$validator = new Validator();
		$validator->validate($data);
		if ($validator->isValid() === true) {
			$this->fail(sprintf('JSON fixture "%s" should be invalid but it is valid.', $dataName));
		}

		$this->assertNotEmpty($validator->getErrors(), 'Inconsistent behavior - validator correctly returned is invalid valid but incorrectly returned no errors.');
	}

	public function testMissingValidateRun1(): void
	{
		$validator = new Validator();
		$this->expectException(\RuntimeException::class);
		$validator->isValid();
	}

	public function testMissingValidateRun2(): void
	{
		$validator = new Validator();
		$this->expectException(\RuntimeException::class);
		$validator->getErrors();
	}

	private static function validJsonProvider(): \Iterator
	{
		$pattern = self::FIXTURES_VALID_PATH . '/*.json';
		yield from self::jsonProvider($pattern);
	}

	private static function invalidJsonProvider(): \Iterator
	{
		$pattern = self::FIXTURES_INVALID_PATH . '/*.json';
		yield from self::jsonProvider($pattern);
	}

	private static function jsonProvider(string $pattern): \Iterator
	{
		$files = glob($pattern);
		foreach ($files as $file) {
			$json = json_decode(file_get_contents($file), flags: JSON_THROW_ON_ERROR);
			yield [basename($file), $json];
		}
	}
}
