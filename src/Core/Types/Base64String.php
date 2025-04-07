<?php

declare(strict_types=1);

namespace App\Core\Types;

final class Base64String extends StringPhantomType
{
    /**
     * @param string $value
     * @return Base64String
     */
    public static function apply(string $value): Base64String
    {
        return parent::fromValue($value);
    }

    /**
     * @param Binary|string $bytes
     * @return Base64String
     */
    public static function fromBytes(Binary|string $bytes): Base64String
    {
        $toEncode = is_string($bytes) ? $bytes : $bytes->value;
        return parent::fromValue(base64_encode($toEncode));
    }

    /**
     * @throws \Exception
     */
    public function decode(): string
    {
        $decodeResult = base64_decode($this->value, true);

        if ($decodeResult === false) {
            throw new \Exception("Failed to decode base64 string: " . $this->value);
        }

        return $decodeResult;
    }
}
