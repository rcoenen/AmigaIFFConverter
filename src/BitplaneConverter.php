<?php

// file: src/BitplaneConverter.php

namespace App\AmigaIFFConverter;

class BitplaneConverter
{
    public function convertToBitplanes($image, array $palette)
    {
        $width     = imagesx($image);
        $height    = imagesy($image);
        $bitplanes = ceil(log(count($palette), 2));

        // Initialize bitplanes
        $bitplaneData = $this->initializeBitplanes($width, $height, $bitplanes);

        // Process image in rows
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $colorIndex = imagecolorat($image, $x, $y);
                $byteOffset = floor($x / 8);
                $bitOffset  = 7 - ($x % 8);

                // Distribute bits to the corresponding bitplanes
                for ($plane = 0; $plane < $bitplanes; $plane++) {
                    $bit = ($colorIndex >> $plane) & 1;
                    if ($bit) {
                        $bitplaneData[$plane][$y][$byteOffset] |= (1 << $bitOffset);
                    }
                }
            }

            // Free memory periodically for large images
            if ($y % 50 === 0) {
                gc_collect_cycles();
            }
        }

        return $bitplaneData;
    }

    private function initializeBitplanes($width, $height, $bitplanes)
    {
        $rowBytes     = ceil($width / 8);      // Bytes per row (each byte = 8 pixels)
        $padding      = $rowBytes % 2 ? 1 : 0; // Add padding if odd number of bytes
        $bitplaneData = [];

        for ($plane = 0; $plane < $bitplanes; $plane++) {
            $bitplaneData[$plane] = [];
            for ($y = 0; $y < $height; $y++) {
                $bitplaneData[$plane][$y] = array_fill(0, $rowBytes + $padding, 0);
            }
        }

        return $bitplaneData;
    }

}
