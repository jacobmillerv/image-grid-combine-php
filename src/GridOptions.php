<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * Inputs for {@see Layout::computeGrid()}.
 */
final class GridOptions
{
    public function __construct(
        /** How many cells to place. Must be at least one. */
        public readonly int $count,
        /** Width of a single cell. */
        public readonly float $cellWidth,
        /** Height of a single cell. */
        public readonly float $cellHeight,
        /** Columns per row; `null` picks the most square-ish grid. */
        public readonly ?int $columns = null,
        /** Gap between neighbouring cells. */
        public readonly float $gap = 0.0,
        /** Padding around the outside of the grid. */
        public readonly float $padding = 0.0,
    ) {
    }

    /**
     * Options for `$count` cells of the given size, with no gap or padding.
     */
    public static function of(int $count, float $cellWidth, float $cellHeight): self
    {
        return new self($count, $cellWidth, $cellHeight);
    }

    /**
     * Pin the number of columns per row.
     */
    public function withColumns(int $columns): self
    {
        return new self($this->count, $this->cellWidth, $this->cellHeight, $columns, $this->gap, $this->padding);
    }

    /**
     * Set the gap between neighbouring cells.
     */
    public function withGap(float $gap): self
    {
        return new self($this->count, $this->cellWidth, $this->cellHeight, $this->columns, $gap, $this->padding);
    }

    /**
     * Set the padding around the outside of the grid.
     */
    public function withPadding(float $padding): self
    {
        return new self($this->count, $this->cellWidth, $this->cellHeight, $this->columns, $this->gap, $padding);
    }
}
