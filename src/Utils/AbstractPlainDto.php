<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Utils;

use BackedEnum;
use JsonSerializable;
use ReflectionClass;
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

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (!$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);
            $result[$name] = $this->normalizeValue($value);
        }

        return $result;
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