<?php
header('Content-Type: application/json');

require_once '../lib/db.php';
require_once '../lib/config.php';
require_once '../lib/filter.php';
require_once '../lib/polaroid.php';
require_once '../lib/resize.php';
require_once '../lib/collage.php';
require_once '../lib/applyText.php';
require_once '../lib/applyEffects.php';
require_once '../lib/log.php';

if (!extension_loaded('gd')) {
    $errormsg = basename($_SERVER['PHP_SELF']).': GD library not loaded! Please enable GD!';
    logErrorAndDie($errormsg);
}

if (empty($_POST['file'])) {
    $errormsg = basename($_SERVER['PHP_SELF']).': No file provided';
    logErrorAndDie($errormsg);
}

function copyAndResize(string $file)
{
    global $config;

    $file_tmp = $config['foldersAbs']['tmp'].DIRECTORY_SEPARATOR.$file;
    $file_original = $config['foldersAbs']['original'].DIRECTORY_SEPARATOR.$file;

    copy($file_tmp, $file_original);

    $sizeReduction = 0.3;
    list($width, $height) = getimagesize($file_tmp);
    $newWidth = $width * $sizeReduction;
    $newHeight = $height * $sizeReduction;

    //Load
    $tmp = imagecreatetruecolor($newWidth, $newHeight);
    $source = imagecreatefromjpeg($file_tmp);

    //Resize
    imagecopyresized($tmp, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $sourceAspectRatio = $newWidth / $newHeight;

    // Calculate the aspect ratio of the new image
    $newAspectRatio = 148 / 100;

    if ($newAspectRatio < $sourceAspectRatio) {
        // The new image has a wider aspect ratio than the source image, so we need to adjust the height
        $newImageHeight = $newWidth / $newAspectRatio;
        $newImageWidth = $newWidth;
    } else {
        // The new image has a taller or equal aspect ratio to the source image, so we need to adjust the width
        $newImageWidth = $newHeight * $newAspectRatio;
        $newImageHeight = $newHeight;
    }

    $tmp_cropped = imagecreatetruecolor($newImageWidth, $newImageHeight);
    $x = ($newWidth - $newImageWidth) / 2;
    $y = ($newHeight - $newImageHeight) / 2;
    imagecopyresampled($tmp_cropped, $tmp, 0, 0, $x, $y, $newImageWidth, $newImageHeight, $newImageWidth,
        $newImageHeight);

    //Output
    imagejpeg($tmp_cropped, $file_tmp);
    imagedestroy($source);
    imagedestroy($tmp);
    imagedestroy($tmp_cropped);
}

$file = $_POST['file'];

$quality = 100;
$imageModified = false;
$image_filter = false;

if (!isset($_POST['style'])) {
    $errormsg = basename($_SERVER['PHP_SELF']).': No style provided';
    logErrorAndDie($errormsg);
}

if (!isset($_POST['filter'])) {
    $ErrorData = [
        'warning' => 'No filter provided! Using plain image filter!',
    ];
    logError($ErrorData);
    $image_filter = 'plain';
}

if (!empty($_POST['filter']) && $_POST['filter'] !== 'plain') {
    $image_filter = $_POST['filter'];
}

// Check collage configuration
if ($_POST['style'] === 'collage') {
    if ($config['textoncollage']['enabled']) {
        testFile($config['textoncollage']['font']);
    }
}

$startTime = microtime(true);
$execTimes = [];
$srcImages = [];
$srcImages[] = $file;

$filename_tmp = $config['foldersAbs']['tmp'].DIRECTORY_SEPARATOR.$file;

if ($_POST['style'] === 'collage') {
    list($collageSrcImagePaths, $srcImages) = getCollageFiles($config['collage'], $filename_tmp, $file, $srcImages);

    if (!createCollage($collageSrcImagePaths, $filename_tmp, $image_filter)) {
        $errormsg = basename($_SERVER['PHP_SELF']).': Could not create collage';
        logErrorAndDie($errormsg);
    }
}

foreach ($srcImages as $image) {
    $filename_photo = $config['foldersAbs']['images'].DIRECTORY_SEPARATOR.$image;
    $filename_keying = $config['foldersAbs']['keying'].DIRECTORY_SEPARATOR.$image;
    $filename_tmp = $config['foldersAbs']['tmp'].DIRECTORY_SEPARATOR.$image;
    $filename_thumb = $config['foldersAbs']['thumbs'].DIRECTORY_SEPARATOR.$image;

    if (!file_exists($filename_tmp)) {
        $errormsg = basename($_SERVER['PHP_SELF']).': File '.$filename_tmp.' does not exist';
        logErrorAndDie($errormsg);
    }

    copyAndResize($file);

    $execTimes[] = microtime(true) - $startTime;
    $imageResource = imagecreatefromjpeg($filename_tmp);
    $execTimes[] = microtime(true) - $startTime;
    if ($_POST['style'] === 'collage' && $file != $image) {
        $editSingleCollage = true;
        $picture_frame = $config['collage']['frame'];
    } else {
        $editSingleCollage = false;
        $picture_frame = $config['picture']['frame'];
    }

    if ($_POST['style'] !== 'collage' || $editSingleCollage) {
        list($imageResource, $imageModified) = editSingleImage($config, $imageResource, $image_filter,
            $editSingleCollage, $picture_frame, $_POST['style'] == 'collage');
    }
    $execTimes[] = microtime(true) - $startTime;
    if ($config['keying']['enabled'] || $_POST['style'] === 'chroma') {
        $chroma_size = substr($config['keying']['size'], 0, -2);
        $chromaCopyResource = resizeImage($imageResource, $chroma_size, $chroma_size);
        imagejpeg($chromaCopyResource, $filename_keying, $config['jpeg_quality']['chroma']);
        imagedestroy($chromaCopyResource);
    }

    $configText = $config['textonpicture'];
    list($imageResource, $imageModified) = addTextToImage($configText, $imageResource, $imageModified,
        $_POST['style'] == 'collage');
    $execTimes[] = microtime(true) - $startTime;
    // image scale, create thumbnail
    $thumb_size = substr($config['picture']['thumb_size'], 0, -2);
    $thumbResource = resizeImage($imageResource, $thumb_size, $thumb_size);

    imagejpeg($thumbResource, $filename_thumb, $config['jpeg_quality']['thumb']);
    imagedestroy($thumbResource);
    $execTimes[] = microtime(true) - $startTime;
    compressImage($config, $imageModified, $imageResource, $filename_tmp, $filename_photo);
    $execTimes[] = microtime(true) - $startTime;
    if (!$config['picture']['keep_original']) {
        unlink($filename_tmp);
    }

    imagedestroy($imageResource);

    // insert into database
    if ($config['database']['enabled']) {
        if ($_POST['style'] !== 'chroma' || ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === true)) {
            appendImageToDB($image);
        }
    }

    // Change permissions
    $picture_permissions = $config['picture']['permissions'];
    chmod($filename_photo, octdec($picture_permissions));
}

if ($_POST['style'] === 'chroma' && $config['live_keying']['show_all'] === false) {
    unlink($filename_photo);
    unlink($filename_thumb);
}

$LogData = [
    'file' => $file,
    'images' => $srcImages,
    'exec_times' => $execTimes,
    'php' => basename($_SERVER['PHP_SELF']),
];
$LogString = json_encode($LogData);
if ($config['dev']['loglevel'] > 1) {
    logError($LogData);
}
echo $LogString;
