#!/usr/bin/env php
<?php

// iff_convertor.php

/**
 * IFF ILBM Converter - Command Line Interface
 * 
 * Converts modern image formats to Amiga IFF ILBM format.
 * 
 * Usage:
 *   php iff_convertor.php <input_image> <output_iff> [width] [height] [colors] [dither] [compress]
 */

// Load the main converter class
require_once __DIR__ . '/src/AmigaIFFConverter.php';

use App\AmigaIFFConverter\AmigaIFFConverter;

// Display help if no arguments provided
if ($argc < 3) {
    echo <<<HELP
Usage: php iff_convertor.php <input_image> <output_iff> [width] [height] [colors] [dither] [compress]

Arguments:
  input_image  - Path to input JPEG or PNG file
  output_iff   - Path for output IFF file
  width        - Optional target width (default: source width)
  height       - Optional target height (default: source height)
  colors       - Number of colors (2-256, must be power of 2, default: 32)
  dither       - Enable dithering (true/false, default: true)
  compress     - Enable ByteRun1 compression (true/false, default: true)

Example:
  php iff_convertor.php photo.jpg output.iff 320 200 16 false true

HELP;
    exit(1);
}

// Parse arguments
$inputFile = $argv[1];
$outputFile = $argv[2];
$options = [
    'width' => isset($argv[3]) ? (int)$argv[3] : null,
    'height' => isset($argv[4]) ? (int)$argv[4] : null,
    'colors' => isset($argv[5]) ? (int)$argv[5] : 32,
    'dither' => isset($argv[6]) ? filter_var($argv[6], FILTER_VALIDATE_BOOLEAN) : true,
    'compress' => isset($argv[7]) ? filter_var($argv[7], FILTER_VALIDATE_BOOLEAN) : true,
];

try {
    $converter = new AmigaIFFConverter();
    $converter->convertToIFF($inputFile, $outputFile, $options);
    echo "Conversion complete: $outputFile\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}