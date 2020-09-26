<?php declare(strict_types=1);

$localConfigFilePath = __DIR__ . '/../../data/config.local.php';
$exampleLocalConfigFilePath = __DIR__ . '/../../data/config.local.example';
if (file_exists($localConfigFilePath) === false) {
	if (copy($exampleLocalConfigFilePath, $localConfigFilePath)) {
		printf('[CopyLocalConfig] Local config file was missing but was created from example file.' . PHP_EOL);
	} else {
		throw new \Exception(sprintf('[CopyLocalConfig] Error while copying example config file "%s" as local config file "%s". Check for warnings, if example file exists and permission in data folder.', $exampleLocalConfigFilePath, $localConfigFilePath));
	}
} else {
	printf('[CopyLocalConfig] Local config file already exist, doing nothing.' . PHP_EOL);
}
