#!/usr/bin/env php
<?php
/**
 * IFF ILBM Converter - Command Line Interface
 * 
 * This script provides a command-line interface for converting modern image formats
 * to Amiga IFF ILBM format.
 *
 * Usage:
 *   php iff_convertor.php <input_image> <output_iff> [width] [height] [colors] [dither] [compress]
 *
 * Arguments:
 *   input_image  - Path to input JPEG or PNG file
 *   output_iff   - Path for output IFF file
 *   width        - Optional target width (default: source width)
 *   height       - Optional target height (default: source height)
 *   colors       - Number of colors (2-256, must be power of 2, default: 32)
 *   dither       - Enable dithering (true/false, default: true)
 *   compress     - Enable ByteRun1 compression (true/false, default: true)
 *
 * Example:
 *   php iff_convertor.php photo.jpg output.iff 320 200 16 false true
 */

require_once dirname(__FILE__) . '/AmigaIFFConverter.php';

// Display help if no arguments provided
if ($argc < 3) {
    echo "Usage: php iff_convertor.php <input_image> <output_iff> [width] [height] [colors] [dither] [compress]\n\n";
    echo "Arguments:\n";
    echo "  input_image  - Path to input JPEG or PNG file\n";
    echo "  output_iff   - Path for output IFF file\n";
    echo "  width        - Optional target width (default: source width)\n";
    echo "  height       - Optional target height (default: source height)\n";
    echo "  colors       - Number of colors (2-256, must be power of 2, default: 32)\n";
    echo "  dither       - Enable dithering (true/false, default: true)\n";
    echo "  compress     - Enable ByteRun1 compression (true/false, default: true)\n\n";
    echo "Example:\n";
    echo "  php iff_convertor.php photo.jpg output.iff 320 200 16 false true\n";
    exit(1);
}

// Parse command line arguments
$inputFile = $argv[1];
$outputFile = $argv[2];

// Validate input file
if (!file_exists($inputFile)) {
    echo "Error: Input file does not exist: $inputFile\n";
    exit(1);
}

// Validate input file format
$extension = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
    echo "Error: Input file must be JPEG or PNG format\n";
    exit(1);
}

// Set up conversion options
$options = [
    'width' => isset($argv[3]) ? (int)$argv[3] : null,
    'height' => isset($argv[4]) ? (int)$argv[4] : null,
    'colors' => isset($argv[5]) ? (int)$argv[5] : 32,
    'dither' => isset($argv[6]) ? filter_var($argv[6], FILTER_VALIDATE_BOOLEAN) : true,
    'compress' => isset($argv[7]) ? filter_var($argv[7], FILTER_VALIDATE_BOOLEAN) : true,
];

// Validate color count
if ($options['colors'] < 2 || $options['colors'] > 256 || !is_power_of_two($options['colors'])) {
    echo "Error: Color count must be a power of 2 between 2 and 256\n";
    exit(1);
}

// Helper function to check if a number is a power of 2
function is_power_of_two($n) {
    return ($n & ($n - 1)) === 0 && $n > 0;
}

try {
    // Create converter instance and process the image
    $converter = new AmigaIFFConverter();
    $converter->convertToIFF($inputFile, $outputFile, $options);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}