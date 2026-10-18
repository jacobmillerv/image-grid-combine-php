<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * The dimensions of one source image.
 */
final class SourceSize
{
    public function __construct(
        /** Source width in pixels. */
        public readonly float $width,
        /** Source height in pixels. */
        public readonly float $height,
    ) {
    }

    /**
     * A source of the given pixel dimensions.
     */
    public static function of(int $width, int $height): self
    {
        return new self((float) $width, (float) $height);
    }

    /**
     * Aspect ratio as `width / height`.
     */
    public function aspectRatio(): float
    {
        return $this->width / $this->height;
    }
}
