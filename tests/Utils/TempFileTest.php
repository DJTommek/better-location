<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\TempFile;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use PHPUnit\Framework\TestCase;

final class TempFileTest extends TestCase
{
	/** @var string Normalized path based on running operating system (forward or back slashes) to base temporary directory */
	private static $tempDir;
	/** @var string[] Files and directories in this array are expected, that will not be deleted after all tests are completed */
	private static $expectedNonDeletedItems = [];

	public static function setUpBeforeClass(): void
	{
		self::$tempDir = FileSystem::normalizePath(TempFile::TEMP_DIR);
		FileSystem::delete(self::$tempDir);  // Directory should to be empty on start for checking final status
	}

	/**
	 * Default intended behavior, where file and directory are deleted once reference is lost.
	 */
	public function testTempFileAutodestruct(): void
	{
		$tempfile = new TempFile('auto-destruct.txt', 'random content');
		$tempFileFullPath = $tempfile->get()->getPathname();
		$tempDirFullPath = $tempfile->get()->getPath();

		$this->assertInstanceOf(TempFile::class, $tempfile);
		$this->assertInstanceOf(\SplFileInfo::class, $tempfile->get());

		$this->assertSame('auto-destruct.txt', $tempfile->get()->getBasename());
		$this->assertTrue(Strings::startsWith($tempFileFullPath, self::$tempDir));
		$this->assertTrue($tempfile->getPathname() === $tempfile->get()->getPathname());

		$this->assertFileExists($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		$this->assertSame('random content', file_get_contents($tempFileFullPath));

		unset($tempfile); // automatically destructed
		$this->assertDirectoryDoesNotExist($tempDirFullPath);
		$this->assertFileDoesNotExist($tempFileFullPath);
	}

	/**
	 * Manually calling delete() as simulating __destroy().
	 */
	public function testTempFileManualDestruct(): void
	{
		$tempfile = new TempFile('manual-destruct.txt');
		$tempFileFullPath = $tempfile->getPathname();
		$tempDirFullPath = $tempfile->get()->getPath();

		$this->assertFileExists($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		$tempfile->delete();
		$tempfile->delete(); // calling delete multiple times should do nothing, since file was already deleted.

		$this->assertDirectoryDoesNotExist($tempDirFullPath);
		$this->assertFileDoesNotExist($tempFileFullPath);
	}

	/**
	 * Manually delete file should not throw any error, even on deleting.
	 */
	public function testTempFileManuallyDeletedFile(): void
	{
		$tempfile = new TempFile('manual-file-delete.txt');
		$tempFileFullPath = $tempfile->getPathname();
		$tempDirFullPath = $tempfile->get()->getPath();

		$this->assertFileExists($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		unlink($tempFileFullPath);

		$this->assertDirectoryExists($tempDirFullPath); // dir still exists, only file was deleted

		unset($tempfile); // file is already deleted, so it will delete directory

		$this->assertDirectoryDoesNotExist($tempDirFullPath);
		$this->assertFileDoesNotExist($tempFileFullPath);
	}

	/**
	 * Multiple temporary files with same filename.
	 */
	public function testTempFileMultipleSameName(): void
	{
		$tempfile1 = new TempFile('identical-file-name.txt', 'some random content for first temporary file');
		$tempfile2 = new TempFile('identical-file-name.txt', 'another random content for second temporary file');

		$this->assertTrue($tempfile1->get()->getFilename() === $tempfile2->get()->getFilename());
		$this->assertSame('some random content for first temporary file', file_get_contents($tempfile1->getPathname()));
		$this->assertSame('another random content for second temporary file', file_get_contents($tempfile2->getPathname()));
	}

	/**
	 * Missing permissions to delete file. No exception should be thrown as it doesn't have any impact on application itself.
	 */
	public function testTempFileUnableToDeleteFile(): void
	{
		$tempfile = new TempFile('unable-to-delete-file.txt');
		$tempFileFullPath = $tempfile->getPathname();
		$tempDirFullPath = $tempfile->get()->getPath();

		$this->assertFileExists($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		chmod($tempFileFullPath, 0000); // no one has permission for this file
		unset($tempfile); // normally it would throw "Unable to delete '/full/path/to/file/random-dir-name/unable-to-delete.txt'. Permission denied"

		$this->assertFileExists($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		self::$expectedNonDeletedItems[] = $tempFileFullPath;
		self::$expectedNonDeletedItems[] = $tempDirFullPath;
	}

	/**
	 * Missing permissions to delete directory. No exception can be thrown as it doesn't have any impact on application itself.
	 */
	public function testTempFileUnableToDeleteDirectory(): void
	{
		$tempfile = new TempFile('unable-to-delete-dir.txt');
		$tempFileFullPath = $tempfile->getPathname();
		$tempDirFullPath = $tempfile->get()->getPath();

		$this->assertFileExists($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		chmod($tempDirFullPath, 0000); // no one has permission for this directory
		unset($tempfile); // normally it would throw "Unable to delete directory '/full/path/to/file/random-dir-name/'. Permission denied"

		// File was deleted but directory not
		$this->assertFileDoesNotExist($tempFileFullPath);
		$this->assertDirectoryExists($tempDirFullPath);

		self::$expectedNonDeletedItems[] = $tempDirFullPath;
	}

	/**
	 * Cleanup: delete intentionally not deleted files and then check if temporary directory is empty.
	 */
	public static function tearDownAfterClass(): void
	{
		// Some files and directories were not deleted because of permission denied errors...
		self::assertCount(3, self::$expectedNonDeletedItems);
		$iterator = \Nette\Utils\Finder::find('**')->from(self::$tempDir);
		self::assertCount(3, $iterator);

		// ... so delete them now...
		foreach (self::$expectedNonDeletedItems as $pathname) {
			chmod($pathname, 0700);
			FileSystem::delete($pathname);
		}

		// ... and now directory should be completely empty.
		$iterator = \Nette\Utils\Finder::find('**')->from(self::$tempDir);
		self::assertCount(0, $iterator); // all files and directories should be deleted
	}
}
