<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Helper;

/**
 * Convert HCT image hex string to image
 */
class ImageConverter
{
    /**
     * Convert hex string to binary data
     *
     * @param string $hexString
     * @return string Binary data
     */
    public function hexToBytes($hexString)
    {
        // Remove any whitespace
        $hexString = preg_replace('/\s+/', '', $hexString);

        // Convert hex string to binary
        return hex2bin($hexString);
    }

    /**
     * Convert binary data to image resource
     *
     * @param string $binaryData
     * @return resource|false GD image resource
     */
    public function bytesToImage($binaryData)
    {
        // Create image from binary string
        $image = imagecreatefromstring($binaryData);

        if ($image === false) {
            return false;
        }

        return $image;
    }

    /**
     * Convert image to 1-bit black and white (monochrome)
     *
     * @param resource $image GD image resource
     * @return resource 1-bit monochrome image
     */
    public function convertTo1Bit($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Create a new image
        $monoImage = imagecreate($width, $height);

        // Allocate only black and white colors for 1-bit
        $white = imagecolorallocate($monoImage, 255, 255, 255);
        $black = imagecolorallocate($monoImage, 0, 0, 0);

        // Convert each pixel to black or white
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $rgb);

                // Calculate brightness
                $brightness = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;

                // Threshold: > 127 = white, <= 127 = black
                $color = ($brightness > 127) ? $white : $black;
                imagesetpixel($monoImage, $x, $y, $color);
            }
        }

        return $monoImage;
    }

    /**
     * Complete conversion: hex string to 1-bit image
     *
     * @param string $hexString HCT image hex string
     * @return resource|false GD image resource or false on failure
     */
    public function hexStringToImage($hexString)
    {
        // Step 1: Hex to bytes
        $binaryData = $this->hexToBytes($hexString);

        if ($binaryData === false || empty($binaryData)) {
            return false;
        }

        // Step 2: Bytes to image
        $image = $this->bytesToImage($binaryData);

        if ($image === false) {
            return false;
        }

        // Step 3: Convert to 1-bit monochrome
        $monoImage = $this->convertTo1Bit($image);

        // Free original image memory
        imagedestroy($image);

        return $monoImage;
    }

    /**
     * Output image to browser
     *
     * @param resource $image GD image resource
     * @param string $format Image format (png, jpg, gif)
     * @return void
     */
    public function outputImage($image, $format = 'png')
    {
        header('Content-Type: image/' . $format);

        switch ($format) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image);
                break;
            case 'gif':
                imagegif($image);
                break;
            case 'png':
            default:
                imagepng($image);
                break;
        }

        imagedestroy($image);
    }

    /**
     * Save image to file
     *
     * @param resource $image GD image resource
     * @param string $filePath File path to save
     * @param string $format Image format (png, jpg, gif)
     * @return bool
     */
    public function saveImage($image, $filePath, $format = 'png')
    {
        $result = false;

        switch ($format) {
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($image, $filePath);
                break;
            case 'gif':
                $result = imagegif($image, $filePath);
                break;
            case 'png':
            default:
                $result = imagepng($image, $filePath);
                break;
        }

        imagedestroy($image);
        return $result;
    }

    /**
     * Get image as base64 data URL
     *
     * @param resource $image GD image resource
     * @param string $format Image format (png, jpg, gif)
     * @return string Base64 data URL
     */
    public function imageToBase64DataUrl($image, $format = 'png')
    {
        ob_start();

        switch ($format) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image);
                break;
            case 'gif':
                imagegif($image);
                break;
            case 'png':
            default:
                imagepng($image);
                break;
        }

        $imageData = ob_get_clean();
        imagedestroy($image);

        $base64 = base64_encode($imageData);
        return 'data:image/' . $format . ';base64,' . $base64;
    }
}
