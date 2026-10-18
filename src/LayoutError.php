<?php

declare(strict_types=1);

namespace ImageGridCombine;

use InvalidArgumentException;

/**
 * Everything that can go wrong while computing a layout.
 *
 * The `reason` code is stable, so callers can branch on it without matching
 * the human-readable message.
 */
final class LayoutError extends InvalidArgumentException
{
    public const NO_CELLS = 'no_cells';
    public const NON_POSITIVE_CELL_SIZE = 'non_positive_cell_size';
    public const NEGATIVE_SPACING = 'negative_spacing';
    public const NO_COLUMNS = 'no_columns';
    public const NON_POSITIVE_SOURCE = 'non_positive_source';
    public const NON_POSITIVE_BOX = 'non_positive_box';
    public const NO_SOURCES = 'no_sources';

    private function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * A grid was requested with no cells.
     */
    public static function noCells(): self
    {
        return new self(self::NO_CELLS, 'count must be a positive integer');
    }

    /**
     * Cell width or height was zero, negative or NAN.
     */
    public static function nonPositiveCellSize(): self
    {
        return new self(self::NON_POSITIVE_CELL_SIZE, 'cell width and height must be positive numbers');
    }

    /**
     * Gap or padding was negative or NAN.
     */
    public static function negativeSpacing(): self
    {
        return new self(self::NEGATIVE_SPACING, 'gap and padding must not be negative');
    }

    /**
     * A column count of zero was requested.
     */
    public static function noColumns(): self
    {
        return new self(self::NO_COLUMNS, 'columns must be a positive integer');
    }

    /**
     * A source image had a zero, negative or NAN dimension.
     */
    public static function nonPositiveSource(): self
    {
        return new self(self::NON_POSITIVE_SOURCE, 'source width and height must be positive numbers');
    }

    /**
     * The destination box had a zero, negative or NAN dimension.
     */
    public static function nonPositiveBox(): self
    {
        return new self(self::NON_POSITIVE_BOX, 'box width and height must be positive numbers');
    }

    /**
     * {@see Layout::autoCellSize()} or {@see GridCombiner::combine()} got an
     * empty array.
     */
    public static function noSources(): self
    {
        return new self(self::NO_SOURCES, 'sources must be a non-empty array');
    }
}
