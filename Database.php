<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    /** @var mysqli */
    private mysqli $mysqli;

    /** @var string */
    private const string SKIP = '__SKIP__'; // Специальное значение для пропуска

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @param string $query
     * @param array $args
     * @return string
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $placeholders = SpecifiersEnum::placeholders();

        $index = 0;
        $query = preg_replace_callback($placeholders, function ($matches) use ($args, &$index) {
            $specifier = $matches[1] ?? '';
            $value = $args[$index++] ?? self::SKIP;

            if ($value === self::SKIP) {
                return self::SKIP;
            }

            $specificationFunction = SpecifiersEnum::tryFrom($specifier)->specificationFunction();
            if (method_exists($this, $specificationFunction)) {
                return $this->$specificationFunction($value);
            } else {
                throw new Exception('Unknown specifier: ' . $specifier);
            }
        }, $query);

        $query = preg_replace_callback('/\{([^{}]*)\}/', function ($matches) {
            return str_contains($matches[1], self::SKIP) ? '' : $matches[1];
        }, $query);

        return str_replace(self::SKIP, '', $query);
    }

    /**
     * @return string
     */
    public function skip(): string
    {
        return self::SKIP;
    }

    private function intval($value)
    {
        return is_null($value) ? 'NULL' : intval($value);
    }

    private function escapeValue($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_string($value)) {
            return "'" . $this->mysqli->real_escape_string($value) . "'";
        } elseif (is_int($value) || is_float($value)) {
            return $value;
        } else {
            throw new Exception('Unsupported data type.');
        }
    }

    private function formatArray(array $array): string
    {
        if ($this->isAssoc($array)) {
            $result = [];
            foreach ($array as $key => $value) {
                $key = $this->formatIdentifier($key);
                $value = $this->escapeValue($value);
                $result[] = "$key = $value";
            }
            return implode(', ', $result);
        } else {
            $escapedArray = array_map([$this, 'escapeValue'], $array);
            return implode(', ', $escapedArray);
        }
    }

    private function formatIdentifier($identifier): string
    {
        if (is_array($identifier)) {
            return implode(', ', array_map(function ($item) {
                return '`' . $this->mysqli->real_escape_string($item) . '`';
            }, $identifier));
        } else {
            return '`' . $this->mysqli->real_escape_string($identifier) . '`';
        }
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
