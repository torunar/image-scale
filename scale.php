<?php
/*
* Description:  Resize and crop image to maximal side length, maintaining aspect ratio
* Requirements: PHP 4 >= 4.3.0, PHP 5, PHP 7, GD graphics library
* Version:      1.0
* Author:       Mike Schekotov <torunar@gmail.com>
*/

define('IMAGE_SIZE_MIN',  200);
define('IMAGE_SIZE_MAX', 1200);
define('IMAGE_ASPECT',    3/4);

/*
* Resize and crop image to maximal side length, maintaining aspect ratio
* @param  $sourceImage - path to image
* @param  $minSize     - minimal side length required to process image
* @param  $maxSize     - maximal side length image will be scaled and cropped to
* @param  $destAspect  - aspect of out image
* @return                Array, containing scaled image name, path, width and height
 *                       null on failure
*/
function image_resize_to_fit($sourceImage, $minSize = IMAGE_SIZE_MIN, $maxSize = IMAGE_SIZE_MAX, $destAspect = IMAGE_ASPECT) {
  list($width, $height, $type) = getimagesize($sourceImage);

  if ($width <= $minSize && $height <= $minSize) {
    $parts = explode(DIRECTORY_SEPARATOR, $sourceImage);
    return ['image' => array_pop($parts), 'path' => $sourceImage, 'width' => $width, 'height' => $height];
  }

  $sourceAspect = $width / $height;

  switch ($type) {
    case IMAGETYPE_GIF:
      $source = imagecreatefromgif($sourceImage);
      break;
    case IMAGETYPE_JPEG:
      $source = imagecreatefromjpeg($sourceImage);
      break;
    case IMAGETYPE_PNG:
      $source = imagecreatefrompng($sourceImage);
      break;
    case IMAGETYPE_BMP:
      $source = imagecreatefromwbmp($sourceImage);
      break;
    default:
      return null;
      break;
  }

  // geometry to crop to
  if ($sourceAspect < $destAspect) {
    $tempW = $width;
    $tempH = (int) ($tempW / $destAspect);
  }
  else {
    $tempW      = $width;
    $tempH      = $height;
    $destAspect = $sourceAspect;
  }

  // top left corner of desired area
  $x0 = ($width - $tempW)  / 2;
  $y0 = ($height - $tempH) / 2;

  // image scaled to desired aspect
  $tempImage = imagecreatetruecolor($tempW, $tempH);
  imagecopy(
    $tempImage,
    $source,
    0, 0,
    $x0, $y0,
    $tempW, $tempH
  );

  // get desired geometry from temp geometry
  $destW = $tempW;
  $destH = $tempH;
  if ($tempW > $maxSize || $tempH > $maxSize) {
    if ($destAspect < 1) {
      $destH = $maxSize;
      $destW  = (int) ($maxSize * $destAspect);
    }
    else {
      $destW  = $maxSize;
      $destH = (int) ($maxSize / $destAspect);
    }
  }

  // scale image
  $scaledImage = imagecreatetruecolor($destW, $destH);
  imagecopyresampled(
    $scaledImage,
    $tempImage,
    0, 0,
    0, 0,
    $destW, $destH,
    $tempW, $tempH
  );

  // write image to file
  $destPath = preg_replace('/\.([^.]*)$/', "_{$destW}x{$destH}.$1", $sourceImage);
  switch ($type) {
    case IMAGETYPE_GIF:
      imagegif($scaledImage, $destPath);
      break;
    case IMAGETYPE_JPEG:
      imagejpeg($scaledImage, $destPath);
      break;
    case IMAGETYPE_PNG:
      imagepng($scaledImage, $destPath);
      break;
    case IMAGETYPE_BMP:
      imagewbmp($scaledImage, $destPath);
      break;
  }

  $parts = explode(DIRECTORY_SEPARATOR, $destPath);

  return ['image' => array_pop($parts), 'path' => $destPath, 'width' => $destW, 'height' => $destH];
}