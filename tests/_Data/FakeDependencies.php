<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data;

use TeamMatePro\UseCaseBundle\Http\RequestDependencies;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class FakeDependencies extends RequestDependencies
{
    public function __construct()
    {
        $validator = new class() implements ValidatorInterface {

            public function getMetadataFor(mixed $value): MetadataInterface
            {
                return new class implements MetadataInterface {

                    public function getCascadingStrategy(): int
                    {
                        return 0;
                    }

                    public function getTraversalStrategy(): int
                    {
                        return 0;
                    }

                    public function getConstraints(): array
                    {
                        return [];
                    }

                    public function findConstraints(string $group): array
                    {
                        return [];
                    }
                };
            }

            public function hasMetadataFor(mixed $value): bool
            {
                return true;
            }

            public function validate(
                mixed                           $value,
                array|Constraint|null           $constraints = null,
                array|GroupSequence|string|null $groups = null
            ): ConstraintViolationListInterface
            {
                return new ConstraintViolationList();
            }

            public function validateProperty(
                object                          $object,
                string                          $propertyName,
                array|GroupSequence|string|null $groups = null
            ): ConstraintViolationListInterface
            {
                return new ConstraintViolationList();
            }

            public function validatePropertyValue(
                object|string                   $objectOrClass,
                string                          $propertyName,
                mixed                           $value,
                array|GroupSequence|string|null $groups = null
            ): ConstraintViolationListInterface
            {
                return new ConstraintViolationList();
            }

            public function startContext(): ContextualValidatorInterface
            {
            }

            public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
            {
            }
        };

        $stack = new RequestStack();
        $stack->push(new Request(
            server: [
                'HTTP_X_CUSTOM_HEADER' => 'foobar',
                'HTTP_AUTHORIZATION' => 'Bearer 123',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ]
        ));

        parent::__construct(
            validator: $validator,
            requestStack: $stack,
            security: new FakeSecurityService(),
        );
    }
}
