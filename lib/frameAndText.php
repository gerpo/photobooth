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

    // Add Text
    $black = imagecolorallocate($img, 0, 0, 0);
    $fontPath = '../resources/fonts/Richardson.otf';
    $text = "Happy New Year";
    $fontSize = 52

    list($left, $bottom, $right, , , $top) = imageftbbox($fontSize, 0, $fontPath, $text);
    $xOffset = ($right - $left) / 2;
    $yOffset = ($bottom - $top) / 2;

    imagefttext($img, $fontSize, 0, (imagesx($img) / 2) - $xOffset, imagesy($img) -120 + $yOffset + 5, $black, $fontPath, $text);


    // We destroy the image we have been working with
    #imagedestroy($img);

    // We return the rotated image
    return $img;
}
