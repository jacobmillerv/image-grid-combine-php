<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * Puts the layout maths and the pixel buffer together.
 */
final class GridCombiner
{
    private function __construct()
    {
    }

    /**
     * Combine `$sources` into a single grid image, in input order.
     *
     * Cells are sized from the largest source unless
     * {@see CombineOptions::withCellSize()} pins them, so nothing is upscaled.
     * The grid is laid out row-major, and each source is drawn into its cell
     * with {@see CombineOptions::$fit}.
     *
     * @param list<Canvas> $sources
     *
     * @throws LayoutError
     */
    public static function combine(array $sources, ?CombineOptions $options = null): Canvas
    {
        if ($sources === []) {
            throw LayoutError::noSources();
        }

        $options ??= new CombineOptions();

        if ($options->cellWidth !== null && $options->cellHeight !== null) {
            $cell = new CellSize(
                $options->cellWidth,
                $options->cellHeight,
                $options->columns ?? Layout::defaultColumns(count($sources)),
            );
        } else {
            $sizes = [];
            foreach ($sources as $source) {
                $sizes[] = new SourceSize((float) $source->width(), (float) $source->height());
            }

            $cell = Layout::autoCellSize($sizes, new AutoCellOptions(
                $options->columns,
                $options->gap,
                $options->padding,
                $options->maxWidth,
            ));
        }

        $layout = Layout::computeGrid(
            GridOptions::of(count($sources), $cell->cellWidth, $cell->cellHeight)
                ->withColumns($cell->columns)
                ->withGap($options->gap)
                ->withPadding($options->padding),
        );

        $canvas = Canvas::filled(
            (int) max(0.0, ceil($layout->width)),
            (int) max(0.0, ceil($layout->height)),
            $options->background,
        );

        foreach ($sources as $index => $source) {
            $target = $layout->cell($index);
            if ($target === null) {
                continue;
            }

            $canvas->draw($source, $target->rect(), $options->fit);
        }

        return $canvas;
    }
}
