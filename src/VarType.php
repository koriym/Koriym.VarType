<?php

declare(strict_types=1);

namespace Koriym\VarType;

final class VarType
{
    static public function dump($value): void
    {
        echo (new self())($value);
    }

    public function __invoke($value): string
    {
        if (is_array($value)) {
            return $this->getArrayType($value);
        } elseif (is_object($value)) {
            return $this->getObjectType($value);
        } else {
            return $this->getScalarType($value);
        }
    }

    private function getArrayType(array $array): string
    {
        if (empty($array)) {
            return 'array';
        }

        $isAssociative = $this->isAssociativeArray($array);
        $types = [];

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
        } else {
            $uniqueTypes = array_unique($types);
            return 'array<' . implode('|', $uniqueTypes) . '>';
        }
    }

    private function getObjectType(object $object): string
    {
        $className = get_class($object);
        $properties = [];

        foreach (get_object_vars($object) as $key => $value) {
            $valueType = $this->__invoke($value);
            $properties[] = "{$key}: {$valueType}";
        }

        if (empty($properties)) {
            return $className;
        } else {
            return "{$className}{" . implode(', ', $properties) . '}';
        }
    }

    private function getScalarType($value): string
    {
        if (is_int($value)) {
            return 'int';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_bool($value)) {
            return 'bool';
        } elseif (is_string($value)) {
            return 'string';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return gettype($value);
        }
    }

    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
