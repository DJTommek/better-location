<?php declare(strict_types=1);

namespace App\Maintenance;

use App\Config;
use App\Utils\Formatter;
use Nette\Utils\FileSystem;
use unreal4u\TelegramAPI\Telegram;

/**
 * - all paths must have forward slashes, even on Windows: '/'
 * - no path should end with /
 */
class LogArchiver
{
	private const ARCHIVE_FILE_FORMAT = 'U_' . Config::DATETIME_FILE_FORMAT;
	private const DIR_TO_SAVE_ARCHIVE = Config::FOLDER_DATA . '/archived-logs';
	private const WHITELISTED_EXTENSIONS = [
		'jsonl',
		'html',
		'log',
		'zip'
	];
	private const TOO_SMALL_THRESHOLD = 1024 * 512; // 512 kilobytes

	/**
	 * Return paths, that should be backed up.
	 * key: name of directory in archive
	 * value: absolute path to directory, which should be archived
	 *
	 * @return array<string,string>
	 */
	private function pathsToBackup(): array
	{
		return [
			'tracy-log' => FileSystem::unixSlashes(Config::getTracyPath()),
			'log' => FileSystem::unixSlashes(Config::FOLDER_DATA . '/log'),
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function pathsToCleanup(): array
	{
		return [
			'log' => FileSystem::unixSlashes(Config::FOLDER_DATA . '/log'),
			'archives' => FileSystem::unixSlashes(self::DIR_TO_SAVE_ARCHIVE),
		];
	}

	/**
	 * @param array<string> $dirsToBackup
	 * @return \Generator<\stdClass>
	 */
	private function iterateLogFiles(array $dirsToBackup): \Generator
	{
		foreach ($dirsToBackup as $dirNameInArchive => $directoryToBackup) {
			$dirIterator = new \RecursiveDirectoryIterator($directoryToBackup, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
			$iterator = new \RecursiveIteratorIterator($dirIterator);

			foreach ($iterator as $fileInfo) {
				assert($fileInfo instanceof \SplFileInfo);
				if ($fileInfo->isFile() === false) {
					continue;
				}
				if (in_array($fileInfo->getExtension(), self::WHITELISTED_EXTENSIONS, true) === false) {
					continue;
				}
				$result = new \stdClass();
				$result->directoryToBackup = $directoryToBackup;
				$result->dirNameInArchive = $dirNameInArchive;
				$result->fileInfo = $fileInfo;
				yield $result;
			}
		}
	}

	public function createLogArchive(): string
	{
		$archiveFilePath = FileSystem::unixSlashes(sprintf(
			'%s/better-location-logs-%s.zip',
			self::DIR_TO_SAVE_ARCHIVE,
			(new \DateTime())->format(self::ARCHIVE_FILE_FORMAT),
		));
		FileSystem::createDir(dirname($archiveFilePath));

		$archive = new \ZipArchive();
		$result = $archive->open($archiveFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
		if ($result !== true) {
			throw new \RuntimeException(sprintf('Error while creating archive file, result: "%s"', $result));
		}

		foreach ($this->iterateLogFiles($this->pathsToBackup()) as $item) {
			$filepathAbsolute = $item->fileInfo->getPathname();
			assert(str_starts_with($filepathAbsolute, $item->directoryToBackup));
			$filepathInArchive = $item->dirNameInArchive . mb_substr($filepathAbsolute, mb_strlen($item->directoryToBackup));
			$archive->addFile($filepathAbsolute, $filepathInArchive);
		}

		if ($archive->numFiles === 0) {
			throw new \RuntimeException('Archive was created with no files.');
		}

		if ($archive->close() === false) {
			throw new \RuntimeException('Unable to properly close created archive.');
		};
		$this->validateArchive($archiveFilePath);
		return basename($archiveFilePath);
	}

	private function validateArchive(string $archiveFilePath): void
	{
		if (file_exists($archiveFilePath) === false) {
			throw new \RuntimeException('Testing archive file failed - archive was not created');
		}
		$archiveFileSize = filesize($archiveFilePath);
		if ($archiveFileSize < self::TOO_SMALL_THRESHOLD) {
			throw new \RuntimeException(sprintf(
				'Testing archive file failed - file size has only %s.',
				Formatter::size($archiveFileSize)
			));
		}

		// test zip by opening it
		$archive = new \ZipArchive();
		$openResult = $archive->open($archiveFilePath);
		if ($openResult !== true) {
			throw new \RuntimeException('Testing archive file failed - unable to open archive for, error code: ' . $openResult);
		}
	}

	public function deleteOldFiles(): int
	{
		clearstatcache();
		$now = \time();

		$deletedLogFilesCount = 0;

		foreach ($this->iterateLogFiles($this->pathsToCleanup()) as $item) {
			assert($item->fileInfo instanceof \SplFileInfo);

			$diff = $now - $item->fileInfo->getMTime();
			if ($diff < Config::LOGS_OLD_THRESHOLD) {
				continue;
			}
			FileSystem::delete($item->fileInfo->getPathname());

			$deletedLogFilesCount++;
		}
		return $deletedLogFilesCount;
	}
}

