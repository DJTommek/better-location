<?php declare(strict_types=1);

namespace App\Web\Maintenance;

use App\Maintenance\LogArchiver;
use App\Utils\Utils;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Telegram;

class LogPresenter extends MainPresenter
{
	public function __construct(
		private readonly LogArchiver $logArchiver,
	) {
	}

	public function action(): void
	{
		if ($this->request->getQuery('password') !== \App\Config::CRON_PASSWORD) {
			$this->apiResponse(true, 'Invalid password', httpCode: self::HTTP_FORBIDDEN);
		}

		$result = new \stdClass();
		$result->archiveName = null;
		$result->deletedLogFilesCount = 0;

		try {
			if (Utils::globalGetToBool('createArchive', true) === true) {
				$result->archiveName = $this->logArchiver->createLogArchive();
			}
			if (Utils::globalGetToBool('deleteOldLogs', false) === true) {
				$result->deletedLogFilesCount = $this->logArchiver->deleteOldFiles();
			}
		} catch (\Throwable $exception) {
			$this->apiResponse(
				true,
				'Error occured during log maintenance: ' . $exception->getMessage(),
				$result,
				httpCode: self::HTTP_INTERNAL_SERVER_ERROR,
			);
		}

		$this->apiResponse(false, 'Log maintenance was finished', $result);
	}
}

