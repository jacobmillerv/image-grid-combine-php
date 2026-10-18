<?php

declare(strict_types=1);

namespace ImageGridCombine\Tests;

use ImageGridCombine\Rgba;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RgbaTest extends TestCase
{
    public function testRgbIsOpaque(): void
    {
        $colour = Rgba::rgb(1, 2, 3);

        self::assertSame(1, $colour->r);
        self::assertSame(2, $colour->g);
        self::assertSame(3, $colour->b);
        self::assertSame(255, $colour->a);
    }

    public function testNamedColours(): void
    {
        self::assertTrue(Rgba::transparent()->isTransparent());
        self::assertTrue(Rgba::black()->equals(Rgba::rgba(0, 0, 0, 255)));
        self::assertTrue(Rgba::white()->equals(Rgba::rgb(255, 255, 255)));
    }

    public function testWithAlphaKeepsTheChannels(): void
    {
        $colour = Rgba::white()->withAlpha(128);

        self::assertSame(255, $colour->r);
        self::assertSame(128, $colour->a);
        self::assertFalse($colour->isTransparent());
    }

    public function testPackedIntegersRoundTrip(): void
    {
        $colour = Rgba::rgba(1, 2, 3, 4);

        self::assertTrue($colour->equals(Rgba::fromPackedInt($colour->toPackedInt())));
        self::assertSame(0x01020304, $colour->toPackedInt());
    }

    /**
     * @return iterable<string, array{int, int, int, int}>
     */
    public static function outOfRangeChannels(): iterable
    {
        yield 'red too high' => [256, 0, 0, 255];
        yield 'green negative' => [0, -1, 0, 255];
        yield 'blue too high' => [0, 0, 300, 255];
        yield 'alpha negative' => [0, 0, 0, -1];
    }

    #[DataProvider('outOfRangeChannels')]
    public function testChannelsOutsideAByteAreRejected(int $r, int $g, int $b, int $a): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rgba($r, $g, $b, $a);
    }
}
