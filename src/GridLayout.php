<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * The geometry of a whole grid.
 */
final class GridLayout
{
    /**
     * @param list<Cell> $cells One entry per image, in input order.
     */
    public function __construct(
        /** Total width of the finished image. */
        public readonly float $width,
        /** Total height of the finished image. */
        public readonly float $height,
        /** Columns per row. */
        public readonly int $columns,
        /** Rows needed to hold every cell. */
        public readonly int $rows,
        /** Gap between neighbouring cells. */
        public readonly float $gap,
        /** Padding around the outside of the grid. */
        public readonly float $padding,
        public readonly array $cells,
    ) {
    }

    /**
     * The cell at `$index`, or `null` when the grid is not that large.
     */
    public function cell(int $index): ?Cell
    {
        return $this->cells[$index] ?? null;
    }

    /**
     * The last row is usually short, so its cells stop early.
     *
     * Returns how many cells the given row actually holds.
     */
    public function cellsInRow(int $row): int
    {
        if ($row >= $this->rows || $row < 0) {
            return 0;
        }

        return max(0, min($this->columns, count($this->cells) - $row * $this->columns));
    }
}
