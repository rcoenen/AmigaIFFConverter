<?php
/**
 * AmigaIFFConverter - Modern Image to Amiga IFF ILBM Converter
 * 
 * This class converts modern image formats (JPEG/PNG) to Amiga IFF ILBM format.
 * IFF ILBM was the standard image format for the Commodore Amiga computer,
 * supporting features like planar bitmap data, color palettes, and compression.
 *
 * Features:
 * - Converts JPEG and PNG to IFF ILBM
 * - Optional image resizing
 * - Configurable color palettes (2-256 colors)
 * - Optional Floyd-Steinberg dithering
 * - ByteRun1 compression support
 *
 * Limitations:
 * - Only supports basic ILBM features (BMHD, CMAP, BODY chunks)
 * - No HAM (Hold-And-Modify) mode support
 * - No EHB (Extra-Half-Brite) mode support
 * - No support for masks or sprites
 * - No CAMG (Amiga ViewPort modes) support
 * - Color counts must be power of 2 (2, 4, 8, 16, 32, 64, 128, 256)
 *
 * Best results are achieved with standard Amiga resolutions:
 * - 320x200 (NTSC)
 * - 320x256 (PAL)
 * - 640x200 (NTSC Hi-Res)
 * - 640x256 (PAL Hi-Res)
 *
 * @version 1.0.0
 * @license MIT
 */
class AmigaIFFConverter {
    /** @var int Image width in pixels */
    private $width;
    
    /** @var int Image height in pixels */
    private $height;
    
    /** @var int Number of bitplanes (log2 of color count) */
    private $bitplanes;
    
    /** @var array RGB color values for the palette [{r, g, b}] */
    private $palette;
    
    /** @var array 3D array of bitplane data [plane][row][byte] */
    private $imageData;
    
    // IFF ILBM chunk identifiers
    /** @var string IFF container identifier */
    const FORM_HEADER = "FORM";
    
    /** @var string ILBM form type */
    const ILBM_TYPE = "ILBM";
    
    /** @var string Bitmap Header chunk */
    const BMHD_CHUNK = "BMHD";
    
    /** @var string Color Map chunk */
    const CMAP_CHUNK = "CMAP";
    
    /** @var string Bitmap Data chunk */
    const BODY_CHUNK = "BODY";
    
    /**
     * Constructor - initializes default settings
     */
    public function __construct() {
        $this->bitplanes = 5; // Default to 32 colors (2^5)
    }
    
    /**
     * Display progress message with optional percentage
     *
     * @param string $message Progress message to display
     * @param float|null $percent Optional percentage (0-100)
     */
    private function progress($message, $percent = null) {
        if ($percent !== null) {
            echo sprintf("[%.1f%%] %s\n", $percent, $message);
        } else {
            echo "$message\n";
        }
        flush();
    }

    /**
     * Convert an image file to IFF ILBM format
     *
     * @param string $inputFile Path to input image (JPEG or PNG)
     * @param string $outputFile Path for output IFF file
     * @param array $options Conversion options:
     *                      - width: Target width in pixels (null = source width)
     *                      - height: Target height in pixels (null = source height)
     *                      - colors: Number of colors (2-256, must be power of 2)
     *                      - dither: Enable dithering (boolean)
     *                      - compress: Enable ByteRun1 compression (boolean)
     * @throws Exception If conversion fails
     */
    public function convertToIFF($inputFile, $outputFile, $options = []) {
        // Set default options
        $defaults = [
            'width' => null,
            'height' => null,
            'colors' => 32,
            'dither' => true,
            'compress' => true
        ];
        $options = array_merge($defaults, $options);
        
        $this->progress("Starting conversion process...");
        
        // Load source image
        $this->progress("Loading source image...", 5);
        $sourceImage = $this->loadImage($inputFile);
        if (!$sourceImage) {
            throw new Exception("Failed to load source image");
        }
        
        // Process image and convert to IFF
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        $this->progress(sprintf("Loaded image: %dx%d pixels", $origWidth, $origHeight), 10);
        
        // Calculate target dimensions
        $this->width = $options['width'] ?? $origWidth;
        $this->height = $options['height'] ?? $origHeight;
        
        // Resize if necessary
        if ($this->width !== $origWidth || $this->height !== $origHeight) {
            $this->progress("Resizing image...", 15);
            $resized = imagecreatetruecolor($this->width, $this->height);
            imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, 
                             $this->width, $this->height, $origWidth, $origHeight);
            imagedestroy($sourceImage);
            $sourceImage = $resized;
            $this->progress(sprintf("Resized to %dx%d pixels", $this->width, $this->height), 20);
        }
        
        // Set up bitplanes based on color count
        $this->bitplanes = ceil(log($options['colors'], 2));
        $this->progress(sprintf("Using %d bitplanes for %d colors", $this->bitplanes, $options['colors']), 25);
        
        // Convert to paletted format
        $this->convertToPaletted($sourceImage, $options['colors'], $options['dither']);
        
        // Write the IFF file
        $this->writeIFFFile($outputFile, $options['compress']);
        
        imagedestroy($sourceImage);
    }
    
    /**
     * Load an image file using GD
     *
     * @param string $file Path to image file
     * @return resource GD image resource
     * @throws Exception If file format is unsupported
     */
    private function loadImage($file) {
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
    
    /**
     * Convert true color image to paletted format
     *
     * @param resource $image GD image resource
     * @param int $maxColors Maximum number of colors
     * @param bool $dither Enable dithering
     */
    private function convertToPaletted($image, $maxColors, $dither) {
        $this->progress("Converting to paletted format...", 30);
        $palettedImage = imagecreatetruecolor($this->width, $this->height);
        
        // Apply color reduction with optional dithering
        if ($dither) {
            $this->progress("Applying dithering...", 35);
            imagetruecolortopalette($image, true, $maxColors);
        } else {
            $this->progress("Converting to palette without dithering...", 35);
            imagetruecolortopalette($image, false, $maxColors);
        }
        
        // Extract the palette
        $this->palette = [];
        for ($i = 0; $i < $maxColors; $i++) {
            $color = imagecolorsforindex($image, $i);
            $this->palette[] = [
                'r' => $color['red'],
                'g' => $color['green'],
                'b' => $color['blue']
            ];
        }
        
        // Convert to bitplanes
        $this->convertToBitplanes($image);
    }
    
    /**
     * Convert paletted image data to Amiga bitplane format
     * 
     * Amiga graphics use a planar format where each bit of a pixel's color
     * index is stored in a separate bitplane. This converts from chunky
     * pixel format (one byte per pixel) to planar format.
     *
     * @param resource $image GD image resource
     */
    private function convertToBitplanes($image) {
        $rowBytes = ceil($this->width / 8);
        $padding = $rowBytes % 2 ? 1 : 0;
        $this->imageData = [];
        
        $this->progress("Initializing bitplanes...", 45);
        // Create empty bitplanes
        for ($plane = 0; $plane < $this->bitplanes; $plane++) {
            $this->imageData[$plane] = array_fill(0, $this->height, 
                                      array_fill(0, $rowBytes + $padding, 0));
        }
        
        $this->progress("Converting to bitplanes...", 50);
        $totalPixels = $this->height * $this->width;
        $pixelCount = 0;
        
        // Convert pixels to bitplanes
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                $colorIndex = imagecolorat($image, $x, $y);
                
                // Distribute bits across planes
                for ($plane = 0; $plane < $this->bitplanes; $plane++) {
                    $bit = ($colorIndex >> $plane) & 1;
                    $byteOffset = floor($x / 8);
                    $bitOffset = 7 - ($x % 8);
                    
                    if ($bit) {
                        $this->imageData[$plane][$y][$byteOffset] |= (1 << $bitOffset);
                    }
                }
                
                $pixelCount++;
                if ($pixelCount % ($totalPixels / 20) == 0) {
                    $percent = 50 + ($pixelCount / $totalPixels * 30);
                    $this->progress("Converting pixels to bitplanes...", $percent);
                }
            }
        }
        $this->progress("Bitplane conversion complete", 80);
    }
    
    /**
     * Write IFF ILBM file
     *
     * Creates an IFF file with FORM header and ILBM chunks:
     * - BMHD (Bitmap Header)
     * - CMAP (Color Map)
     * - BODY (Bitmap Data)
     *
     * @param string $outputFile Path to output file
     * @param bool $compress Enable ByteRun1 compression
     * @throws Exception If file creation fails
     */
    private function writeIFFFile($outputFile, $compress) {
        $this->progress("Starting IFF file writing...", 85);
        $fp = fopen($outputFile, 'wb');
        if (!$fp) {
            throw new Exception("Failed to create output file");
        }
        
        // Write FORM header
        $this->progress("Writing FORM header...", 86);
        fwrite($fp, self::FORM_HEADER);
        
        // Reserve space for form size
        $formSizePos = ftell($fp);
        fwrite($fp, pack("N", 0));
        
        // Write ILBM type
        fwrite($fp, self::ILBM_TYPE);
        
        // Write chunks
        $this->progress("Writing BMHD chunk...", 87);
        $this->writeBMHDChunk($fp, $compress);
        
        $this->progress("Writing CMAP chunk...", 88);
        $this->writeCMAPChunk($fp);
        
        $this->progress("Writing BODY chunk...", 89);
        $this->writeBODYChunk($fp, $compress);
        
        // Update FORM size
        $this->progress("Finalizing IFF file...", 98);
        $formSize = ftell($fp) - $formSizePos - 4;
        fseek($fp, $formSizePos);
        fwrite($fp, pack("N", $formSize));
        
        fclose($fp);
        
        // Output summary
        $this->progress("IFF file writing complete!", 100);
        echo "\n\nSuccessfully created IFF file: $outputFile\n";
        echo sprintf("Size: %d bytes\n", filesize($outputFile));
        echo sprintf("Dimensions: %dx%d pixels\n", $this->width, $this->height);
        echo sprintf("Colors: %d (%d bitplanes)\n", pow(2, $this->bitplanes), $this->bitplanes);
        echo sprintf("Compression: %s\n", $compress ? "Enabled" : "Disabled");
    }
    
    /**
     * Write BMHD (Bitmap Header) chunk
     * 
     * Format:
     * - w, h: width and height in pixels (UWORD)
     * - x, y: position for image (WORD)
     * - nPlanes: number of bitplanes (UBYTE)
     * - masking: masking type (UBYTE)
     * - compression: compression type (UBYTE)
     * - pad1: unused (UBYTE)
     * - transparentColor: color for transparency (UWORD)
     * - xAspect, yAspect: pixel aspect ratio (UBYTE)
     * - pageWidth, pageHeight: source page size (WORD)
     *
     * @param resource $fp File pointer
     * @param bool $compress Enable compression
     */
    private function writeBMHDChunk($fp, $compress) {
        fwrite($fp, self::BMHD_CHUNK);
        fwrite($fp, pack("N", 20)); // Chunk size
        
        // BitMapHeader structure
        // Format: nnnncccnccnn (20 bytes total)
        $data = pack("n", $this->width) .     // w (2 bytes)
                pack("n", $this->height) .    // h (2 bytes)
                pack("n", 0) .                // x (2 bytes)
                pack("n", 0) .                // y (2 bytes)
                pack("C", $this->bitplanes) . // nPlanes (1 byte)
                pack("C", 0) .                // masking (1 byte)
                pack("C", $compress ? 1 : 0) .// compression (1 byte)
                pack("C", 0) .                // pad1 (1 byte)
                pack("n", 0) .                // transparentColor (2 bytes)
                pack("C", 1) .                // xAspect (1 byte)
                pack("C", 1) .                // yAspect (1 byte)
                pack("n", $this->width) .     // pageWidth (2 bytes)
                pack("n", $this->height);     // pageHeight (2 bytes)
        
        fwrite($fp, $data);
    }
    
    /**
     * Write CMAP (Color Map) chunk
     * 
     * The CMAP chunk contains RGB color values for the image palette.
     * Each color is stored as three bytes (red, green, blue) with values 0-255.
     * The total size must be padded to an even number of bytes.
     *
     * @param resource $fp File pointer
     */
    private function writeCMAPChunk($fp) {
        fwrite($fp, self::CMAP_CHUNK);
        
        $cmapSize = count($this->palette) * 3;  // 3 bytes per color (RGB)
        fwrite($fp, pack("N", $cmapSize));
        
        // Write each color's RGB values
        foreach ($this->palette as $color) {
            fwrite($fp, pack("C*", $color['r'], $color['g'], $color['b']));
        }
        
        // IFF chunks must be padded to even length
        if ($cmapSize % 2) {
            fwrite($fp, "\0");
        }
    }
    
    /**
     * Write BODY chunk containing bitmap data
     * 
     * The BODY chunk contains the actual image data in planar format.
     * Data is organized as interleaved rows from each bitplane:
     * - Row 0 from plane 0
     * - Row 0 from plane 1
     * - ...
     * - Row 0 from plane n
     * - Row 1 from plane 0
     * - etc.
     *
     * @param resource $fp File pointer
     * @param bool $compress Enable ByteRun1 compression
     * @throws Exception If writing fails
     */
    private function writeBODYChunk($fp, $compress) {
        $this->progress("Starting BODY chunk write...", 89);
        fwrite($fp, self::BODY_CHUNK);
        
        // Reserve space for chunk size
        $bodySizePos = ftell($fp);
        fwrite($fp, pack("N", 0));
        $bodyStart = ftell($fp);
        
        // Calculate row size with padding to word boundary
        $rowBytes = ceil($this->width / 8);
        $padding = $rowBytes % 2 ? 1 : 0;
        
        // Process all rows of all bitplanes
        $totalRows = $this->height * $this->bitplanes;
        $rowCount = 0;
        
        $this->progress(sprintf("Total rows to process: %d", $totalRows), 90);
        
        try {
            // Write interleaved bitplane rows
            for ($y = 0; $y < $this->height; $y++) {
                for ($plane = 0; $plane < $this->bitplanes; $plane++) {
                    $row = $this->imageData[$plane][$y];
                    
                    if ($compress) {
                        $this->progress(sprintf("Compressing row %d of plane %d", $y, $plane), 90);
                        $this->writeCompressedRow($fp, $row);
                    } else {
                        $this->progress(sprintf("Writing uncompressed row %d of plane %d", $y, $plane), 90);
                        fwrite($fp, pack("C*", ...$row));
                    }
                    
                    $rowCount++;
                    if ($rowCount % max(1, floor($totalRows / 20)) === 0) {
                        $percent = 90 + ($rowCount / $totalRows * 5);
                        $this->progress(sprintf("Processed %d of %d rows", $rowCount, $totalRows), $percent);
                    }
                }
            }
            
            // Update chunk size
            $bodySize = ftell($fp) - $bodyStart;
            $this->progress(sprintf("BODY chunk size: %d bytes", $bodySize), 95);
            
            fseek($fp, $bodySizePos);
            fwrite($fp, pack("N", $bodySize));
            fseek($fp, $bodyStart + $bodySize);
            
            // Pad chunk to even length if needed
            if ($bodySize % 2) {
                fwrite($fp, "\0");
                $this->progress("Added padding byte", 96);
            }
            
            $this->progress("BODY chunk complete", 97);
            
        } catch (Exception $e) {
            // Add debugging info to error message
            $this->progress(sprintf("Error at position y=%d, plane=%d", $y, $plane), 90);
            $this->progress("Row data: " . implode(',', array_map(function($b) { 
                return sprintf("%02X", $b); 
            }, $row)), 90);
            $this->progress("Error writing BODY chunk: " . $e->getMessage(), 90);
            throw $e;
        }
    }
    
    /**
     * Write a compressed row using ByteRun1 encoding
     * 
     * ByteRun1 is an RLE variant that supports both run-length encoding for
     * repeated bytes and literal sequences for non-repeating data.
     *
     * Format:
     * n >= 0: literal run of n+1 bytes follows
     * n < 0: repeat next byte (-n)+1 times
     * n = -128: no operation
     *
     * For efficiency:
     * - Use RLE for 3 or more repeated bytes
     * - Use literal runs for non-repeating bytes
     * - Maximum run length is 127 bytes
     * 
     * @param resource $fp File pointer
     * @param array $row Array of bytes to compress
     */
    private function writeCompressedRow($fp, $row) {
        $i = 0;
        $len = count($row);
        
        while ($i < $len) {
            // Handle last byte as a literal
            if ($i == $len - 1) {
                fwrite($fp, pack("C*", 0, $row[$i]));  // Literal run of 1 byte
                break;
            }
            
            // Find run of identical bytes
            $run = 1;
            while ($i + $run < $len && $row[$i] === $row[$i + $run] && $run < 127) {
                $run++;
            }
            
            // If run is 3 or more bytes, use RLE
            if ($run >= 3) {
                fwrite($fp, pack("C*", 257 - $run, $row[$i])); // 257-run = negative count
                $i += $run;
                continue;
            }
            
            // Otherwise find run of different bytes
            $different = 1;
            $startPos = $i;
            
            while ($i + $different < $len && 
                   ($i + $different == $len - 1 || 
                    $row[$i + $different] !== $row[$i + $different + 1] || 
                    ($i + $different + 2 < $len && 
                     $row[$i + $different + 1] !== $row[$i + $different + 2])) && 
                   $different < 127) {
                $different++;
            }
            
            // Write literal run
            fwrite($fp, pack("C", $different - 1)); // Count of bytes minus 1
            fwrite($fp, pack("C*", ...array_slice($row, $startPos, $different)));
            $i += $different;
        }
    }
}

// Example usage:
/*
$converter = new AmigaIFFConverter();
$converter->convertToIFF('input.jpg', 'output.iff', [
    'width' => 320,
    'height' => 200,
    'colors' => 32,
    'dither' => true,
    'compress' => true
]);
*/