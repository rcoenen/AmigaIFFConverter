<?php

// file: src/ImageProcessor.php

namespace App\AmigaIFFConverter;

class ImageProcessor
{
    public function loadImage($file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($file);
                break;
            case 'png':
                $img = imagecreatefrompng($file);
                break;
            default:
                throw new Exception("Unsupported image format");
        }

        // Create a new true color image with black background
        $newImg = imagecreatetruecolor(imagesx($img), imagesy($img));
        $black  = imagecolorallocate($newImg, 0, 0, 0);
        imagefill($newImg, 0, 0, $black);

        // Copy the image onto black background
        imagecopy($newImg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imagedestroy($img);

        return $newImg;
    }

    public function resizeImage($image, $targetWidth, $targetHeight)
    {
        $width  = imagesx($image);
        $height = imagesy($image);

        if ($targetWidth == $width && $targetHeight == $height) {
            return $image;
        }

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        // Create black background
        $black = imagecolorallocate($resized, 0, 0, 0);
        imagefill($resized, 0, 0, $black);

        // Resample onto black background
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        return $resized;
    }

    public function extractPalette($image, $maxColors, $dither = true)
    {
        // Create working copy with black background
        $workingImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        $black        = imagecolorallocate($workingImage, 0, 0, 0);
        imagefill($workingImage, 0, 0, $black);
        imagecopy($workingImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        // First convert without dithering to get base palette
        imagetruecolortopalette($workingImage, false, $maxColors);

        // Create final image with black background
        $palettedImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        $black         = imagecolorallocate($palettedImage, 0, 0, 0);
        imagefill($palettedImage, 0, 0, $black);
        imagecopy($palettedImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        // Now convert with dithering if enabled
        imagetruecolortopalette($palettedImage, $dither, $maxColors);

        // Get palette from working image to maintain consistency
        $palette = [];
        for ($i = 0; $i < $maxColors; $i++) {
            $color     = imagecolorsforindex($workingImage, $i);
            $palette[] = [
                'r' => $color['red'],
                'g' => $color['green'],
                'b' => $color['blue'],
            ];
        }

        // Ensure first color is black (background)
        $palette[0] = ['r' => 0, 'g' => 0, 'b' => 0];

        imagedestroy($workingImage);
        return [$palette, $palettedImage];
    }
}
