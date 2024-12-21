# PHP JPEG/PNG to Amiga IFF ILBM Converter

Convert modern image formats (JPEG, PNG) to the classic Amiga IFF ILBM format. This tool provides a simple way to create IFF images compatible with Amiga computers and emulators.

## Why?

Why not? This tool lets you create images for a legendary computer system, the Commodore Amiga, just because you can. While plenty of tools can view or open IFF/ILBM files, very few actually let you create them. 

Also, I was curious to see if it’s even possible to write such a tool in a language such as PHP. This project is a way to test those limits, relive some nostalgia, and celebrate the creative quirks of the Amiga graphics legacy.

### HAM Artifacts

The **[Hold-And-Modify (HAM)](https://en.wikipedia.org/wiki/Hold-And-Modify)** mode is iconic to the Amiga, but it comes with its quirks. HAM6 only allows 16 base colors and relies on modifying the previous pixel’s color to create a high-color effect. This can lead to noticeable color fringing, particularly along horizontal scanlines where sharp contrasts meet. 

For example, in images with large horizontal gradients or sharp color transitions—such as where a yellow car body meets black text—HAM may require intermediate pixels to approximate the desired color. The result is a "color fringing" artifact that is characteristic of HAM6's limitations.

I actually enjoy seeing these artifacts, especially on modern Retina-powered MacBooks. Yes, it’s nerdy and geeky but it is fascinating to see the creative constraints of a 1990s computer system juxtaposed with today’s high-resolution displays!

## Quick Start

```bash
# Basic conversion with default settings
php iff_convertor.php --input=input.jpg --output=output.iff

# Create a standard image with size and colors (e.g., 320x200, 16 colors, no dither)
php iff_convertor.php --input=input.jpg --output=output.iff --width=320 --height=200 --colors=16 --dither=false --compress=true

# Generate a HAM6 image (e.g., 320x256)
php iff_convertor.php --input=input.jpg --output=output_ham6.iff --width=320 --height=256 --ham=true --compress=true
```

## Features

- Convert JPEG/PNG to IFF ILBM
- Optional image resizing
- Configurable color palettes (2-256 colors)
- Hold-And-Modify (HAM6) support for classic Amiga chipsets
- ByteRun1 compression support
- Works with PAL and NTSC resolutions

## Installation

1. Clone the repository:
```bash
git clone https://github.com/rcoenen/AmigaIFFConverter.git
cd AmigaIFFConverter
```

2. Requirements:
- PHP 7.4 or higher
- GD extension enabled
- Command line access

## Command Line Usage

```bash
php iff_convertor.php --input=<input_image> --output=<output_iff> [--width=<width>] [--height=<height>] [--colors=<colors>] [--dither=<dither>] [--compress=<compress>] [--ham=<ham>]
```

Parameters:
- `--input`: JPEG or PNG file
- `--output`: Output IFF file name
- `--width`: Target width (optional, default=source width)
- `--height`: Target height (optional, default=source height)
- `--colors`: Number of colors (2-256, must be power of 2, default=32)
- `--dither`: Enable dithering (true/false, default=true)
- `--compress`: Enable compression (true/false, default=true)
- `--ham`: Enable HAM mode (true/false, default=false)

## Recommended Settings

### Standard Palette Images

- **Low Resolution (320x200/256):** Suitable for most Amiga games and demos.
  ```bash
  php iff_convertor.php --input=input.jpg --output=output.iff --width=320 --height=200 --colors=32 --dither=true --compress=true
  ```

- **Hi-Resolution (640x256):** Ideal for Workbench and productivity.
  ```bash
  php iff_convertor.php --input=input.jpg --output=output.iff --width=640 --height=256 --colors=16 --dither=true --compress=true
  ```

### HAM6 Images

HAM6 mode creates high-fidelity images using 16 base colors and Hold-And-Modify techniques.
```bash
php iff_convertor.php --input=input.jpg --output=output_ham6.iff --width=320 --height=256 --ham=true --compress=true
```

*Note: HAM6 (6-bitplanes) was introduced with the original Amiga chipset. Later AGA models introduced HAM8, but this tool currently supports only the classic HAM6 mode.*

## Standard Amiga Resolutions

| Mode           | Width | Height | Common Colors |
|----------------|-------|--------|---------------|
| NTSC Low-Res   | 320   | 200    | 32            |
| PAL Low-Res    | 320   | 256    | 32            |
| NTSC Hi-Res    | 640   | 200    | 16            |
| PAL Hi-Res     | 640   | 256    | 16            |
| Productivity   | 640   | 480    | 4             |

## Technical Details

### IFF ILBM Format
The converter generates standard IFF ILBM files with:
- FORM container
- BMHD (Bitmap Header) chunk
- CMAP (Color Map) chunk
- BODY (Bitmap Data) chunk

### HAM Mode
In HAM6 mode, images are rendered using 16 base colors. Each pixel can either:
1. Use a base color.
2. Modify one component (R, G, or B) of the previous pixel's color.

This creates a high-color effect while using only six bitplanes, a signature feature of classic Amiga chipsets.

### ByteRun1 Compression
Optional RLE compression that supports:
- Run-length encoding for repeated bytes.
- Literal runs for non-repeating data.
- Efficient encoding for both patterns.

## Limitations

- Input: Only JPEG and PNG supported.
- Colors: Must be power of 2 (2, 4, 8, 16, 32, 64, 128, 256).
- HAM mode: Currently supports only HAM6 (no HAM8 for AGA chipset).
- No EHB (Extra-Half-Brite) mode.
- No support for masks or sprites.

## Compatibility Notes

- Best results with standard Amiga resolutions.
- Files are compatible with:
  - Amiga OS and Workbench.
  - DPaint and other Amiga art programs.
  - Modern Amiga emulators.
  - IFF/ILBM viewers.

## Future Improvements

Planned features:
- HAM8 mode support (AGA chipset).
- EHB mode support.
- Improved color quantization.
- Additional dithering methods.

## Contributing

Contributions welcome! Please read CONTRIBUTING.md first.

## License

MIT License - see LICENSE file.

## Credits

- Based on the IFF ILBM format by Electronic Arts (1985).
- Uses PHP's GD library for image processing.
