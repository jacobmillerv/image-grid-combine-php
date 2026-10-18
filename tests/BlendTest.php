<?php

declare(strict_types=1);

namespace ImageGridCombine\Tests;

use ImageGridCombine\Blend;
use ImageGridCombine\Rgba;
use PHPUnit\Framework\TestCase;

final class BlendTest extends TestCase
{
    public function testAnOpaqueSourceReplacesTheDestination(): void
    {
        self::assertTrue(Blend::over(Rgba::white(), Rgba::black())->equals(Rgba::black()));
    }

    public function testAClearSourceKeepsTheDestination(): void
    {
        self::assertTrue(Blend::over(Rgba::white(), Rgba::transparent())->equals(Rgba::white()));
    }

    public function testAClearDestinationKeepsTheSource(): void
    {
        $halfRed = Rgba::rgba(255, 0, 0, 128);

        self::assertTrue(Blend::over(Rgba::transparent(), $halfRed)->equals($halfRed));
    }

    public function testHalfTransparentColoursAreMixed(): void
    {
        $result = Blend::over(Rgba::white(), Rgba::rgba(255, 0, 0, 128));

        self::assertSame(255, $result->a);
        self::assertSame(255, $result->r);
        self::assertSame(127, $result->g);
        self::assertSame(127, $result->b);
    }

    public function testTwoTranslucentColoursAccumulateAlpha(): void
    {
        $result = Blend::over(Rgba::rgba(0, 0, 255, 128), Rgba::rgba(255, 0, 0, 128));

        self::assertSame(192, $result->a);
        self::assertSame(170, $result->r);
        self::assertSame(0, $result->g);
        self::assertSame(85, $result->b);
    }
}
