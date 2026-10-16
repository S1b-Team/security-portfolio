<?php

namespace App\Support;

final class FixMode
{
    public static function enabled(): bool
    {
        return (bool) config('lab.fix_enabled', true);
    }
}
