<?php

declare(strict_types=1);

namespace ImageGridCombine;

use InvalidArgumentException;

/**
 * An owned, straight-alpha RGBA8 pixel buffer.
 *
 * A canvas knows nothing about PNG, JPEG or any other container: decode first,
 * hand the pixels over, and this class does the rest. Pixels are stored one
 * packed `0xRRGGBBAA` integer each, which keeps a 4-megapixel image at roughly
 * the same footprint as the raw bytes while staying cheap to index.
 */
final class Canvas
{
    /**
     * @param list<int> $pixels
     */
    private function __construct(
        private readonly int $width,
        private readonly int $height,
        private array $pixels,
    ) {
        if ($width < 0 || $height < 0) {
            throw new InvalidArgumentException('Canvas width and height must not be negative.');
        }
    }

    /**
     * A fully transparent canvas of the given size.
     */
    public static function create(int $width, int $height): self
    {
        self::assertSize($width, $height);

        return new self($width, $height, array_fill(0, $width * $height, 0));
    }

    /**
     * A canvas of the given size, every pixel set to `$color`.
     */
    public static function filled(int $width, int $height, Rgba $color): self
    {
        self::assertSize($width, $height);

        return new self($width, $height, array_fill(0, $width * $height, $color->toPackedInt()));
    }

    /**
     * Wrap an existing list of packed `0xRRGGBBAA` pixels.
     *
     * @param list<int> $pixels
     */
    public static function fromPackedPixels(int $width, int $height, array $pixels): self
    {
        self::assertSize($width, $height);

        if (count($pixels) !== $width * $height) {
            throw new InvalidArgumentException(sprintf(
                'Expected %d pixels for a %dx%d canvas, got %d.',
                $width * $height,
                $width,
                $height,
                count($pixels),
            ));
        }

        return new self($width, $height, array_values($pixels));
    }

    /**
     * Wrap a packed RGBA8 byte string, four bytes per pixel.
     */
    public static function fromRgba8(int $width, int $height, string $bytes): self
    {
        self::assertSize($width, $height);

        if (strlen($bytes) !== $width * $height * 4) {
            throw new InvalidArgumentException(sprintf(
                'Expected %d bytes for a %dx%d canvas, got %d.',
                $width * $height * 4,
                $width,
                $height,
                strlen($bytes),
            ));
        }

        /** @var list<int> $pixels */
        $pixels = array_values(unpack('N*', $bytes) ?: []);

        return new self($width, $height, $pixels);
    }

    /**
     * Canvas width in pixels.
     */
    public function width(): int
    {
        return $this->width;
    }

    /**
     * Canvas height in pixels.
     */
    public function height(): int
    {
        return $this->height;
    }

    /**
     * Whether the canvas holds no pixels at all.
     */
    public function isEmpty(): bool
    {
        return $this->pixels === [];
    }

    /**
     * The raw pixels as packed `0xRRGGBBAA` integers, row-major from the top
     * left.
     *
     * @return list<int>
     */
    public function packedPixels(): array
    {
        return $this->pixels;
    }

    /**
     * The raw pixels as colours, row-major from the top left.
     *
     * @return list<Rgba>
     */
    public function pixels(): array
    {
        return array_map(Rgba::fromPackedInt(...), $this->pixels);
    }

    /**
     * The packed RGBA8 byte string, four bytes per pixel.
     */
    public function toRgba8(): string
    {
        return pack('N*', ...$this->pixels);
    }

    /**
     * The colour at `($x, $y)`, or `null` when the point is outside.
     */
    public function pixel(int $x, int $y): ?Rgba
    {
        if ($x < 0 || $y < 0 || $x >= $this->width || $y >= $this->height) {
            return null;
        }

        return Rgba::fromPackedInt($this->pixels[$y * $this->width + $x]);
    }

    /**
     * Overwrite the colour at `($x, $y)`. Out-of-bounds writes are ignored.
     */
    public function setPixel(int $x, int $y, Rgba $color): void
    {
        if ($x < 0 || $y < 0 || $x >= $this->width || $y >= $this->height) {
            return;
        }

        $this->pixels[$y * $this->width + $x] = $color->toPackedInt();
    }

    /**
     * Paint `$color` over every pixel inside `$rect`, clipped to the canvas.
     */
    public function fillRect(Rect $rect, Rgba $color): void
    {
        $packed = $color->toPackedInt();

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if (! $rect->contains($x + 0.5, $y + 0.5)) {
                    continue;
                }

                $index = $y * $this->width + $x;
                $this->pixels[$index] = Blend::over(
                    Rgba::fromPackedInt($this->pixels[$index]),
                    $color,
                )->toPackedInt();
            }
        }
    }

    /**
     * Draw `$source` into `$dest`, resampling with nearest-neighbour.
     *
     * `$fit` decides how the source fills the box; see {@see FitMode}. The
     * result is alpha-composited over whatever the canvas already holds, so
     * cells can overlap and translucent sources stay translucent.
     *
     * Sampling is nearest-neighbour on purpose: this library ships no
     * interpolation so that the same input always produces the same output.
     */
    public function draw(Canvas $source, Rect $dest, FitMode $fit): void
    {
        if ($source->isEmpty() || $this->isEmpty()) {
            return;
        }

        try {
            $mapping = Layout::fitRect(
                new SourceSize((float) $source->width, (float) $source->height),
                $dest->width,
                $dest->height,
                $fit,
            );
        } catch (LayoutError) {
            return;
        }

        $left = (int) max(0.0, floor($dest->x));
        $top = (int) max(0.0, floor($dest->y));
        $right = (int) min((float) $this->width, ceil($dest->right()));
        $bottom = (int) min((float) $this->height, ceil($dest->bottom()));
        if ($right <= $left || $bottom <= $top) {
            return;
        }

        $maxSourceX = (float) ($source->width - 1);
        $maxSourceY = (float) ($source->height - 1);
        $edge = 1e-9;

        for ($y = $top; $y < $bottom; $y++) {
            for ($x = $left; $x < $right; $x++) {
                // Where this pixel's centre falls inside the destination box.
                $boxX = $x + 0.5 - $dest->x;
                $boxY = $y + 0.5 - $dest->y;

                // Then where that lands inside the fitted region. Anything
                // outside is letterboxing and stays untouched.
                $relX = ($boxX - $mapping->dest->x) / $mapping->dest->width;
                $relY = ($boxY - $mapping->dest->y) / $mapping->dest->height;
                if ($relX < -$edge || $relX > 1.0 + $edge || $relY < -$edge || $relY > 1.0 + $edge) {
                    continue;
                }

                $sampleX = $mapping->source->x + max(0.0, min(1.0, $relX)) * $mapping->source->width;
                $sampleY = $mapping->source->y + max(0.0, min(1.0, $relY)) * $mapping->source->height;

                $sx = (int) max(0.0, min($maxSourceX, floor($sampleX)));
                $sy = (int) max(0.0, min($maxSourceY, floor($sampleY)));

                $src = $source->pixel($sx, $sy);
                if ($src === null || $src->isTransparent()) {
                    continue;
                }

                $index = $y * $this->width + $x;
                $this->pixels[$index] = Blend::over(
                    Rgba::fromPackedInt($this->pixels[$index]),
                    $src,
                )->toPackedInt();
            }
        }
    }

    private static function assertSize(int $width, int $height): void
    {
        if ($width < 0 || $height < 0) {
            throw new InvalidArgumentException('Canvas width and height must not be negative.');
        }
    }
}
