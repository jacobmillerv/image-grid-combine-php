<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * The source crop and the destination box produced by {@see Layout::fitRect()}.
 *
 * `source` is the region to read from the input image and `dest` is where it
 * lands inside the box, both relative to their own origins.
 */
final class FitRect
{
    public function __construct(
        /** Region of the source image to sample. */
        public readonly Rect $source,
        /** Region of the destination box to paint. */
        public readonly Rect $dest,
    ) {
    }
}
