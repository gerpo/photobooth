<?php

/**
 * Function to apply the polaroid effect to an image.
 *
 * @param  string  $resource  Image resource
 * @param  float  $rotation  Image rotation angle
 * @param  int  $rbcc  red background color component
 * @param  int  $gbcc  green background color component
 * @param  int  $bbcc  blue background color component
 * @return resource image with the polaroid effect applied
 */
function frameAndText($resource, $rotation, $rbcc, $gbcc, $bbcc)
{
    // We create a new image
    $img = imagecreatetruecolor(imagesx($resource) + 50, imagesy($resource) + 74);
    $white = imagecolorallocate($img, 255, 255, 255);

    // We fill in the new white image
    imagefill($img, 0, 0, $white);

    // We copy the image to which we want to apply the polariod effect in our new image.
    imagecopy($img, $resource, 25, 25, 0, 0, imagesx($resource), imagesy($resource) - 120);

    // Clear cach
    imagedestroy($resource);

    // Add Text 1st line
    $black = imagecolorallocate($img, 0, 0, 0);
    $fontPath = '../resources/fonts/Richardson.otf';
    $text = "Happy New Year";
    $fontSize = 52;

    list($left, $bottom, $right, , , $top) = imageftbbox($fontSize, 0, $fontPath, $text);
    $xOffsetLine1 = ($right - $left) / 2;
    $yOffsetLine1 = ($bottom - $top) / 2;

    imagefttext($img, $fontSize, 0, (imagesx($img) / 2) - $xOffsetLine1, imagesy($img) - 120 + $yOffsetLine1 + 5,
        $black, $fontPath, $text);

    // Add Text 2nd line
    $fontPath = '../resources/fonts/ZenKakuGothicAntique-Regular.ttf';
    $text = "31.12.2022";
    $fontSize = 48;

    list($left, $bottom, $right, , , $top) = imageftbbox($fontSize, 0, $fontPath, $text);
    $xOffsetLine2 = ($right - $left) / 2;
    $yOffsetLine2 = ($bottom - $top) / 2;

    imagefttext($img, $fontSize, 0, (imagesx($img) / 2) - $xOffsetLine2,
        imagesy($img) - 120 + $yOffsetLine1 * 1.5 + $yOffsetLine2 * 1.5 + 5, $black, $fontPath, $text);

    // We destroy the image we have been working with
    #imagedestroy($img);

    // We return the rotated image
    return $img;
}
