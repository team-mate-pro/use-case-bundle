<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use ReflectionClass;
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

        // Check if value is null
        if ($value === null) {
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" is null', $property));
        }

        // Check if value is Undefined
        if ($value instanceof Undefined) {
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" is undefined', $property));
        }

        // Check if value is not set (isset returns false for null and uninitialized properties)
        // This check is now redundant since we already checked for null and Undefined above
        // but we keep it for uninitialized typed properties
        if (!isset($this->{$property})) {
            throw new HttpMalformedRequestException(message: sprintf('Property "%s" is not set', $property));
        }

        return $value;
    }
}
