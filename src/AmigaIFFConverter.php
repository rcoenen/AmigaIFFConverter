<?php

// file: src/AmigaIFFConverter.php

namespace App\AmigaIFFConverter;

// Load dependencies
require_once __DIR__ . '/ImageProcessor.php';
require_once __DIR__ . '/BitplaneConverter.php';
require_once __DIR__ . '/FileWriter.php';
require_once __DIR__ . '/DitheringHandler.php';
require_once __DIR__ . '/HAMConverter.php'; // Add new HAM converter

ini_set('memory_limit', '256M');

class AmigaIFFConverter
{
    private $imageProcessor;
    private $bitplaneConverter;
    private $fileWriter;
    private $ditheringHandler;
    private $hamConverter; // Add HAM converter

    public function __construct()
    {
        $this->imageProcessor    = new ImageProcessor();
        $this->bitplaneConverter = new BitplaneConverter();
        $this->fileWriter        = new FileWriter();
        $this->ditheringHandler  = new DitheringHandler();
        $this->hamConverter      = new HAMConverter(); // Initialize HAM converter
    }

    public function convertToIFF($inputFile, $outputFile, $options)
    {
        // Main conversion logic
        $sourceImage = $this->imageProcessor->loadImage($inputFile);

        // Handle resizing
        if ($options['width'] || $options['height']) {
            $sourceImage = $this->imageProcessor->resizeImage($sourceImage, $options['width'], $options['height']);
        }

        if (isset($options['ham']) && $options['ham']) {
            // HAM mode logic
            list($palette, $palettedImage) = $this->imageProcessor->extractPalette(
                $sourceImage,
                16,  // HAM mode requires 16 base colors
                false// No dithering for HAM mode
            );
            $bitplanes = $this->hamConverter->convertToHAM($sourceImage, $palette);
        } else {
            // Standard mode logic
            list($palette, $palettedImage) = $this->imageProcessor->extractPalette(
                $sourceImage,
                $options['colors'], // Colors defined in CLI options
                $options['dither']
            );
            $bitplanes = $this->bitplaneConverter->convertToBitplanes($palettedImage, $palette);
        }

        // Write the final IFF file
        $this->fileWriter->writeIFFFile($outputFile, $bitplanes, $palette, $options);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($palettedImage);
    }
}
