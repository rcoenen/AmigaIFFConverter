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

    public function writeIFFFile($outputFile, $bitplanes, $palette, $options)
    {
        $fp = fopen($outputFile, 'wb');
        if (! $fp) {
            throw new \Exception("Failed to create output file");
        }

        // Calculate correct number of bitplanes
        $numBitplanes = ceil(log($options['colors'], 2));

        $this->writeFORMHeader($fp);
        $this->writeBMHDChunk($fp, $options['width'], $options['height'], $numBitplanes, $options['compress']);
        $this->writeCMAPChunk($fp, $palette);
        $this->writeBODYChunk($fp, $bitplanes, $options['compress']);

                                    // Update FORM size
        $formSize = ftell($fp) - 8; // Subtract FORM header size
        fseek($fp, 4);              // Position after FORM identifier
        fwrite($fp, pack("N", $formSize));

        fclose($fp);
    }

    private function writeFORMHeader($fp)
    {
        fwrite($fp, self::FORM_HEADER);
        fwrite($fp, pack("N", 0)); // Placeholder for FORM size
        fwrite($fp, self::ILBM_TYPE);
    }

    private function writeBMHDChunk($fp, $width, $height, $numBitplanes, $compress)
    {
        fwrite($fp, self::BMHD_CHUNK);
        fwrite($fp, pack("N", 20)); // Chunk size

                                       // BitMapHeader structure
        $data = pack("n", $width) .    // w (2 bytes)
        pack("n", $height) .           // h (2 bytes)
        pack("n", 0) .                 // x (2 bytes)
        pack("n", 0) .                 // y (2 bytes)
        pack("C", $numBitplanes) .     // nPlanes (1 byte)
        pack("C", 0) .                 // masking (1 byte)
        pack("C", $compress ? 1 : 0) . // compression (1 byte)
        pack("C", 0) .                 // pad1 (1 byte)
        pack("n", 0) .                 // transparentColor (2 bytes)
        pack("C", 1) .                 // xAspect (1 byte)
        pack("C", 1) .                 // yAspect (1 byte)
        pack("n", $width) .            // pageWidth (2 bytes)
        pack("n", $height);            // pageHeight (2 bytes)

        fwrite($fp, $data);
    }

    private function writeCMAPChunk($fp, $palette)
    {
        fwrite($fp, self::CMAP_CHUNK);

        $cmapSize = count($palette) * 3; // 3 bytes per color (RGB)
        fwrite($fp, pack("N", $cmapSize));

        // Write each color's RGB values
        foreach ($palette as $color) {
            fwrite($fp, pack("C*", $color['r'], $color['g'], $color['b']));
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
        fwrite($fp, pack("N", 0));
        $bodyStart = ftell($fp);

        // Process one row at a time from all planes
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

        $bodySize = ftell($fp) - $bodyStart;
        fseek($fp, $bodySizePos);
        fwrite($fp, pack("N", $bodySize));
        fseek($fp, $bodyStart + $bodySize);

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
            // Handle last byte as a literal
            if ($i == $len - 1) {
                $output .= chr(0) . chr($row[$i]); // Change from fwrite to string concatenation
                break;
            }

            // Find run of identical bytes
            $run = 1;
            while ($i + $run < $len && $row[$i] === $row[$i + $run] && $run < 127) {
                $run++;
            }

            // If run is 3 or more bytes, use RLE
            if ($run >= 3) {
                $output .= chr(257 - $run) . chr($row[$i]); // 257-run = negative count
                $i += $run;
                continue;
            }

            // Otherwise find run of different bytes
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

            // Write literal run
            $output .= chr($different - 1) . implode('', array_map('chr', array_slice($row, $startPos, $different)));
            $i += $different;
        }

        return $output;
    }
}
