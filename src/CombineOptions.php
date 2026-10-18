<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * Options for {@see GridCombiner::combine()}.
 *
 * Immutable: every `with*` method returns a new instance.
 */
final class CombineOptions
{
    public function __construct(
        /** Columns per row; `null` picks the most square-ish grid. */
        public readonly ?int $columns = null,
        /** Gap between neighbouring cells. */
        public readonly float $gap = 0.0,
        /** Padding around the outside of the grid. */
        public readonly float $padding = 0.0,
        /** How each source fills its cell. */
        public readonly FitMode $fit = FitMode::Cover,
        /** Pin the cell width instead of sizing cells from the sources. */
        public readonly ?float $cellWidth = null,
        /** Pin the cell height instead of sizing cells from the sources. */
        public readonly ?float $cellHeight = null,
        /** Cap for the finished image, used only when cells are auto-sized. */
        public readonly float $maxWidth = 2048.0,
        /** Colour the canvas starts out as; shows through gaps and letterboxing. */
        public readonly Rgba $background = new Rgba(0, 0, 0, 0),
    ) {
    }

    /**
     * Pin the number of columns per row.
     */
    public function withColumns(int $columns): self
    {
        return $this->copy(['columns' => $columns]);
    }

    /**
     * Set the gap between neighbouring cells.
     */
    public function withGap(float $gap): self
    {
        return $this->copy(['gap' => $gap]);
    }

    /**
     * Set the padding around the outside of the grid.
     */
    public function withPadding(float $padding): self
    {
        return $this->copy(['padding' => $padding]);
    }

    /**
     * Choose how each source fills its cell.
     */
    public function withFit(FitMode $fit): self
    {
        return $this->copy(['fit' => $fit]);
    }

    /**
     * Pin the cell size instead of deriving it from the sources.
     */
    public function withCellSize(float $width, float $height): self
    {
        return $this->copy(['cellWidth' => $width, 'cellHeight' => $height]);
    }

    /**
     * Set the colour behind every cell.
     */
    public function withBackground(Rgba $background): self
    {
        return $this->copy(['background' => $background]);
    }

    /**
     * Cap the finished image's width when cells are auto-sized.
     */
    public function withMaxWidth(float $maxWidth): self
    {
        return $this->copy(['maxWidth' => $maxWidth]);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function copy(array $changes): self
    {
        /** @var array{columns: ?int, gap: float, padding: float, fit: FitMode, cellWidth: ?float, cellHeight: ?float, maxWidth: float, background: Rgba} $current */
        $current = [
            'columns' => $this->columns,
            'gap' => $this->gap,
            'padding' => $this->padding,
            'fit' => $this->fit,
            'cellWidth' => $this->cellWidth,
            'cellHeight' => $this->cellHeight,
            'maxWidth' => $this->maxWidth,
            'background' => $this->background,
        ];

        return new self(...array_replace($current, $changes));
    }
}
