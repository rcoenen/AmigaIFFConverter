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

    public function extractPalette($image, $maxColors, $dither = true, $chipset = 'ECS')
    {
        // Determine maximum colors based on chipset
        if ($chipset === 'ECS') {
            $maxColors = min($maxColors, 32); // Limit to 32 colors for ECS
        } elseif ($chipset === 'AGA') {
            $maxColors = min($maxColors, 256); // Future support for 256 colors for AGA
        }

        // Create a new image clamped to 12-bit RGB (Amiga ECS palette)
        $clampedImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $color = imagecolorat($image, $x, $y);

                // Extract 24-bit RGB values
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                // Clamp to 12-bit (ECS range: 0-15)
                $r = (int) ($r / 16) * 16;
                $g = (int) ($g / 16) * 16;
                $b = (int) ($b / 16) * 16;

                // Allocate the clamped color to the new image
                $newColor = imagecolorallocate($clampedImage, $r, $g, $b);
                imagesetpixel($clampedImage, $x, $y, $newColor);
            }
        }

        // Reduce the clamped image to the desired number of colors
        imagetruecolortopalette($clampedImage, $dither, $maxColors);

        // Extract the palette from the reduced image
        $palette = [];
        for ($i = 0; $i < $maxColors; $i++) {
            $color = imagecolorsforindex($clampedImage, $i);

            // Convert to 12-bit RGB (0-15 range for ECS compatibility)
            $palette[] = [
                'r' => (int) ($color['red'] / 16),
                'g' => (int) ($color['green'] / 16),
                'b' => (int) ($color['blue'] / 16),
            ];
        }

        // Ensure the first palette color is black (for background compatibility)
        $palette[0] = ['r' => 0, 'g' => 0, 'b' => 0];

        // Clean up
        imagedestroy($clampedImage);

        // Return the palette and the reduced image
        return [$palette, $clampedImage];
    }

}
