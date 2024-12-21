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

        // Validate chipset and determine processing logic
        if (! isset($options['chipset'])) {
            $options['chipset'] = 'ECS'; // Default to ECS if not specified
        }

        if ($options['chipset'] === 'ECS') {
            // ECS Standard Mode Logic
            list($palette, $palettedImage) = $this->imageProcessor->extractPalette(
                $sourceImage,
                $options['colors'], // Limit to 32 colors for ECS
                $options['dither'], // Apply dithering if specified
                'ECS'               // Pass chipset to ensure compatibility
            );

            $bitplanes = $this->bitplaneConverter->convertToBitplanes($palettedImage, $palette);
        } elseif ($options['chipset'] === 'AGA') {
            // Placeholder for future AGA logic
            throw new \Exception("AGA chipset support is not implemented yet.");
        } else {
            throw new \Exception("Invalid chipset option: {$options['chipset']}. Use ECS or AGA.");
        }

        // Write the final IFF file
        $this->fileWriter->writeIFFFile($outputFile, $bitplanes, $palette, $options);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($palettedImage);
    }

}
