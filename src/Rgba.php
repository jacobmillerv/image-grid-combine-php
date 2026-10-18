<?php

declare(strict_types=1);

namespace ImageGridCombine;

use InvalidArgumentException;

/**
 * A straight (non-premultiplied) RGBA8 colour.
 *
 * Channels are validated on construction, so a colour that exists is always a
 * colour that can be written to a buffer without clamping.
 */
final class Rgba
{
    public function __construct(
        public readonly int $r,
        public readonly int $g,
        public readonly int $b,
        public readonly int $a = 255,
    ) {
        foreach (['r' => $r, 'g' => $g, 'b' => $b, 'a' => $a] as $channel => $value) {
            if ($value < 0 || $value > 255) {
                throw new InvalidArgumentException(sprintf(
                    'Channel %s must be between 0 and 255, got %d.',
                    $channel,
                    $value,
                ));
            }
        }
    }

    /**
     * An opaque colour.
     */
    public static function rgb(int $r, int $g, int $b): self
    {
        return new self($r, $g, $b);
    }

    /**
     * A colour with an explicit alpha.
     */
    public static function rgba(int $r, int $g, int $b, int $a): self
    {
        return new self($r, $g, $b, $a);
    }

    /**
     * Fully transparent black.
     */
    public static function transparent(): self
    {
        return new self(0, 0, 0, 0);
    }

    /**
     * Opaque black.
     */
    public static function black(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * Opaque white.
     */
    public static function white(): self
    {
        return new self(255, 255, 255);
    }

    /**
     * Whether nothing at all would be painted by this colour.
     */
    public function isTransparent(): bool
    {
        return $this->a === 0;
    }

    /**
     * The same colour with a different alpha.
     */
    public function withAlpha(int $a): self
    {
        return new self($this->r, $this->g, $this->b, $a);
    }

    public function equals(self $other): bool
    {
        return $this->r === $other->r
            && $this->g === $other->g
            && $this->b === $other->b
            && $this->a === $other->a;
    }

    /**
     * The colour packed as a single `0xRRGGBBAA` integer.
     *
     * @internal Used by {@see Canvas} to store pixels compactly.
     */
    public function toPackedInt(): int
    {
        return ($this->r << 24) | ($this->g << 16) | ($this->b << 8) | $this->a;
    }

    /**
     * @internal Used by {@see Canvas} to read pixels back out.
     */
    public static function fromPackedInt(int $packed): self
    {
        return new self(
            ($packed >> 24) & 0xFF,
            ($packed >> 16) & 0xFF,
            ($packed >> 8) & 0xFF,
            $packed & 0xFF,
        );
    }
}
