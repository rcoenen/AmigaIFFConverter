<?php
// file: src/AmigaIFFConverter.php

namespace App\AmigaIFFConverter;

// Load dependencies
require_once __DIR__ . '/ImageProcessor.php';
require_once __DIR__ . '/BitplaneConverter.php';
require_once __DIR__ . '/FileWriter.php';
require_once __DIR__ . '/DitheringHandler.php';

ini_set('memory_limit', '256M');

class AmigaIFFConverter
{

    private $imageProcessor;
    private $bitplaneConverter;
    private $fileWriter;
    private $ditheringHandler;

    public function __construct()
    {
        $this->imageProcessor    = new ImageProcessor();
        $this->bitplaneConverter = new BitplaneConverter();
        $this->fileWriter        = new FileWriter();
        $this->ditheringHandler  = new DitheringHandler();
    }

    public function convertToIFF($inputFile, $outputFile, $options)
    {
        // Main conversion logic
        $sourceImage = $this->imageProcessor->loadImage($inputFile);

        // Handle resizing
        if ($options['width'] || $options['height']) {
            $sourceImage = $this->imageProcessor->resizeImage($sourceImage, $options['width'], $options['height']);
        }

        // Generate palette AND get paletted image
        list($palette, $palettedImage) = $this->imageProcessor->extractPalette(
            $sourceImage,
            $options['colors'],
            $options['dither']// Pass dithering option
        );

        // Convert paletted image to bitplanes
        $bitplanes = $this->bitplaneConverter->convertToBitplanes($palettedImage, $palette);

        // Write the final IFF file
        $this->fileWriter->writeIFFFile($outputFile, $bitplanes, $palette, $options);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($palettedImage);
    }
}
