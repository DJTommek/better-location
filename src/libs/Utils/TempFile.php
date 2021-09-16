<?php declare(strict_types=1);

namespace App\Utils;

use App\Config;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Nette\Utils\FileSystem;

/**
 * Create file with specific filename and content. File is automatically deleted once it is not referenced.
 */
class TempFile
{
	/** @var string Temporary directory, where temporary directories and files are created */
	const TEMP_DIR = Config::FOLDER_TEMP . '/temp-file';

	/** @var string */
	private $filePath;
	/** @var string */
	private $dirPath;

	/**
	 * @param string $fileName
	 * @param null|string|UrlImmutable|Url $content Content, which should be saved in file. If URL is provided, content is downloaded from that url. Null will create empty file.
	 */
	public function __construct(string $fileName, $content = null)
	{
		$this->dirPath = self::TEMP_DIR . '/' . uniqid();
		$this->filePath = $this->dirPath . '/' . $fileName;
		if ($content instanceof \Nette\Http\UrlImmutable || $content instanceof \Nette\Http\Url) {
			$content = file_get_contents($content->getAbsoluteUrl());
		}
		if (is_null($content)) {
			$content = '';
		}
		FileSystem::write($this->filePath, $content);
	}

	public function __destruct()
	{
		FileSystem::delete($this->dirPath);
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}
}
