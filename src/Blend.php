<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * Alpha compositing for straight (non-premultiplied) colours.
 */
final class Blend
{
    private function __construct()
    {
    }

    /**
     * Composite `$src` over `$dst` using the source-over operator.
     *
     * Both colours are straight alpha, matching the buffer layout of
     * {@see Canvas}.
     */
    public static function over(Rgba $dst, Rgba $src): Rgba
    {
        if ($src->a === 255 || $dst->a === 0) {
            return $src;
        }
        if ($src->a === 0) {
            return $dst;
        }

        $sa = $src->a / 255.0;
        $da = $dst->a / 255.0;
        $outAlpha = $sa + $da * (1.0 - $sa);
        if ($outAlpha <= 0.0) {
            return Rgba::transparent();
        }

        $channel = static function (int $s, int $d) use ($sa, $da, $outAlpha): int {
            $value = ($s * $sa + $d * $da * (1.0 - $sa)) / $outAlpha;

            return (int) max(0.0, min(255.0, round($value)));
        };

        return new Rgba(
            $channel($src->r, $dst->r),
            $channel($src->g, $dst->g),
            $channel($src->b, $dst->b),
            (int) max(0.0, min(255.0, round($outAlpha * 255.0))),
        );
    }
}
