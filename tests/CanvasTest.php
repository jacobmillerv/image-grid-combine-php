<?php

declare(strict_types=1);

namespace ImageGridCombine\Tests;

use ImageGridCombine\Canvas;
use ImageGridCombine\FitMode;
use ImageGridCombine\Rect;
use ImageGridCombine\Rgba;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CanvasTest extends TestCase
{
    private static function red(): Rgba
    {
        return Rgba::rgb(255, 0, 0);
    }

    private static function blue(): Rgba
    {
        return Rgba::rgb(0, 0, 255);
    }

    public function testANewCanvasStartsTransparent(): void
    {
        $canvas = Canvas::create(3, 2);

        self::assertSame(3, $canvas->width());
        self::assertSame(2, $canvas->height());
        self::assertFalse($canvas->isEmpty());
        foreach ($canvas->pixels() as $pixel) {
            self::assertTrue($pixel->isTransparent());
        }
    }

    public function testAZeroSizedCanvasIsEmpty(): void
    {
        self::assertTrue(Canvas::create(0, 0)->isEmpty());
    }

    public function testPixelsAreIndexedRowMajor(): void
    {
        $canvas = Canvas::create(3, 2);
        $canvas->setPixel(2, 1, Rgba::white());

        self::assertTrue($canvas->pixel(2, 1)?->equals(Rgba::white()) ?? false);
        self::assertTrue($canvas->pixels()[5]->equals(Rgba::white()));
    }

    public function testOutOfBoundsAccessIsIgnored(): void
    {
        $canvas = Canvas::create(2, 2);
        $canvas->setPixel(9, 9, Rgba::white());

        self::assertNull($canvas->pixel(9, 9));
        self::assertNull($canvas->pixel(-1, 0));
        foreach ($canvas->pixels() as $pixel) {
            self::assertTrue($pixel->isTransparent());
        }
    }

    public function testRgba8RoundTrips(): void
    {
        $canvas = Canvas::fromRgba8(1, 1, "\x01\x02\x03\x04");

        self::assertTrue($canvas->pixel(0, 0)?->equals(Rgba::rgba(1, 2, 3, 4)) ?? false);
        self::assertSame("\x01\x02\x03\x04", $canvas->toRgba8());
    }

    public function testRgba8RoundTripsForALargerCanvas(): void
    {
        $canvas = Canvas::filled(2, 3, Rgba::rgba(9, 8, 7, 6));

        self::assertSame(str_repeat("\x09\x08\x07\x06", 6), $canvas->toRgba8());
        self::assertTrue(Canvas::fromRgba8(2, 3, $canvas->toRgba8())->pixel(1, 2)?->equals(Rgba::rgba(9, 8, 7, 6)) ?? false);
    }

    public function testRgba8RejectsAShortBuffer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Canvas::fromRgba8(2, 2, "\x00\x00\x00\x00");
    }

    public function testFromPackedPixelsRejectsTheWrongCount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Canvas::fromPackedPixels(2, 2, [0]);
    }

    public function testNegativeSizesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Canvas::create(-1, 4);
    }

    public function testDrawingStretchFillsTheWholeBox(): void
    {
        $canvas = Canvas::filled(4, 4, Rgba::white());
        $canvas->draw(Canvas::filled(1, 1, self::red()), new Rect(0.0, 0.0, 4.0, 4.0), FitMode::Stretch);

        foreach ($canvas->pixels() as $pixel) {
            self::assertTrue($pixel->equals(self::red()));
        }
    }

    public function testDrawingOnlyTouchesTheDestinationBox(): void
    {
        $canvas = Canvas::filled(4, 4, Rgba::white());
        $canvas->draw(Canvas::filled(2, 2, self::red()), new Rect(2.0, 0.0, 2.0, 2.0), FitMode::Cover);

        self::assertTrue($canvas->pixel(0, 0)?->equals(Rgba::white()) ?? false);
        self::assertTrue($canvas->pixel(1, 1)?->equals(Rgba::white()) ?? false);
        self::assertTrue($canvas->pixel(2, 0)?->equals(self::red()) ?? false);
        self::assertTrue($canvas->pixel(3, 1)?->equals(self::red()) ?? false);
        self::assertTrue($canvas->pixel(2, 2)?->equals(Rgba::white()) ?? false);
    }

    public function testContainLetterboxesAndLeavesTheBarsAlone(): void
    {
        // A wide source in a square box: the bars above and below stay clear.
        $canvas = Canvas::filled(4, 4, Rgba::white());
        $canvas->draw(Canvas::filled(4, 2, self::blue()), new Rect(0.0, 0.0, 4.0, 4.0), FitMode::Contain);

        self::assertTrue($canvas->pixel(0, 0)?->equals(Rgba::white()) ?? false);
        self::assertTrue($canvas->pixel(0, 1)?->equals(self::blue()) ?? false);
        self::assertTrue($canvas->pixel(0, 2)?->equals(self::blue()) ?? false);
        self::assertTrue($canvas->pixel(3, 2)?->equals(self::blue()) ?? false);
        self::assertTrue($canvas->pixel(0, 3)?->equals(Rgba::white()) ?? false);
    }

    public function testCoverCropsInsteadOfLetterboxing(): void
    {
        $canvas = Canvas::filled(2, 2, Rgba::white());
        $canvas->draw(Canvas::filled(4, 1, self::blue()), new Rect(0.0, 0.0, 2.0, 2.0), FitMode::Cover);

        foreach ($canvas->pixels() as $pixel) {
            self::assertTrue($pixel->equals(self::blue()));
        }
    }

    public function testDrawingClipsAtTheCanvasEdge(): void
    {
        $canvas = Canvas::filled(2, 2, Rgba::white());
        $canvas->draw(Canvas::filled(4, 4, self::red()), new Rect(1.0, 1.0, 4.0, 4.0), FitMode::Cover);

        self::assertTrue($canvas->pixel(0, 0)?->equals(Rgba::white()) ?? false);
        self::assertTrue($canvas->pixel(1, 1)?->equals(self::red()) ?? false);
    }

    public function testDrawingATransparentSourceChangesNothing(): void
    {
        $canvas = Canvas::filled(4, 4, Rgba::white());
        $canvas->draw(Canvas::create(4, 4), new Rect(0.0, 0.0, 4.0, 4.0), FitMode::Cover);

        foreach ($canvas->pixels() as $pixel) {
            self::assertTrue($pixel->equals(Rgba::white()));
        }
    }

    public function testDrawingADegenerateBoxChangesNothing(): void
    {
        $canvas = Canvas::filled(4, 4, Rgba::white());
        $canvas->draw(Canvas::filled(4, 4, self::red()), new Rect(0.0, 0.0, 0.0, 4.0), FitMode::Cover);

        foreach ($canvas->pixels() as $pixel) {
            self::assertTrue($pixel->equals(Rgba::white()));
        }
    }

    public function testFillRectPaintsOnlyTheRectangle(): void
    {
        $canvas = Canvas::filled(4, 4, Rgba::white());
        $canvas->fillRect(new Rect(1.0, 1.0, 2.0, 2.0), self::red());

        self::assertTrue($canvas->pixel(0, 0)?->equals(Rgba::white()) ?? false);
        self::assertTrue($canvas->pixel(1, 1)?->equals(self::red()) ?? false);
        self::assertTrue($canvas->pixel(2, 2)?->equals(self::red()) ?? false);
        self::assertTrue($canvas->pixel(3, 3)?->equals(Rgba::white()) ?? false);
    }

    public function testFillRectCompositesTranslucentColours(): void
    {
        $canvas = Canvas::filled(1, 1, Rgba::white());
        $canvas->fillRect(new Rect(0.0, 0.0, 1.0, 1.0), Rgba::rgba(255, 0, 0, 128));

        self::assertTrue($canvas->pixel(0, 0)?->equals(Rgba::rgb(255, 127, 127)) ?? false);
    }
}
