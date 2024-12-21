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
                return imagecreatefromjpeg($file);
            case 'png':
                return imagecreatefrompng($file);
            default:
                throw new Exception("Unsupported image format");
        }
    }

    public function resizeImage($image, $targetWidth, $targetHeight)
    {
        $width  = imagesx($image);
        $height = imagesy($image);

        if ($targetWidth == $width && $targetHeight == $height) {
            return $image;
        }

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        return $resized;
    }

    public function extractPalette($image, $maxColors, $dither = true)
    {
        // Create working copy
        $workingImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagecopy($workingImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        // First convert without dithering to get base palette
        imagetruecolortopalette($workingImage, false, $maxColors);

        // Create final image
        $palettedImage = imagecreatetruecolor(imagesx($image), imagesy($image));
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

        imagedestroy($workingImage);
        return [$palette, $palettedImage];
    }

}
