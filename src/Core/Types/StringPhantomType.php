<?php

declare(strict_types=1);

namespace App\Core\Types;

/** I hope this meme brings you joy and curiosity https://imgflip.com/i/9nt6sr */
abstract class StringPhantomType
{
    public string $value;

    final public function __construct(string $value)
    {
        $this->value = $value;
    }

    protected static function fromValue(string $value): static
    {
        return new static($value);
    }
}