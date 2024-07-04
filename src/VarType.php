<?php

declare(strict_types=1);

namespace Koriym\VarType;

use function array_keys;
use function array_unique;
use function count;
use function get_object_vars;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function range;

final class VarType
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public static function dump(mixed $value): void
    {
        echo (new self())($value);
    }

    public function __invoke(mixed $value): string
    {
        if (is_array($value)) {
            return $this->getArrayType($value);
        }

        if (is_object($value)) {
            return $this->getObjectType($value);
        }

        return $this->getScalarType($value);
    }

    /** @param array<mixed> $array */
    private function getArrayType(array $array): string
    {
        if (empty($array)) {
            return 'array';
        }

        $isAssociative = $this->isAssociativeArray($array);
        $types = [];

        /** @psalm-suppress  MixedAssignment */
        foreach ($array as $key => $value) {
            $valueType = $this->__invoke($value);
            if ($isAssociative) {
                $types[] = "{$key}: {$valueType}";
            } else {
                $types[] = $valueType;
            }
        }

        if ($isAssociative) {
            return 'array{' . implode(', ', $types) . '}';
        }

        $uniqueTypes = array_unique($types);

        return 'array<' . implode('|', $uniqueTypes) . '>';
    }

    private function getObjectType(object $object): string
    {
        /** @psalm-suppress  MixedAssignment */
        $className = $object::class;
        $properties = [];

        /** @psalm-suppress MixedAssignment */
        foreach (get_object_vars($object) as $key => $value) {
            $valueType = $this->__invoke($value);
            $properties[] = "{$key}: {$valueType}";
        }

        if (empty($properties)) {
            return $className;
        }

        return "{$className}{" . implode(', ', $properties) . '}';
    }

    private function getScalarType(mixed $value): string
    {
        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if ($value === null) {
            return 'null';
        }

        return 'string';
    }

    /** @param array<mixed> $array */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        $keys = array_keys($array);

        // Check if all keys are integers and form a sequence starting from 0
        return $keys !== range(0, count($array) - 1);
    }
}
