<?php

declare(strict_types=1);

namespace App\Core\Types;

final class Binary extends StringPhantomType
{
    /**
     * @param string $value
     * @return Binary
     */
    public static function apply(string $value): Binary
    {
        return parent::fromValue($value);
    }
}
