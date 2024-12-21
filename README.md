# PHP JPEG/PNG to Amiga IFF ILBM Converter

Convert modern image formats (JPEG, PNG) to the classic Amiga IFF ILBM format. This tool provides a simple way to create IFF images compatible with Amiga computers and emulators.

## Quick Start

```bash
# Basic conversion with default settings
php iff_convertor.php input.jpg output.iff

# Specify size and colors (e.g., 320x200, 16 colors, no dither)
php iff_convertor.php input.jpg output.iff 320 200 16 false true

# Create hi-res PAL image (640x256, 4 colors)
php iff_convertor.php input.jpg output.iff 640 256 4 true true
```

## Features

- Convert JPEG/PNG to IFF ILBM
- Optional image resizing
- Configurable color palettes (2-256 colors)
- Floyd-Steinberg dithering (optional)
- ByteRun1 compression support
- Progress reporting during conversion

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/amiga-iff-converter.git
cd amiga-iff-converter
```

2. Requirements:
- PHP 7.4 or higher
- GD extension enabled
- Command line access

## Command Line Usage

```bash
php iff_convertor.php <input_image> <output_iff> [width] [height] [colors] [dither] [compress]
```

Parameters:
- `input_image`: JPEG or PNG file
- `output_iff`: Output IFF file name
- `width`: Target width (optional, default=source width)
- `height`: Target height (optional, default=source height)
- `colors`: Number of colors (2-256, must be power of 2, default=32)
- `dither`: Enable dithering (true/false, default=true)
- `compress`: Enable compression (true/false, default=true)

## Programming Interface

```php
require_once 'AmigaIFFConverter.php';

$converter = new AmigaIFFConverter();
$converter->convertToIFF('input.jpg', 'output.iff', [
    'width' => 320,
    'height' => 200,
    'colors' => 16,
    'dither' => false,
    'compress' => true
]);
```

## Recommended Settings

### Low Resolution (320x200/256)
Best for most Amiga games and demos:
```bash
php iff_convertor.php input.jpg output.iff 320 200 32 true true
```

### Hi-Resolution (640x200/256)
Good for Workbench and productivity software:
```bash
php iff_convertor.php input.jpg output.iff 640 256 16 true true
```

### Maximum Quality (32 colors)
Balances color depth with file size:
```bash
php iff_convertor.php input.jpg output.iff 320 200 32 false true
```

## Standard Amiga Resolutions

| Mode | Width | Height | Common Colors |
|------|--------|---------|--------------|
| NTSC Low-Res | 320 | 200 | 32 |
| PAL Low-Res | 320 | 256 | 32 |
| NTSC Hi-Res | 640 | 200 | 16 |
| PAL Hi-Res | 640 | 256 | 16 |
| Productivity | 640 | 480 | 4 |

## Technical Details

### IFF ILBM Format
The converter generates standard IFF ILBM files with:
- FORM container
- BMHD (Bitmap Header) chunk
- CMAP (Color Map) chunk
- BODY (Bitmap Data) chunk

### Planar Data
Amiga graphics use a planar format where each bit of a pixel's color index is stored in a separate bitplane. For example:
- 16 colors = 4 bitplanes
- 32 colors = 5 bitplanes
- 64 colors = 6 bitplanes

### ByteRun1 Compression
Optional RLE compression that supports:
- Run-length encoding for repeated bytes
- Literal runs for non-repeating data
- Efficient encoding for both patterns

## Limitations

- Input: Only JPEG and PNG supported
- Colors: Must be power of 2 (2, 4, 8, 16, 32, 64, 128, 256)
- No HAM (Hold-And-Modify) mode
- No EHB (Extra-Half-Brite) mode
- No support for masks or sprites
- Basic dithering only

## Compatibility Notes

- Best results with standard Amiga resolutions
- Files are compatible with:
  - Amiga OS and Workbench
  - DPaint and other Amiga art programs
  - Modern Amiga emulators
  - IFF/ILBM viewers

## Future Improvements

Planned features:
- HAM mode support
- EHB mode support
- Better color quantization
- Additional dithering methods
- More input formats

## Contributing

Contributions welcome! Please read CONTRIBUTING.md first.

## License

MIT License - see LICENSE file.

## Credits

- Based on the IFF ILBM format by Electronic Arts (1985)
- Uses PHP's GD library for image processing
