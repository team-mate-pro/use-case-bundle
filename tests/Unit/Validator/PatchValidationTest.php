<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use TeamMatePro\UseCaseBundle\Validator\PatchValidation;
use TeamMatePro\UseCaseBundle\Validator\PatchValidationValidator;

#[CoversClass(PatchValidation::class)]
final class PatchValidationTest extends TestCase
{
    public function testCanBeInstantiatedWithConstraints(): void
    {
        $constraints = [new NotBlank(), new Email()];
        $patchValidation = new PatchValidation($constraints);

        $this->assertSame($constraints, $patchValidation->constraints);
    }

    public function testHasDefaultMessage(): void
    {
        $patchValidation = new PatchValidation([new NotBlank()]);

        $this->assertSame('Invalid value.', $patchValidation->message);
    }

    public function testValidatedByReturnsCorrectValidatorClass(): void
    {
        $patchValidation = new PatchValidation([new NotBlank()]);

        $this->assertSame(PatchValidationValidator::class, $patchValidation->validatedBy());
    }

    public function testAcceptsEmptyConstraintsArray(): void
    {
        $patchValidation = new PatchValidation([]);

        $this->assertIsArray($patchValidation->constraints);
        $this->assertCount(0, $patchValidation->constraints);
    }

    public function testAcceptsMultipleConstraints(): void
    {
        $constraints = [
            new NotBlank(),
            new Email(),
            new Length(['min' => 5, 'max' => 100]),
        ];
        $patchValidation = new PatchValidation($constraints);

        $this->assertCount(3, $patchValidation->constraints);
        $this->assertContainsOnlyInstancesOf(\Symfony\Component\Validator\Constraint::class, $patchValidation->constraints);
    }

    public function testCanBeUsedAsAttribute(): void
    {
        $reflection = new \ReflectionClass(PatchValidation::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);

        $attribute = $attributes[0]->newInstance();
        $this->assertSame(
            \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE,
            $attribute->flags
        );
    }
}
