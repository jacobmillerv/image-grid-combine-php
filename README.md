# image-grid-combine

Combine several images into one grid image: grid layout maths plus RGBA
compositing, with no dependencies.

```bash
composer require lumfuse/image-grid-combine
```

It started life as the collage step of https://aiimagecombiner.app and is
published here so the same code can be reused anywhere. It does two things, and
keeps them apart:

- **layout** — how many columns a set of images wants, how big each cell should
  be, and how a source rectangle maps into its destination box. Pure arithmetic
  on floats, no buffers involved.
- **canvas** — an owned RGBA8 buffer, alpha compositing, and
  `GridCombiner::combine()`, which puts the two halves together.

It does not decode or encode image files, and it has no extensions to install.
Decode your inputs however you like — GD, Imagick, a pure-PHP decoder — hand the
pixels over, and write the result out with whatever encoder you already have.

## Quick start

```php
use ImageGridCombine\Canvas;
use ImageGridCombine\GridCombiner;
use ImageGridCombine\Rgba;

// Whatever your decoder gives you: two 2x2 tiles, red and blue.
$red = Canvas::filled(2, 2, Rgba::rgb(255, 0, 0));
$blue = Canvas::filled(2, 2, Rgba::rgb(0, 0, 255));

$grid = GridCombiner::combine([$red, $blue]);

$grid->width();                       // 4
$grid->height();                      // 2
$grid->pixel(0, 0)->equals($red);     // true
$grid->pixel(3, 1)->equals($blue);    // true
```

`GridCombiner::combine()` picks the most square-ish grid by default, sizes every
cell from the largest source so nothing is upscaled, and lays the images out
row-major.

## Choosing the shape

`CombineOptions` is immutable, so every `with*` method hands back a new one:

```php
use ImageGridCombine\Canvas;
use ImageGridCombine\CombineOptions;
use ImageGridCombine\FitMode;
use ImageGridCombine\GridCombiner;
use ImageGridCombine\Rgba;

$images = array_map(
    static fn (): Canvas => Canvas::filled(64, 48, Rgba::white()),
    range(1, 5),
);

$options = (new CombineOptions())
    ->withColumns(3)
    ->withGap(8.0)
    ->withPadding(16.0)
    ->withFit(FitMode::Contain)
    ->withBackground(Rgba::white());

$grid = GridCombiner::combine($images, $options);
```

The three fit modes:

| Mode              | Aspect ratio | What happens to the overflow |
| ----------------- | ------------ | ---------------------------- |
| `FitMode::Cover`  | kept         | cropped, box is filled       |
| `FitMode::Contain`| kept         | letterboxed over the background |
| `FitMode::Stretch`| ignored      | squashed to fit              |

## Reading and writing pixels

A `Canvas` is a straight-alpha RGBA8 buffer, row-major from the top left:

```php
use ImageGridCombine\Canvas;
use ImageGridCombine\Rgba;

$canvas = Canvas::fromRgba8(2, 2, $bytesFromYourDecoder);

$canvas->pixel(1, 0);                        // ?Rgba, null when out of bounds
$canvas->setPixel(1, 0, Rgba::white());
$canvas->toRgba8();                          // packed bytes, 4 per pixel
$canvas->packedPixels();                     // 0xRRGGBBAA integers
```

Out-of-bounds reads return `null` and out-of-bounds writes are ignored, so you
can blit from a computed layout without clamping first.

## Layout only

If you have your own compositing already, skip the buffer entirely:

```php
use ImageGridCombine\GridOptions;
use ImageGridCombine\Layout;

$layout = Layout::computeGrid(GridOptions::of(4, 100.0, 80.0)->withGap(10.0));

$layout->columns;        // 2
$layout->rows;           // 2
$layout->cell(3)->x;     // 110.0
```

## Rounding

Layouts are computed in floats, so fractional cell sizes survive a downscale
instead of drifting. Buffers are whole pixels: `GridCombiner::combine()` rounds
the grid size up, and drawing samples with nearest-neighbour, so the same input
always produces the same output.

## Errors

Layout problems throw `LayoutError`, an `InvalidArgumentException` carrying a
stable `reason` code:

```php
use ImageGridCombine\GridCombiner;
use ImageGridCombine\LayoutError;

try {
    GridCombiner::combine([]);
} catch (LayoutError $error) {
    $error->reason === LayoutError::NO_SOURCES;  // true
}
```

## Requirements

PHP 8.1 or newer. No extensions, no runtime dependencies.

## Tests

```bash
composer install
composer test
```

## License

MIT.
