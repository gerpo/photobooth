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
    imagecopy($img, $resource, 25, 25, 0, 0, imagesx($resource), imagesy($resource) - 100);

    // Clear cach
    imagedestroy($resource);

    // Border color
    $color = imagecolorallocate($img, 192, 192, 192);
    // We put a gray border to our image.
    imagerectangle($img, 0, 0, imagesx($img) - 4, imagesy($img) - 4, $color);

    // Add Text
    $black = imagecolorallocate($img, 0, 0, 0);
    $fontPath = '../resources/fonts/Richardson.otf';

    imagefttext($img, 40, 0, (imagesx($img) / 2) - 10, imagesy($img) - 80, $black, $fontPath, "Happy New Year");


    // We destroy the image we have been working with
    #imagedestroy($img);

    // We return the rotated image
    return $img;
}
