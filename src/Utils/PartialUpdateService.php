<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Utils;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use TeamMatePro\Contracts\Dto\Undefined;

final readonly class PartialUpdateService
{
    /**
     * Copy values from $from to $into by calling setters on $into,
     * but only for getters on $from that return a value NOT instance of Undefined.
     *
     * @template T of object
     * @param object $from
     * @param T $into
     * @param string[] $skips
     * @return T
     * @throws ReflectionException
     */
    public function map(object $from, object $into, bool $strict = false, array $skips = []): object
    {
        $ref = new ReflectionClass($from);
        $targetRef = new ReflectionClass($into);

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->getNumberOfRequiredParameters() !== 0) {
                continue;
            }

            $name = $m->getName();
            if (!preg_match('/^(get|is)(.+)$/', $name, $mch)) {
                continue; // not a getter
            }

            $prop = lcfirst($mch[2]);

            // Skip if property is in skips array
            if (in_array($prop, $skips, true)) {
                continue;
            }

            $value = $m->invoke($from);

            // Skip if sentinel "Undefined"
            if (is_object($value) && is_a($value, Undefined::class)) {
                continue;
            }

            // Apply value if $into has a matching public setter or public property
            $setter = 'set' . ucfirst($prop);
            if ($targetRef->hasMethod($setter)) {
                $setterMethod = $targetRef->getMethod($setter);
                if ($setterMethod->isPublic()) {
                    $into->$setter($value);
                    continue;
                }
            }

            if ($targetRef->hasProperty($prop)) {
                $property = $targetRef->getProperty($prop);
                if ($property->isPublic()) {
                    $into->$prop = $value;
                    continue;
                }
            }

            if ($strict) {
                throw new InvalidArgumentException(
                    'Unable to map property "' . $prop . '" to setter "' . $setter . '".'
                );
            }
        }

        return $into;
    }
}
