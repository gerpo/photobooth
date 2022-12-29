<?php
require_once '../lib/config.php';
require_once '../lib/helper.php';
require_once '../lib/s3/S3Upload.php';

$filename = $_GET['filename'];

$url = (new S3Upload())->getPresignedURL($filename).'&response-content-disposition=attachment';

include '../vendor/phpqrcode/lib/full/qrlib.php';

switch ($config['qr']['ecLevel']) {
    case 'QR_ECLEVEL_L':
        $ecLevel = QR_ECLEVEL_L;
        break;
    case 'QR_ECLEVEL_M':
        $ecLevel = QR_ECLEVEL_M;
        break;
    case 'QR_ECLEVEL_Q':
        $ecLevel = QR_ECLEVEL_Q;
        break;
    case 'QR_ECLEVEL_H':
        $ecLevel = QR_ECLEVEL_H;
        break;
    default:
        $ecLevel = QR_ECLEVEL_M;
        break;
}

QRcode::png($url, false, $ecLevel, 8);
