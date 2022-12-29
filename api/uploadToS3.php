<?php

header('Content-Type: application/json');

require_once '../lib/config.php';
require_once '../lib/log.php';

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']) . ': No file provided';
    logErrorAndDie($errormsg);
}

function uploadToS3(string $file)
{
    global $config;

    if ($config['aws']['upload_files'] && (empty($config['aws']['access_key']) || empty($config['aws']['secret']) || empty($config['aws']['bucket']))) {
        $ErrorData = [
            'error' => 'AWS environment variables not probably set.',
            'access_key' => $config['aws']['access_key'],
            'bucket' => $config['aws']['bucket'],
            'secret' => getenv('AWS_UPLOAD_ACCESS_KEY'),
            'config' => $config,
            'php' => basename($_SERVER['PHP_SELF']),
        ];
        $ErrorString = json_encode($ErrorData);
        logError($ErrorData);
        die($ErrorString);
    }

    try {
        (new S3Upload())->multipartUpload($file);
    } catch (\Exception $e) {
        $ErrorData = [
            'error' => 'AWS S3 upload not successful.',
            'file' => $file,
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