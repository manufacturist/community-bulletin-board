<?php

declare(strict_types=1);

namespace App\Core\Types;

final class Moment extends StringPhantomType
{
    /**
     * @param string $value
     * @return Moment
     */
    public static function apply(string $value): Moment
    {
        return parent::fromValue($value);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function now(): Moment
    {
        $value = new \DateTime('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.v');
        return parent::fromValue($value);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function parse(string $value): Moment
    {
        $value = new \DateTime($value)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.v');

        return parent::fromValue($value);
    }
}
