<?php

declare(strict_types=1);

namespace ImageGridCombine\Tests;

use ImageGridCombine\Canvas;
use ImageGridCombine\CombineOptions;
use ImageGridCombine\FitMode;
use ImageGridCombine\GridCombiner;
use ImageGridCombine\LayoutError;
use ImageGridCombine\Rgba;
use PHPUnit\Framework\TestCase;

final class GridCombinerTest extends TestCase
{
    private static function red(): Rgba
    {
        return Rgba::rgb(255, 0, 0);
    }

    private static function blue(): Rgba
    {
        return Rgba::rgb(0, 0, 255);
    }

    public function testItLaysSourcesOutLeftToRight(): void
    {
        $grid = GridCombiner::combine([
            Canvas::filled(2, 2, self::red()),
            Canvas::filled(2, 2, self::blue()),
        ]);

        self::assertSame(4, $grid->width());
        self::assertSame(2, $grid->height());
        self::assertTrue($grid->pixel(0, 0)?->equals(self::red()) ?? false);
        self::assertTrue($grid->pixel(3, 1)?->equals(self::blue()) ?? false);
    }

    public function testItWrapsOntoNewRows(): void
    {
        $cell = Canvas::filled(2, 2, Rgba::white());

        $grid = GridCombiner::combine(
            [$cell, $cell, $cell, $cell],
            (new CombineOptions())->withColumns(2),
        );

        self::assertSame(4, $grid->width());
        self::assertSame(4, $grid->height());
        foreach ($grid->pixels() as $pixel) {
            self::assertTrue($pixel->equals(Rgba::white()));
        }
    }

    public function testItHonoursGapPaddingAndBackground(): void
    {
        $cell = Canvas::filled(2, 2, self::red());
        $options = (new CombineOptions())
            ->withColumns(2)
            ->withGap(2.0)
            ->withPadding(1.0)
            ->withBackground(Rgba::white());

        $grid = GridCombiner::combine([$cell, $cell, $cell, $cell], $options);

        // 1 + 2 + 2 + 2 + 1
        self::assertSame(8, $grid->width());
        self::assertSame(8, $grid->height());
        self::assertTrue($grid->pixel(0, 0)?->equals(Rgba::white()) ?? false);
        self::assertTrue($grid->pixel(1, 1)?->equals(self::red()) ?? false);
        self::assertTrue($grid->pixel(4, 4)?->equals(Rgba::white()) ?? false);
        self::assertTrue($grid->pixel(5, 5)?->equals(self::red()) ?? false);
    }

    public function testItUsesTheLargestSourceAsTheCell(): void
    {
        $grid = GridCombiner::combine(
            [Canvas::filled(2, 2, self::red()), Canvas::filled(4, 4, self::blue())],
            (new CombineOptions())->withColumns(2),
        );

        self::assertSame(8, $grid->width());
        self::assertSame(4, $grid->height());
    }

    public function testItCanPinTheCellSize(): void
    {
        $small = Canvas::filled(2, 2, self::red());

        $grid = GridCombiner::combine([$small, $small], (new CombineOptions())->withCellSize(3.0, 3.0));

        self::assertSame(6, $grid->width());
        self::assertSame(3, $grid->height());
    }

    public function testItCanLetterboxInsteadOfCropping(): void
    {
        $wide = Canvas::filled(4, 1, self::blue());

        $grid = GridCombiner::combine(
            [$wide],
            (new CombineOptions())
                ->withFit(FitMode::Contain)
                ->withCellSize(4.0, 4.0)
                ->withBackground(Rgba::white()),
        );

        self::assertSame(4, $grid->width());
        self::assertSame(4, $grid->height());
        self::assertTrue($grid->pixel(0, 0)?->equals(Rgba::white()) ?? false);
        self::assertTrue($grid->pixel(0, 1)?->equals(self::blue()) ?? false);
        self::assertTrue($grid->pixel(0, 2)?->equals(self::blue()) ?? false);
        self::assertTrue($grid->pixel(0, 3)?->equals(Rgba::white()) ?? false);
    }

    public function testItRejectsAnEmptyInput(): void
    {
        try {
            GridCombiner::combine([]);
            self::fail('Expected a LayoutError.');
        } catch (LayoutError $error) {
            self::assertSame(LayoutError::NO_SOURCES, $error->reason);
        }
    }

    public function testItRejectsABadCellSize(): void
    {
        $this->expectException(LayoutError::class);

        GridCombiner::combine(
            [Canvas::filled(2, 2, Rgba::white())],
            (new CombineOptions())->withCellSize(0.0, 0.0),
        );
    }
}
