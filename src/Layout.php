<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * Pure layout maths.
 *
 * Nothing here allocates pixels, decodes an image or touches the filesystem.
 * It only turns sizes into coordinates, which keeps it cheap to unit test.
 */
final class Layout
{
    private function __construct()
    {
    }

    /**
     * Work out how many columns to use when the caller does not say.
     *
     * Produces the most square-ish grid that still fits every image.
     */
    public static function defaultColumns(int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        return (int) ceil(sqrt((float) $count));
    }

    /**
     * Compute the pixel geometry of a grid.
     *
     * @throws LayoutError
     */
    public static function computeGrid(GridOptions $options): GridLayout
    {
        if ($options->count <= 0) {
            throw LayoutError::noCells();
        }
        if (!($options->cellWidth > 0.0) || !($options->cellHeight > 0.0)) {
            throw LayoutError::nonPositiveCellSize();
        }
        if (!($options->gap >= 0.0) || !($options->padding >= 0.0)) {
            throw LayoutError::negativeSpacing();
        }

        $columns = $options->columns ?? self::defaultColumns($options->count);
        if ($columns <= 0) {
            throw LayoutError::noColumns();
        }

        $rows = intdiv($options->count + $columns - 1, $columns);
        $cells = [];

        for ($index = 0; $index < $options->count; $index++) {
            $row = intdiv($index, $columns);
            $column = $index % $columns;
            $cells[] = new Cell(
                $index,
                $options->padding + $column * ($options->cellWidth + $options->gap),
                $options->padding + $row * ($options->cellHeight + $options->gap),
                $options->cellWidth,
                $options->cellHeight,
            );
        }

        $columnsF = (float) $columns;
        $rowsF = (float) $rows;

        return new GridLayout(
            $options->padding * 2.0 + $columnsF * $options->cellWidth + ($columnsF - 1.0) * $options->gap,
            $options->padding * 2.0 + $rowsF * $options->cellHeight + ($rowsF - 1.0) * $options->gap,
            $columns,
            $rows,
            $options->gap,
            $options->padding,
            $cells,
        );
    }

    /**
     * Fit a source rectangle into a destination box.
     *
     * * {@see FitMode::Cover} fills the box, cropping the overflow, and keeps
     *   the aspect ratio.
     * * {@see FitMode::Contain} fits inside the box, letterboxing, and keeps
     *   the aspect ratio.
     * * {@see FitMode::Stretch} fills the box exactly and ignores the aspect
     *   ratio.
     *
     * @throws LayoutError
     */
    public static function fitRect(
        SourceSize $source,
        float $boxWidth,
        float $boxHeight,
        FitMode $fit,
    ): FitRect {
        if (!($source->width > 0.0) || !($source->height > 0.0)) {
            throw LayoutError::nonPositiveSource();
        }
        if (!($boxWidth > 0.0) || !($boxHeight > 0.0)) {
            throw LayoutError::nonPositiveBox();
        }

        if ($fit === FitMode::Stretch) {
            return new FitRect(
                new Rect(0.0, 0.0, $source->width, $source->height),
                new Rect(0.0, 0.0, $boxWidth, $boxHeight),
            );
        }

        $sourceRatio = $source->aspectRatio();
        $boxRatio = $boxWidth / $boxHeight;

        if ($fit === FitMode::Cover) {
            // Crop the source to the box's aspect ratio, then fill the box with it.
            $wider = $sourceRatio > $boxRatio;
            $sourceWidth = $wider ? $source->height * $boxRatio : $source->width;
            $sourceHeight = $wider ? $source->height : $source->width / $boxRatio;

            return new FitRect(
                new Rect(
                    ($source->width - $sourceWidth) / 2.0,
                    ($source->height - $sourceHeight) / 2.0,
                    $sourceWidth,
                    $sourceHeight,
                ),
                new Rect(0.0, 0.0, $boxWidth, $boxHeight),
            );
        }

        $scale = min($boxWidth / $source->width, $boxHeight / $source->height);
        $destWidth = $source->width * $scale;
        $destHeight = $source->height * $scale;

        return new FitRect(
            new Rect(0.0, 0.0, $source->width, $source->height),
            new Rect(
                ($boxWidth - $destWidth) / 2.0,
                ($boxHeight - $destHeight) / 2.0,
                $destWidth,
                $destHeight,
            ),
        );
    }

    /**
     * Largest cell size that keeps every source's aspect ratio inside a target
     * width, used when the caller does not pin an explicit cell size.
     *
     * Cells default to the largest source, so nothing is ever upscaled; only a
     * grid wider than `maxWidth` is scaled down.
     *
     * @param list<SourceSize> $sources
     *
     * @throws LayoutError
     */
    public static function autoCellSize(array $sources, AutoCellOptions $options): CellSize
    {
        if ($sources === []) {
            throw LayoutError::noSources();
        }
        if (!($options->gap >= 0.0) || !($options->padding >= 0.0)) {
            throw LayoutError::negativeSpacing();
        }

        $columns = $options->columns ?? self::defaultColumns(count($sources));
        if ($columns <= 0) {
            throw LayoutError::noColumns();
        }

        $cellWidth = 0.0;
        $cellHeight = 0.0;
        foreach ($sources as $source) {
            $cellWidth = max($cellWidth, $source->width);
            $cellHeight = max($cellHeight, $source->height);
        }

        $columnsF = (float) $columns;
        $totalWidth = $options->padding * 2.0 + $columnsF * $cellWidth + ($columnsF - 1.0) * $options->gap;

        if ($totalWidth <= $options->maxWidth) {
            return new CellSize($cellWidth, $cellHeight, $columns);
        }

        $usable = $options->maxWidth - $options->padding * 2.0 - ($columnsF - 1.0) * $options->gap;
        $scale = $usable / ($columnsF * $cellWidth);

        return new CellSize(
            max(1.0, floor($cellWidth * $scale)),
            max(1.0, floor($cellHeight * $scale)),
            $columns,
        );
    }
}
