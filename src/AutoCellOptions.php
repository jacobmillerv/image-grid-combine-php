<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * Options for {@see Layout::autoCellSize()}.
 */
final class AutoCellOptions
{
    public function __construct(
        /** Columns per row; `null` picks the most square-ish grid. */
        public readonly ?int $columns = null,
        /** Gap between neighbouring cells. */
        public readonly float $gap = 0.0,
        /** Padding around the outside of the grid. */
        public readonly float $padding = 0.0,
        /** Cap for the finished image, in pixels. */
        public readonly float $maxWidth = 2048.0,
    ) {
    }

    public function withColumns(int $columns): self
    {
        return new self($columns, $this->gap, $this->padding, $this->maxWidth);
    }

    public function withGap(float $gap): self
    {
        return new self($this->columns, $gap, $this->padding, $this->maxWidth);
    }

    public function withPadding(float $padding): self
    {
        return new self($this->columns, $this->gap, $padding, $this->maxWidth);
    }

    public function withMaxWidth(float $maxWidth): self
    {
        return new self($this->columns, $this->gap, $this->padding, $maxWidth);
    }
}
