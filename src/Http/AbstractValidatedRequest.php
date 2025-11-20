<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use TeamMatePro\Contracts\Dto\Undefined;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use function array_merge;
use function get_class;
use function method_exists;
use function property_exists;
use TeamMatePro\UseCaseBundle\Http\Exception\HttpMalformedRequestException;
use function sprintf;

/**
 * Abstract base class for validated HTTP requests in SSA Core Bundle.
 *
 * This class provides automatic request data population, validation, security checks,
 * and intelligent type casting for getter methods.
 *
 * ## Key Features:
 *
 * ### 1. Auto-population
 * Request data (JSON body, query params, route attributes) is automatically populated
 * into public properties. Properties with Undefined type hints are initialized with
 * Undefined instances if not present in the request.
 *
 * ### 2. Security Check
 * Override securityCheck() to implement authorization logic. Throws AccessDeniedException
 * if security check fails (returns false).
 *
 * ### 3. Auto-validation
 * Symfony validator constraints are automatically executed on construction unless
 * autoValidateRequest() returns false.
 *
 * ### 4. User ID Injection
 * If the request has a 'userId' property and a user is authenticated, it's auto-populated
 * with the authenticated user's ID.
 *
 * ### 5. getValue() Helper with Type Casting
 * The protected getValue() method provides comprehensive validation and automatic type casting:
 *
 * - Checks if property exists, is set, and is not null/Undefined (unless caller allows it)
 * - Automatically casts values to match the caller method's return type signature
 * - Supports casting between simple types: string, int, float, bool
 *
 * #### Type Casting Examples:
 * ```php
 * // Property: public string|int|null $age;
 * $this->age = 25; // int value
 *
 * // Getter with string return type automatically casts int to string
 * public function getAge(): string
 * {
 *     return $this->getValue('age'); // Returns "25" (string)
 * }
 *
 * // Property: public string|bool|null $enabled;
 * $this->enabled = "true";
 *
 * // Getter with bool return type automatically casts string to bool
 * public function isEnabled(): bool
 * {
 *     return $this->getValue('enabled'); // Returns true (bool)
 * }
 * ```
 *
 * #### Casting Rules:
 * - **To string**: int, float, bool (true→"1", false→"0")
 * - **To int**: numeric string, float (truncates), bool (true→1, false→0)
 * - **To float**: numeric string, int
 * - **To bool**: string ("1","true","yes","on"→true), int (0→false, other→true), float (0.0→false, other→true)
 *
 * ### 6. Populate Strategies
 * Two strategies available (override getPopulateStrategy()):
 * - PROPERTY_SET_STRATEGY (default): Direct property assignment
 * - SERIALIZER_STRATEGY: Uses Symfony serializer for complex denormalization
 *
 * ## Usage Example:
 * ```php
 * class CreateUserRequest extends AbstractValidatedRequest
 * {
 *     #[Assert\NotBlank]
 *     #[Assert\Email]
 *     public string $email;
 *
 *     #[Assert\NotBlank]
 *     public string|int $age;
 *
 *     public string|null $userId = null;
 *
 *     protected function securityCheck(): bool
 *     {
 *         return $this->isGranted('ROLE_USER');
 *     }
 *
 *     public function getEmail(): string
 *     {
 *         return $this->getValue('email');
 *     }
 *
 *     // Automatically casts int to string if needed
 *     public function getAge(): string
 *     {
 *         return $this->getValue('age');
 *     }
 * }
 * ```
 *
 * @method string|null getUserId()
 * @method bool isGranted(mixed $attributes, mixed $subject = null)
 * @property-read Security $security
 */
abstract class AbstractValidatedRequest
{
    public const PROPERTY_SET_STRATEGY = 'property';
    public const SERIALIZER_STRATEGY = 'serializer';

    protected array $headers = [];

    public function __construct(protected readonly RequestDependencies $deps)
    {
        $this->populate();

        if ($this->securityCheck() === false) {
            throw new AccessDeniedException();
        }

        if ($this->autoValidateRequest()) {
            $this->validate();
        }
    }

    protected function populate(): void
    {
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties() as $property) {
            $type = $property->getType();

            if (!method_exists($type, 'getTypes')) {
                continue;
            }

            foreach ($type->getTypes() as $type) {
                if ($type->isBuiltin()) {
                    continue;
                }

                if ($type->getName() === Undefined::class) {
                    $this->{$property->getName()} = new Undefined();
                }
            }
        }
        $currentRequest = $this->deps->requestStack->getCurrentRequest() ?? throw new RuntimeException('No request');

        $this->headers = $currentRequest->headers->all();

        $jsonBody = [];

        try {
            $jsonBody = $currentRequest->toArray();
        } catch (JsonException $e) {
        }

        $data = array_merge($currentRequest->attributes->all(), $jsonBody, $currentRequest->query->all());

        if ($files = $currentRequest->files->all('file')) {
            $data = array_merge($data, ['files' => $files]);
        }

        if ($this->getPopulateStrategy() === self::PROPERTY_SET_STRATEGY) {
            foreach ($data as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
            }
        } else {
            if ($this->getPopulateStrategy() === self::SERIALIZER_STRATEGY) {
                if (!$this->deps->serializer) {
                    throw new RuntimeException('Serializer is required for this strategy');
                }
                $this->deps->serializer->denormalize(
                    $data,
                    get_class($this),
                    null,
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $this]
                );
            } else {
                throw new RuntimeException('Unknown populate strategy');
            }
        }

        $user = $this->deps->security->getUser();

        if (property_exists($this, 'userId') && $user && method_exists($user, 'getId')) {
            $this->userId = $this->deps->security->getUser()->getId();
        }
    }

    protected function getPopulateStrategy(): string
    {
        return self::PROPERTY_SET_STRATEGY;
    }

    protected function securityCheck(): bool
    {
        return true;
    }

    protected function autoValidateRequest(): bool
    {
        return true;
    }

    public function validate(): void
    {
        $errors = $this->deps->validator->validate($this);

        if (count($errors) > 0) {
            throw new ValidationFailedException($this, $errors);
        }
    }

    final public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name][0]);
    }

    final public function getHeader(string $name): ?string
    {
        return $this->headers[$name][0] ?? null;
    }

    public function __call(string $name, array $arguments)
    {
        if ($name === 'getUserId') {
            if (!property_exists($this, 'userId')) {
                throw new RuntimeException('userId property is required');
            }
            return $this->userId;
        }

        if ($name === 'isGranted') {
            return $this->deps->security->isGranted(...$arguments);
        }
        throw new RuntimeException('Method not found: ' . $name);
    }

    public function __get(string $name)
    {
        if (property_exists($this->deps, $name)) {
            return $this->deps->{$name};
        }
        throw new RuntimeException('Property not found: ' . $name);
    }

    final public function getMimeType(): ?string
    {
        return $this->headers['accept'][0] ?? null;
    }

    final protected function getValue(string $property): mixed
    {
        if (!property_exists($this, $property)) {
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" does not exist', $property));
        }

        $value = $this->{$property};

        // Get property type information
        $reflection = new ReflectionClass($this);
        $reflectionProperty = $reflection->getProperty($property);
        $propertyType = $reflectionProperty->getType();

        // Check if property type allows null or Undefined
        $propertyAllowsNull = false;
        $propertyAllowsUndefined = false;

        if ($propertyType instanceof ReflectionUnionType) {
            foreach ($propertyType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType) {
                    if ($type->getName() === 'null') {
                        $propertyAllowsNull = true;
                    }
                    if ($type->getName() === Undefined::class) {
                        $propertyAllowsUndefined = true;
                    }
                }
            }
        } elseif ($propertyType instanceof ReflectionNamedType) {
            $propertyAllowsNull = $propertyType->allowsNull();
            if ($propertyType->getName() === Undefined::class) {
                $propertyAllowsUndefined = true;
            }
        }

        // Get caller method information
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? null;

        $callerAllowsNull = false;
        $callerAllowsUndefined = false;

        if ($caller && isset($caller['class'])) {
            try {
                $callerReflection = new ReflectionMethod($caller['class'], $caller['function']);
                $returnType = $callerReflection->getReturnType();

                if ($returnType instanceof ReflectionUnionType) {
                    foreach ($returnType->getTypes() as $type) {
                        if ($type instanceof ReflectionNamedType) {
                            if ($type->getName() === 'null') {
                                $callerAllowsNull = true;
                            }
                            if ($type->getName() === Undefined::class) {
                                $callerAllowsUndefined = true;
                            }
                        }
                    }
                } elseif ($returnType instanceof ReflectionNamedType) {
                    $callerAllowsNull = $returnType->allowsNull();
                    if ($returnType->getName() === Undefined::class) {
                        $callerAllowsUndefined = true;
                    }
                    // Handle mixed return type
                    if ($returnType->getName() === 'mixed') {
                        $callerAllowsNull = true;
                        $callerAllowsUndefined = true;
                    }
                } elseif ($returnType === null) {
                    // No return type means mixed, which allows everything
                    $callerAllowsNull = true;
                    $callerAllowsUndefined = true;
                }
            } catch (ReflectionException $e) {
                // If we can't reflect the caller, assume it doesn't allow null/undefined
            }
        } else {
            // No caller information, assume mixed (allows everything)
            $callerAllowsNull = true;
            $callerAllowsUndefined = true;
        }

        // Check if value is null
        if ($value === null) {
            if ($propertyAllowsNull && $callerAllowsNull) {
                return null;
            }
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" is null', $property));
        }

        // Check if value is Undefined
        if ($value instanceof Undefined) {
            if ($propertyAllowsUndefined && $callerAllowsUndefined) {
                return $value;
            }
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" is undefined', $property));
        }

        // Check if value is not set (isset returns false for null and uninitialized properties)
        if (!isset($this->{$property})) {
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" is not set', $property));
        }

        // Attempt type casting if caller has specific type requirements
        if ($caller && isset($caller['class'])) {
            try {
                $callerReflection = new ReflectionMethod($caller['class'], $caller['function']);
                $returnType = $callerReflection->getReturnType();

                if ($returnType instanceof ReflectionNamedType || $returnType instanceof ReflectionUnionType) {
                    $value = $this->castValueToCallerType($value, $returnType);
                }
            } catch (ReflectionException $e) {
                // If we can't reflect the caller, return value as-is
            }
        }

        return $value;
    }

    /**
     * Cast value to match caller's expected type
     */
    private function castValueToCallerType(mixed $value, ReflectionNamedType|ReflectionUnionType $returnType): mixed
    {
        $expectedTypes = $this->extractExpectedTypes($returnType);
        $currentType = $this->getValueType($value);

        // If current type is already in expected types, no casting needed
        if (in_array($currentType, $expectedTypes, true)) {
            return $value;
        }

        // Try to cast to the first compatible expected type
        foreach ($expectedTypes as $expectedType) {
            $casted = $this->tryCastValue($value, $currentType, $expectedType);
            if ($casted !== null) {
                return $casted;
            }
        }

        // If no casting was successful, return original value
        return $value;
    }

    /**
     * Extract expected type names from return type
     * @return array<string>
     */
    private function extractExpectedTypes(ReflectionNamedType|ReflectionUnionType $returnType): array
    {
        $types = [];

        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($typeName !== 'null' && $typeName !== Undefined::class) {
                        $types[] = $typeName;
                    }
                }
            }
        } elseif ($returnType instanceof ReflectionNamedType) {
            $typeName = $returnType->getName();
            if ($typeName !== 'mixed' && $typeName !== 'null' && $typeName !== Undefined::class) {
                $types[] = $typeName;
            }
        }

        return $types;
    }

    /**
     * Get the type of a value as a string
     */
    private function getValueType(mixed $value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }

        return gettype($value) === 'double' ? 'float' : gettype($value);
    }

    /**
     * Try to cast a value from one type to another
     * Returns null if casting is not possible or makes no sense
     */
    private function tryCastValue(mixed $value, string $fromType, string $toType): mixed
    {
        // Same type, no casting needed
        if ($fromType === $toType) {
            return $value;
        }

        // Casting to string
        if ($toType === 'string') {
            if ($fromType === 'integer' || $fromType === 'float') {
                return is_int($value) || is_float($value) ? (string)$value : null;
            }
            if ($fromType === 'boolean' && is_bool($value)) {
                return $value ? '1' : '0';
            }
        }

        // Casting to int
        if ($toType === 'int' || $toType === 'integer') {
            if ($fromType === 'string' && is_string($value) && is_numeric($value)) {
                return (int)$value;
            }
            if ($fromType === 'float' && is_float($value)) {
                return (int)$value;
            }
            if ($fromType === 'boolean' && is_bool($value)) {
                return $value ? 1 : 0;
            }
        }

        // Casting to float
        if ($toType === 'float') {
            if ($fromType === 'string' && is_string($value) && is_numeric($value)) {
                return (float)$value;
            }
            if ($fromType === 'integer' && is_int($value)) {
                return (float)$value;
            }
        }

        // Casting to bool
        if ($toType === 'bool' || $toType === 'boolean') {
            if ($fromType === 'string' && is_string($value)) {
                return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
            }
            if ($fromType === 'integer' && is_int($value)) {
                return $value !== 0;
            }
            if ($fromType === 'float' && is_float($value)) {
                return $value !== 0.0;
            }
        }

        // No valid casting found
        return null;
    }
}
