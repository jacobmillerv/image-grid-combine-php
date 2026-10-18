<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * The natural size of one cell, as returned by {@see Layout::autoCellSize()}.
 */
final class CellSize
{
    public function __construct(
        /** Width every cell gets. */
        public readonly float $cellWidth,
        /** Height every cell gets. */
        public readonly float $cellHeight,
        /** Columns per row that the size was computed for. */
        public readonly int $columns,
    ) {
    }
}
