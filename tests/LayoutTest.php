<?php

declare(strict_types=1);

namespace ImageGridCombine\Tests;

use ImageGridCombine\AutoCellOptions;
use ImageGridCombine\FitMode;
use ImageGridCombine\GridOptions;
use ImageGridCombine\Layout;
use ImageGridCombine\LayoutError;
use ImageGridCombine\SourceSize;
use PHPUnit\Framework\TestCase;

final class LayoutTest extends TestCase
{
    private static function assertClose(float $expected, float $actual, string $message = ''): void
    {
        self::assertLessThan(1e-9, abs($expected - $actual), $message);
    }

    public function testDefaultColumnsIsTheMostSquareGrid(): void
    {
        self::assertSame(1, Layout::defaultColumns(1));
        self::assertSame(2, Layout::defaultColumns(2));
        self::assertSame(2, Layout::defaultColumns(4));
        self::assertSame(3, Layout::defaultColumns(5));
        self::assertSame(3, Layout::defaultColumns(9));
        self::assertSame(4, Layout::defaultColumns(10));
        self::assertSame(0, Layout::defaultColumns(0));
    }

    public function testGridWithoutAColumnCountUsesTheSquareGrid(): void
    {
        $layout = Layout::computeGrid(GridOptions::of(4, 10.0, 10.0));

        self::assertSame(2, $layout->columns);
        self::assertSame(2, $layout->rows);
        self::assertSame(20.0, $layout->width);
        self::assertSame(20.0, $layout->height);
    }

    public function testCellsAreLaidOutRowMajor(): void
    {
        $layout = Layout::computeGrid(GridOptions::of(5, 10.0, 20.0)->withColumns(3));

        self::assertSame(2, $layout->rows);
        self::assertCount(5, $layout->cells);

        foreach ($layout->cells as $index => $cell) {
            self::assertSame($index, $cell->index);
            self::assertSame(10.0, $cell->width);
            self::assertSame(20.0, $cell->height);
        }

        self::assertClose(0.0, $layout->cells[0]->x);
        self::assertClose(0.0, $layout->cells[0]->y);
        self::assertClose(20.0, $layout->cells[2]->x);
        self::assertClose(0.0, $layout->cells[2]->y);
        self::assertClose(0.0, $layout->cells[3]->x);
        self::assertClose(20.0, $layout->cells[3]->y);
        self::assertClose(10.0, $layout->cells[4]->x);
        self::assertClose(20.0, $layout->cells[4]->y);
    }

    public function testGapAndPaddingAreCountedOnceBetweenCells(): void
    {
        $layout = Layout::computeGrid(
            GridOptions::of(4, 100.0, 50.0)->withColumns(2)->withGap(10.0)->withPadding(5.0),
        );

        // 5 + 100 + 10 + 100 + 5
        self::assertSame(220.0, $layout->width);
        // 5 + 50 + 10 + 50 + 5
        self::assertSame(120.0, $layout->height);
        self::assertClose(5.0, $layout->cells[0]->x);
        self::assertClose(115.0, $layout->cells[1]->x);
        self::assertClose(65.0, $layout->cells[2]->y);
    }

    public function testASingleRowHasNoTrailingGap(): void
    {
        $layout = Layout::computeGrid(GridOptions::of(3, 10.0, 10.0)->withColumns(3)->withGap(4.0));

        self::assertSame(1, $layout->rows);
        self::assertSame(38.0, $layout->width);
        self::assertSame(10.0, $layout->height);
    }

    public function testCellsInRowReportsTheShortLastRow(): void
    {
        $layout = Layout::computeGrid(GridOptions::of(5, 10.0, 10.0)->withColumns(3));

        self::assertSame(3, $layout->cellsInRow(0));
        self::assertSame(2, $layout->cellsInRow(1));
        self::assertSame(0, $layout->cellsInRow(2));
    }

    public function testGridRejectsNoCells(): void
    {
        $this->expectException(LayoutError::class);
        $this->expectExceptionMessage('count must be a positive integer');

        Layout::computeGrid(GridOptions::of(0, 10.0, 10.0));
    }

    public function testGridRejectsAZeroCellWidth(): void
    {
        try {
            Layout::computeGrid(GridOptions::of(1, 0.0, 10.0));
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NON_POSITIVE_CELL_SIZE, $error->reason);
        }
    }

    public function testGridRejectsANanCellHeight(): void
    {
        $this->expectException(LayoutError::class);

        Layout::computeGrid(GridOptions::of(1, 10.0, NAN));
    }

    public function testGridRejectsNegativeSpacing(): void
    {
        try {
            Layout::computeGrid(GridOptions::of(1, 10.0, 10.0)->withGap(-1.0));
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NEGATIVE_SPACING, $error->reason);
        }
    }

    public function testGridRejectsZeroColumns(): void
    {
        try {
            Layout::computeGrid(GridOptions::of(1, 10.0, 10.0)->withColumns(0));
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NO_COLUMNS, $error->reason);
        }
    }

    public function testCoverCropsTheLongEdgeAndFillsTheBox(): void
    {
        // A 4:1 source in a square box: the widest square crop is 100x100,
        // centred, so 150px is trimmed from each side.
        $fitted = Layout::fitRect(new SourceSize(400.0, 100.0), 50.0, 50.0, FitMode::Cover);

        self::assertClose(150.0, $fitted->source->x);
        self::assertClose(0.0, $fitted->source->y);
        self::assertClose(100.0, $fitted->source->width);
        self::assertClose(100.0, $fitted->source->height);
        self::assertClose(0.0, $fitted->dest->x);
        self::assertClose(0.0, $fitted->dest->y);
        self::assertClose(50.0, $fitted->dest->width);
        self::assertClose(50.0, $fitted->dest->height);
    }

    public function testCoverCropsTheShortEdgeWhenTheSourceIsTall(): void
    {
        // The mirror image: a 1:4 source keeps its width and crops the height.
        $fitted = Layout::fitRect(new SourceSize(100.0, 400.0), 50.0, 50.0, FitMode::Cover);

        self::assertClose(0.0, $fitted->source->x);
        self::assertClose(150.0, $fitted->source->y);
        self::assertClose(100.0, $fitted->source->width);
        self::assertClose(100.0, $fitted->source->height);
    }

    public function testCoverOnAMatchingRatioIsTheIdentity(): void
    {
        $fitted = Layout::fitRect(new SourceSize(200.0, 100.0), 40.0, 20.0, FitMode::Cover);

        self::assertClose(0.0, $fitted->source->x);
        self::assertClose(0.0, $fitted->source->y);
        self::assertClose(200.0, $fitted->source->width);
        self::assertClose(100.0, $fitted->source->height);
    }

    public function testContainLetterboxesWithoutCropping(): void
    {
        $fitted = Layout::fitRect(new SourceSize(400.0, 100.0), 50.0, 50.0, FitMode::Contain);

        self::assertClose(0.0, $fitted->source->x);
        self::assertClose(400.0, $fitted->source->width);
        self::assertClose(100.0, $fitted->source->height);
        self::assertClose(50.0, $fitted->dest->width);
        self::assertClose(12.5, $fitted->dest->height);
        self::assertClose(0.0, $fitted->dest->x);
        self::assertClose(18.75, $fitted->dest->y);
    }

    public function testStretchIgnoresTheAspectRatio(): void
    {
        $fitted = Layout::fitRect(new SourceSize(400.0, 100.0), 50.0, 50.0, FitMode::Stretch);

        self::assertClose(400.0, $fitted->source->width);
        self::assertClose(100.0, $fitted->source->height);
        self::assertClose(50.0, $fitted->dest->width);
        self::assertClose(50.0, $fitted->dest->height);
    }

    public function testFitRectRejectsADegenerateSource(): void
    {
        try {
            Layout::fitRect(new SourceSize(0.0, 10.0), 10.0, 10.0, FitMode::Cover);
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NON_POSITIVE_SOURCE, $error->reason);
        }
    }

    public function testFitRectRejectsADegenerateBox(): void
    {
        try {
            Layout::fitRect(new SourceSize(10.0, 10.0), 10.0, -1.0, FitMode::Cover);
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NON_POSITIVE_BOX, $error->reason);
        }
    }

    public function testAutoCellSizeNeverUpscales(): void
    {
        $sources = [new SourceSize(100.0, 50.0), new SourceSize(80.0, 90.0)];
        $cell = Layout::autoCellSize($sources, new AutoCellOptions());

        self::assertSame(2, $cell->columns);
        self::assertSame(100.0, $cell->cellWidth);
        self::assertSame(90.0, $cell->cellHeight);
    }

    public function testAutoCellSizeScalesDownPastTheWidthCap(): void
    {
        $sources = array_fill(0, 4, new SourceSize(1000.0, 500.0));
        $cell = Layout::autoCellSize($sources, new AutoCellOptions(maxWidth: 1024.0));

        self::assertSame(2, $cell->columns);
        self::assertSame(512.0, $cell->cellWidth);
        self::assertSame(256.0, $cell->cellHeight);
    }

    public function testAutoCellSizeAccountsForGapAndPadding(): void
    {
        $sources = array_fill(0, 4, new SourceSize(100.0, 100.0));
        $cell = Layout::autoCellSize($sources, new AutoCellOptions(gap: 10.0, padding: 5.0, maxWidth: 220.0));

        self::assertSame(100.0, $cell->cellWidth);
        self::assertSame(100.0, $cell->cellHeight);
    }

    public function testAutoCellSizeNeverReturnsAZeroCell(): void
    {
        $sources = array_fill(0, 9, new SourceSize(1000.0, 1000.0));
        $cell = Layout::autoCellSize($sources, new AutoCellOptions(maxWidth: 1.0));

        self::assertGreaterThanOrEqual(1.0, $cell->cellWidth);
        self::assertGreaterThanOrEqual(1.0, $cell->cellHeight);
    }

    public function testAutoCellSizeRejectsEmptyInput(): void
    {
        try {
            Layout::autoCellSize([], new AutoCellOptions());
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NO_SOURCES, $error->reason);
        }
    }

    public function testSourceSizeReportsItsAspectRatio(): void
    {
        self::assertSame(2.0, (new SourceSize(200.0, 100.0))->aspectRatio());
        self::assertSame(100.0, SourceSize::of(100, 50)->width);
    }

    public function testRectContainsItsEdges(): void
    {
        $rect = new \ImageGridCombine\Rect(1.0, 2.0, 3.0, 4.0);

        self::assertSame(4.0, $rect->right());
        self::assertSame(6.0, $rect->bottom());
        self::assertTrue($rect->contains(1.0, 2.0));
        self::assertTrue($rect->contains(4.0, 6.0));
        self::assertFalse($rect->contains(4.5, 6.0));
    }
}
