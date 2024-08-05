<?php

namespace FpDbTest;

enum SpecifiersEnum: string
{
    case INT = 'd';
    case FLOAT = 'f';
    case ARRAY = 'a';
    case IDENTIFIER = '#';
    case DEFAULT = '';

    /**
     * @return string
     */
    public function specificationFunction(): string
    {
        return match ($this) {
            self::INT => 'intval',
            self::FLOAT => 'floatval',
            self::ARRAY => 'formatArray',
            self::IDENTIFIER => 'formatIdentifier',
            default => 'escapeValue',
        };
    }

    /**
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return string
     */
    public static function placeholders(): string
    {
        return '/\?(' . implode("|",self::values()) . ')?/';
    }
}
