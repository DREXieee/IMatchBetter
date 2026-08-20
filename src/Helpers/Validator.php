<?php

namespace IMatchBetter\Helpers;

class Validator
{
    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isStrongPassword(string $value): bool
    {
        return strlen($value) >= 8;
    }
}
