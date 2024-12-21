<?php

// File: src/HAMConverter.php

namespace App\AmigaIFFConverter;

class HAMConverter
{
    public function convertToHAM($image, $palette)
    {
        $width  = imagesx($image);
        $height = imagesy($image);

        // Initialize 6 bitplanes for HAM
        $bitplanes = $this->initializeBitplanes($width, $height, 6);

        // Process each scanline independently
        for ($y = 0; $y < $height; $y++) {
            $cr = 0;
            $cg = 0;
            $cb = 0; // Reset color components at start of each line

            // Process each pixel in the line
            for ($x = 0; $x < $width; $x++) {
                $pixel = imagecolorat($image, $x, $y);
                $rgb   = $this->getRGB($image, $pixel);

                // Calculate differences from previous color
                $dr = abs($cr - $rgb['r']);
                $dg = abs($cg - $rgb['g']);
                $db = abs($cb - $rgb['b']);

                $maxDiff = max($dr, $dg, $db);

                // Encode using component with largest difference
                if ($dr == $maxDiff) {
                    // Modify red (mode 2: 32 + value)
                    $value = 32 + $rgb['r'];
                    $cr    = $rgb['r'];
                } elseif ($dg == $maxDiff) {
                    // Modify green (mode 3: 48 + value)
                    $value = 48 + $rgb['g'];
                    $cg    = $rgb['g'];
                } else {
                    // Modify blue (mode 1: 16 + value)
                    $value = 16 + $rgb['b'];
                    $cb    = $rgb['b'];
                }

                // Convert HAM value to bitplanes
                $this->setHAMBits($bitplanes, $x, $y, $value);
            }
        }

        return $bitplanes;
    }

    private function getRGB($image, $pixel)
    {
        $rgb = imagecolorsforindex($image, $pixel);
        // Convert to 4-bit (0-15) range
        return [
            'r' => min(15, (int) ($rgb['red'] / 16)),
            'g' => min(15, (int) ($rgb['green'] / 16)),
            'b' => min(15, (int) ($rgb['blue'] / 16)),
        ];
    }

    private function setHAMBits(&$bitplanes, $x, $y, $value)
    {
        $byteOffset = intdiv($x, 8);
        $bitOffset  = 7 - ($x % 8);

        // Set all 6 bits according to HAM value
        for ($plane = 0; $plane < 6; $plane++) {
            $bit = ($value >> $plane) & 1;
            if ($bit) {
                $bitplanes[$plane][$y][$byteOffset] |= (1 << $bitOffset);
            }
        }
    }

    private function initializeBitplanes($width, $height, $planes)
    {
        $rowBytes  = ceil($width / 8);
        $padding   = $rowBytes % 2 ? 1 : 0;
        $bitplanes = [];

        for ($plane = 0; $plane < $planes; $plane++) {
            $bitplanes[$plane] = [];
            for ($y = 0; $y < $height; $y++) {
                $bitplanes[$plane][$y] = array_fill(0, $rowBytes + $padding, 0);
            }
        }

        return $bitplanes;
    }
}
