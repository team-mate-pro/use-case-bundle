<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Utils;

use BackedEnum;
use JsonSerializable;
use ReflectionClass;
use ReflectionMethod;
use Stringable;

abstract class AbstractPlainDto implements JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [];
        $reflection = new ReflectionClass($this);

        // First, collect all public properties
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (!$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);
            $result[$name] = $this->normalizeValue($value);
        }

        // Then, collect all public methods (these override properties with same key)
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Skip magic methods, jsonSerialize itself, and methods requiring parameters
            if (str_starts_with($methodName, '__')) {
                continue;
            }

            if ($methodName === 'jsonSerialize') {
                continue;
            }

            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $key = $this->extractKeyFromMethodName($methodName);
            $value = $method->invoke($this);
            $result[$key] = $this->normalizeValue($value);
        }

        return $result;
    }

    private function extractKeyFromMethodName(string $methodName): string
    {
        // Strip 'get' prefix and lowercase first letter
        if (str_starts_with($methodName, 'get') && strlen($methodName) > 3) {
            return lcfirst(substr($methodName, 3));
        }

        // Strip 'is' prefix and lowercase first letter
        if (str_starts_with($methodName, 'is') && strlen($methodName) > 2) {
            return lcfirst(substr($methodName, 2));
        }

        // Strip 'has' prefix and lowercase first letter
        if (str_starts_with($methodName, 'has') && strlen($methodName) > 3) {
            return lcfirst(substr($methodName, 3));
        }

        return $methodName;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        return $value;
    }
}