<?php declare(strict_types=1);

namespace App\Utils;

use App\Config;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Tracy\Debugger;

/**
 * Create temporary file with specific filename and content. File is automatically deleted once it is not referenced.
 */
class TempFile
{
	/** @var string Temporary directory, where temporary directories and files are created */
	const TEMP_DIR = Config::FOLDER_TEMP . DIRECTORY_SEPARATOR . 'temp-file';

	/** @var \SplFileInfo */
	private $splFileInfo;

	/**
	 * @param string $fileName
	 * @param null|string|UrlImmutable|Url $content Content, which should be saved in file. If URL is provided, content is downloaded from that url. Null will create empty file.
	 * @throws \Nette\IOException
	 */
	public function __construct(string $fileName, $content = null)
	{
		if ($content instanceof UrlImmutable || $content instanceof Url) {
			$content = file_get_contents($content->getAbsoluteUrl());
		}
		if (is_null($content)) {
			$content = '';
		}
		$pathname = FileSystem::joinPaths(self::TEMP_DIR, uniqid(), $fileName);
		$this->splFileInfo = new \SplFileInfo($pathname);
		FileSystem::write($this->getPathname(), $content);
	}

	public function get(): \SplFileInfo
	{
		return $this->splFileInfo;

	}

	/** @return string Full path for temporary file */
	public function getPathname(): string
	{
		return $this->splFileInfo->getPathname();
	}

	/** Manually delete temporary file (called automatically on object destructing) */
	public function delete(): void
	{
		try {
			FileSystem::delete($this->splFileInfo->getPath());
		} catch (IOException $exception) {
			Debugger::log($exception, Debugger::ERROR); // Do not throw exception, just save to log
		}
	}

	public function __destruct()
	{
		$this->delete();
	}
}
