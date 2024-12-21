<?php

// file: src/FileWriter.php

namespace App\AmigaIFFConverter;

class FileWriter
{
    const FORM_HEADER = "FORM";
    const ILBM_TYPE   = "ILBM";
    const BMHD_CHUNK  = "BMHD";
    const CMAP_CHUNK  = "CMAP";
    const BODY_CHUNK  = "BODY";
    const CAMG_CHUNK  = "CAMG"; // Added CAMG constant

    public function writeIFFFile($outputFile, $bitplanes, $palette, array $options)
    {
        $fp = fopen($outputFile, 'wb');
        if (! $fp) {
            throw new \Exception("Failed to create output file");
        }

        // Calculate number of bitplanes (6 for HAM)
        $numBitplanes = $options['ham'] ? 6 : ceil(log(count($palette), 2));

        $this->writeFORMHeader($fp);
        $this->writeBMHDChunk($fp, $options['width'], $options['height'], $numBitplanes, $options['compress'], $options['ham']);

        if ($options['ham']) {
            $this->writeCAMGChunk($fp); // Write CAMG chunk for HAM mode
        }

        // Write the CMAP chunk, limiting to 16 colors for HAM
        $this->writeCMAPChunk($fp, $options['ham'] ? array_slice($palette, 0, 16) : $palette);

        $this->writeBODYChunk($fp, $bitplanes, $options['compress']);

        // Update FORM size
        $formSize = ftell($fp) - 8;
        fseek($fp, 4);
        fwrite($fp, pack("N", $formSize));

        fclose($fp);
    }

    private function writeCAMGChunk($fp)
    {
        fwrite($fp, self::CAMG_CHUNK);
        fwrite($fp, pack("N", 4)); // Chunk size is 4 bytes

                        // CAMG flags - set HAM mode (0x800) and LACE (0x4)
        $flags = 0x800; // HAM mode flag
        fwrite($fp, pack("N", $flags));
    }

    private function writeFORMHeader($fp)
    {
        fwrite($fp, self::FORM_HEADER);
        fwrite($fp, pack("N", 0)); // Placeholder for FORM size
        fwrite($fp, self::ILBM_TYPE);
    }

    private function writeBMHDChunk($fp, $width, $height, $numBitplanes, $compress, $ham)
    {
        fwrite($fp, self::BMHD_CHUNK);
        fwrite($fp, pack("N", 20)); // Chunk size

                                          // BitMapHeader structure
        $packedData = pack("n", $width) . // w (2 bytes)
        pack("n", $height) .              // h (2 bytes)
        pack("n", 0) .                    // x (2 bytes)
        pack("n", 0) .                    // y (2 bytes)
        pack("C", $numBitplanes) .        // nPlanes (1 byte)
        pack("C", 0) .                    // masking (1 byte)
        pack("C", $compress ? 1 : 0) .    // compression (1 byte)
        pack("C", 0) .                    // pad1 (1 byte)
        pack("n", 0) .                    // transparentColor (2 bytes)
        pack("C", 10) .                   // xAspect (1 byte)
        pack("C", 11) .                   // yAspect (1 byte)
        pack("n", $width) .               // pageWidth (2 bytes)
        pack("n", $height);               // pageHeight (2 bytes)

        fwrite($fp, $packedData);
    }

    private function writeCMAPChunk($fp, $palette, $chipset = 'ECS')
    {
        fwrite($fp, self::CMAP_CHUNK);
        $cmapSize = count($palette) * 3;
        fwrite($fp, pack("N", $cmapSize));

        foreach ($palette as $color) {
            if ($chipset === 'ECS') {
                                       // Scale RGB to 0-15 range for ECS compatibility
                $r = $color['r'] * 17; // Scale 0-15 to 0-255
                $g = $color['g'] * 17;
                $b = $color['b'] * 17;
            } elseif ($chipset === 'AGA') {
                // Use full 24-bit color values for AGA (future support)
                $r = $color['r'];
                $g = $color['g'];
                $b = $color['b'];
            }

            fwrite($fp, pack("C*", $r, $g, $b));
        }

        // IFF chunks must be padded to even length
        if ($cmapSize % 2) {
            fwrite($fp, "\0");
        }
    }

    private function writeBODYChunk($fp, $bitplanes, $compress)
    {
        fwrite($fp, self::BODY_CHUNK);

        $bodySizePos = ftell($fp);
        fwrite($fp, pack("N", 0)); // Placeholder for body size
        $bodyStart = ftell($fp);

        $height     = count($bitplanes[0]);
        $planeCount = count($bitplanes);

        for ($y = 0; $y < $height; $y++) {
            for ($plane = 0; $plane < $planeCount; $plane++) {
                $row = $bitplanes[$plane][$y];
                if ($compress) {
                    fwrite($fp, $this->compressRowByteRun1($row));
                } else {
                    fwrite($fp, pack("C*", ...$row));
                }
            }
        }

        // Update body size
        $bodySize = ftell($fp) - $bodyStart;
        fseek($fp, $bodySizePos);
        fwrite($fp, pack("N", $bodySize));
        fseek($fp, $bodyStart + $bodySize);

        // Pad to even length
        if ($bodySize % 2) {
            fwrite($fp, "\0");
        }
    }

    private function compressRowByteRun1($row)
    {
        $output = '';
        $len    = count($row);
        $i      = 0;

        while ($i < $len) {
            if ($i == $len - 1) {
                $output .= chr(0) . chr($row[$i]);
                break;
            }

            $run = 1;
            while ($i + $run < $len && $row[$i] === $row[$i + $run] && $run < 127) {
                $run++;
            }

            if ($run >= 3) {
                $output .= chr(257 - $run) . chr($row[$i]);
                $i += $run;
                continue;
            }

            $different = 1;
            $startPos  = $i;

            while ($i + $different < $len &&
                ($i + $different == $len - 1 ||
                    $row[$i + $different] !== $row[$i + $different + 1] ||
                    ($i + $different + 2 < $len &&
                        $row[$i + $different + 1] !== $row[$i + $different + 2])) &&
                $different < 127) {
                $different++;
            }

            $output .= chr($different - 1);
            $output .= implode('', array_map('chr', array_slice($row, $startPos, $different)));
            $i += $different;
        }

        return $output;
    }
}
