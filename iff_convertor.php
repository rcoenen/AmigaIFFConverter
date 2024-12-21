#!/usr/bin/env php
<?php

    // File: iff_convertor.php

    /**
     * IFF ILBM Converter - Command Line Interface
     *
     * Converts modern image formats to Amiga IFF ILBM format.
     *
     * Usage:
     *   php iff_convertor.php --input=<input_image> --output=<output_iff> [--width=<width>] [--height=<height>] [--colors=<colors>] [--dither=<dither>] [--compress=<compress>] [--ham=<ham>]
     */

    require_once __DIR__ . '/src/AmigaIFFConverter.php';
    use App\AmigaIFFConverter\AmigaIFFConverter;

    // Default options
    $defaults = [
        'width' => null,    // Source width if null
        'height' => null,   // Source height if null
        'colors' => 32,     // 2-256, must be power of 2
        'dither' => true,   // Enable dithering
        'compress' => true, // Enable ByteRun1 compression
        'ham' => false,     // Enable HAM mode
        'input' => null,    // Input file
        'output' => null,   // Output file
    ];

    // Show help if no arguments
    if ($argc < 2 || in_array('--help', $argv) || in_array('-h', $argv)) {
        echo <<<HELP
		Usage: php iff_convertor.php [options]

Required:
  --input=<file>    Input JPEG or PNG file
  --output=<file>   Output IFF file

Optional:
  --width=<n>       Target width in pixels (default: source width)
  --height=<n>      Target height in pixels (default: source height)
  --colors=<n>      Number of colors (2-256, must be power of 2, default: 32)
  --dither=<bool>   Enable dithering (default: true)
  --compress=<bool> Enable ByteRun1 compression (default: true)
  --ham=<bool>      Enable HAM mode (default: false)

Examples:
  php iff_convertor.php --input=photo.jpg --output=output.iff --width=320 --height=256
  php iff_convertor.php --input=photo.jpg --output=output.iff --ham=true --compress=true
  php iff_convertor.php --input=photo.png --output=output.iff --colors=16 --dither=false

HELP;
        exit(1);
    }

    // Parse command line arguments
    $options = $defaults;
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2));
            if (count($parts) == 2) {
                $key   = $parts[0];
                $value = $parts[1];

                // Type conversion based on option
                switch ($key) {
                    case 'width':
                    case 'height':
                    case 'colors':
                        $value = (int) $value;
                        break;
                    case 'dither':
                    case 'compress':
                    case 'ham':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                }

                $options[$key] = $value;
            }
        }
    }

    // Validate required options
    if (! $options['input'] || ! $options['output']) {
        echo "Error: Input and output files are required\n";
        echo "Use --help for usage information\n";
        exit(1);
    }

    // Validate file exists
    if (! file_exists($options['input'])) {
        echo "Error: Input file '{$options['input']}' not found\n";
        exit(1);
    }

    try {
        $converter = new AmigaIFFConverter();
        $converter->convertToIFF($options['input'], $options['output'], $options);
        echo "Conversion complete: {$options['output']}\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
}
