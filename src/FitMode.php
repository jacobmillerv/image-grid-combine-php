<?php

declare(strict_types=1);

namespace ImageGridCombine;

/**
 * How a source rectangle is mapped onto its destination box.
 */
enum FitMode
{
    /** Fill the box, cropping the overflow, keeping the aspect ratio. */
    case Cover;

    /** Fit inside the box, letterboxing, keeping the aspect ratio. */
    case Contain;

    /** Fill the box exactly, ignoring the aspect ratio. */
    case Stretch;
}
