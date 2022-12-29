<?php

header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';
require_once '../lib/s3/S3Upload.php';

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
}

function uploadToS3(string $file)
{
    global $config;

    $filename_tmp = $config['foldersAbs']['tmp'] . DIRECTORY_SEPARATOR . $file;

    if (!file_exists($filename_tmp)) {
        $errormsg = basename($_SERVER['PHP_SELF']) . ': File ' . $filename_tmp . ' does not exist';
        logErrorAndDie($errormsg);
    }

    if ($config['aws']['upload_files'] && (empty($config['aws']['access_key']) || empty($config['aws']['secret']) || empty($config['aws']['bucket']))) {
        $ErrorData = [
            'error' => 'AWS environment variables not probably set.',
            'access_key' => $config['aws']['access_key'],
            'bucket' => $config['aws']['bucket'],
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        $ErrorString = json_encode($ErrorData);
        logError($ErrorData);
        die($ErrorString);
    }

    try {
        (new S3Upload())->multipartUpload($filename_tmp);
    } catch (\Exception $e) {
        $ErrorData = [
            'error' => 'AWS S3 upload not successful.',
            'file' => $file,
            'filename_tmp' => $filename_tmp,
            'exception' => $e->getMessage(),
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        $ErrorString = json_encode($ErrorData);
        logError($ErrorData);
        die($ErrorString);
    }
}

$file = $_POST['file'];

uploadToS3($file);

$LogData = [
    'file' => $file,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
echo $LogString;