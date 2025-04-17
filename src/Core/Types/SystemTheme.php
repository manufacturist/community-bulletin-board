<?php

declare(strict_types=1);

namespace App\Core\Types;

final class SystemTheme
{
    public const string CORK = 'cork';
    public const string LIGHT = 'light';
    public const string DARK = 'dark';

    public const array THEMES = [self::CORK, self::LIGHT, self::DARK];
}