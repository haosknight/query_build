<?php

namespace FpDbTest;

enum SpecifiersEnum: string
{
    case INT = 'd';
    case FLOAT = 'f';
    case ARRAY = 'a';
    case IDENTIFIER = '#';
    case DEFAULT = '';

    public function specificationFunction()
    {
        return match ($this) {
            self::INT => 'intval',
            self::FLOAT => 'floatval',
            self::ARRAY => 'formatArray',
            self::IDENTIFIER => 'formatIdentifier',
            default => 'escapeValue',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function placeholders()
    {
        return '/\?(' . implode("|",self::values()) . ')?/';
    }
}
