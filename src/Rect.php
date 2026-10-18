<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * A rectangle in continuous coordinates.
 *
 * Layouts are computed in floats so that fractional cell sizes survive a
 * downscale; round to whole pixels only when a buffer is allocated.
 */
final class Rect
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $width,
        public readonly float $height,
    ) {
    }

    /**
     * The x coordinate just past the right edge.
     */
    public function right(): float
    {
        return $this->x + $this->width;
    }

    /**
     * The y coordinate just past the bottom edge.
     */
    public function bottom(): float
    {
        return $this->y + $this->height;
    }

    /**
     * Whether a point falls inside the rectangle, edges included.
     */
    public function contains(float $x, float $y): bool
    {
        return $x >= $this->x && $x <= $this->right()
            && $y >= $this->y && $y <= $this->bottom();
    }

    public function equals(self $other): bool
    {
        return $this->x === $other->x
            && $this->y === $other->y
            && $this->width === $other->width
            && $this->height === $other->height;
    }
}
