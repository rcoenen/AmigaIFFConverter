# PHP JPEG/PNG to Amiga IFF ILBM Converter

Convert modern image formats (JPEG, PNG) to the classic Amiga IFF ILBM format. This tool provides a simple way to create IFF images compatible with Amiga computers and emulators.

## Why?

Why not? This tool lets you create images for a legendary computer system, the Commodore Amiga. While plenty of tools can view IFF/ILBM files, very few actually let you create them.

Also, I was curious to see if it's even possible to write such a tool in PHP. This project is a way to test those limits, relive some nostalgia, and celebrate the creative quirks of the Amiga graphics legacy.

## Quick Start

```bash
# Basic conversion with default settings
php iff_convertor.php --input=input.jpg --output=output.iff

# Standard image (320x200, 16 colors, no dither)
php iff_convertor.php --input=input.jpg --output=output.iff --width=320 --height=200 --colors=16 --dither=false --compress=true

# HAM6 image (320x256)
php iff_convertor.php --input=input.jpg --output=output_ham6.iff --width=320 --height=256 --ham=true --compress=true

# ECS-compatible image (320x256, 32 colors, 12-bit RGB)
php iff_convertor.php --input=input.jpg --output=output_ecs.iff --width=320 --height=256 --colors=32 --chipset=ECS --dither=true --compress=true
```

## Features

- Convert JPEG/PNG to IFF ILBM
- Optional image resizing
- Configurable color palettes (2–256 colors)
- Hold-And-Modify (HAM6) support
- ECS chipset compatibility mode
- ByteRun1 compression support
- PAL and NTSC resolution support

## Understanding Different Modes

### Standard Mode
The IFF ILBM format is flexible and will accept colors from the full 24-bit RGB space (16.7 million colors). You can create technically valid IFF files that:
- Use up to 256 colors selected from 24-bit palette
- Use all 256 levels (0-255) for each RGB channel
- Are valid IFF ILBM files that modern software can read
- May not display correctly on actual Amiga hardware

### ECS Mode
When using `--chipset=ECS`, the converter enforces actual Amiga ECS hardware limitations:
- Colors are clamped to 12-bit RGB space (4096 colors total)
- Each color channel limited to 4-bit depth (16 levels, 0-15)
- Maximum 32 simultaneous colors in standard modes
- Guaranteed compatibility with Amiga ECS hardware and accurate emulators

```bash
# Example ECS-compatible image
php iff_convertor.php --input=input.jpg --output=output_ecs.iff --width=320 --height=256 --colors=32 --chipset=ECS --dither=true --compress=true
```

### HAM Mode
The Hold-And-Modify (HAM6) mode is iconic to the Amiga:
- Uses 16 base colors
- Modifies previous pixel's color for high-color effects
- Can show characteristic color fringing artifacts
- Especially visible in areas with sharp color transitions

## Technical Details

The converter generates standard IFF ILBM files containing:
- FORM container
- BMHD (Bitmap Header) chunk
- CMAP (Color Map) chunk
- BODY (Bitmap Data) chunk

### Recommended Resolutions
- PAL: 320×256
- NTSC: 320×200

### Notes
1. Generated images are tested on FS-UAE and WinUAE emulators
2. ECS mode files should display correctly on original hardware
3. Use standard Amiga resolutions for best results

## TODO
- Add AGA chipset support (24-bit RGB color space)
- Implement HAM8 mode for AGA chipsets
- Add support for halfbrite mode