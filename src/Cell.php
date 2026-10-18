<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * One cell of a computed grid.
 */
final class Cell
{
    public function __construct(
        /** Zero-based position of this cell in the input order. */
        public readonly int $index,
        /** Left edge, including padding and the gaps before it. */
        public readonly float $x,
        /** Top edge, including padding and the gaps before it. */
        public readonly float $y,
        /** Cell width, identical for every cell in the grid. */
        public readonly float $width,
        /** Cell height, identical for every cell in the grid. */
        public readonly float $height,
    ) {
    }

    /**
     * The cell as a {@see Rect}.
     */
    public function rect(): Rect
    {
        return new Rect($this->x, $this->y, $this->width, $this->height);
    }
}
