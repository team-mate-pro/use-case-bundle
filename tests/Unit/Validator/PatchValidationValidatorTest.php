<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use TeamMatePro\Contracts\Dto\Undefined;
use TeamMatePro\UseCaseBundle\Validator\PatchValidation;
use TeamMatePro\UseCaseBundle\Validator\PatchValidationValidator;

#[CoversClass(PatchValidationValidator::class)]
final class PatchValidationValidatorTest extends TestCase
{
    /** @var ValidatorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ValidatorInterface $validator;
    /** @var ExecutionContextInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ExecutionContextInterface $context;
    private PatchValidationValidator $patchValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->patchValidator = new PatchValidationValidator($this->validator);
        $this->patchValidator->initialize($this->context);
    }

    public function testValidateWithUndefinedValueAndNoNotBlankConstraintDoesNotValidate(): void
    {
        $constraint = new PatchValidation([new Email()]);
        $value = new Undefined();

        $this->validator->expects($this->never())
            ->method('validate');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithUndefinedValueAndNotBlankConstraintConvertsToNull(): void
    {
        $constraint = new PatchValidation([new NotBlank()]);
        $value = new Undefined();

        $this->validator->expects($this->once())
            ->method('validate')
            ->with(null, $this->isInstanceOf(NotBlank::class))
            ->willReturn(new ConstraintViolationList());

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithDefinedValueValidatesAgainstAllConstraints(): void
    {
        $constraints = [new NotBlank(), new Email()];
        $constraint = new PatchValidation($constraints);
        $value = 'test@example.com';

        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateAddsViolationsToContext(): void
    {
        $constraint = new PatchValidation([new Email()]);
        $value = 'invalid-email';

        $violation = new ConstraintViolation(
            'This is not a valid email address.',
            'This is not a valid email address.',
            [],
            $value,
            'email',
            $value
        );
        $violations = new ConstraintViolationList([$violation]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('This is not a valid email address.')
            ->willReturn($violationBuilder);

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithMultipleViolationsAddsAllToContext(): void
    {
        $constraint = new PatchValidation([new Email(), new Length(['min' => 20])]);
        $value = 'invalid';

        $violation1 = new ConstraintViolation(
            'Email violation',
            'Email violation',
            [],
            $value,
            'email',
            $value
        );
        $violation2 = new ConstraintViolation(
            'Length violation',
            'Length violation',
            [],
            $value,
            'length',
            $value
        );

        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList([$violation1]),
                new ConstraintViolationList([$violation2])
            );

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->exactly(2))
            ->method('addViolation');

        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithNoViolationsDoesNotAddToContext(): void
    {
        $constraint = new PatchValidation([new Email()]);
        $value = 'valid@example.com';

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithEmptyConstraintsArrayDoesNothing(): void
    {
        $constraint = new PatchValidation([]);
        $value = 'any-value';

        $this->validator->expects($this->never())
            ->method('validate');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateHandlesNotBlankWithUndefinedCorrectly(): void
    {
        $constraint = new PatchValidation([new NotBlank(), new Email()]);
        $value = new Undefined();

        $notBlankViolation = new ConstraintViolation(
            'This value should not be blank.',
            'This value should not be blank.',
            [],
            null,
            'property',
            null
        );

        $this->validator->expects($this->exactly(2))
            ->method('validate')
            ->willReturnCallback(function ($val, $constraint) use ($notBlankViolation) {
                if ($constraint instanceof NotBlank) {
                    // NotBlank receives null (converted from Undefined)
                    $this->assertNull($val);
                    return new ConstraintViolationList([$notBlankViolation]);
                }
                if ($constraint instanceof Email) {
                    // Email receives the original Undefined value (not converted)
                    $this->assertInstanceOf(Undefined::class, $val);
                    return new ConstraintViolationList();
                }
                return new ConstraintViolationList();
            });

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new PatchValidation([new Email()]);
        $value = null;

        $this->validator->expects($this->once())
            ->method('validate')
            ->with(null, $this->isInstanceOf(Email::class))
            ->willReturn(new ConstraintViolationList());

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithEmptyString(): void
    {
        $constraint = new PatchValidation([new NotBlank()]);
        $value = '';

        $violation = new ConstraintViolation(
            'This value should not be blank.',
            'This value should not be blank.',
            [],
            $value,
            'property',
            $value
        );

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->patchValidator->validate($value, $constraint);
    }

    public function testValidateWithComplexObject(): void
    {
        $constraint = new PatchValidation([new NotBlank()]);
        $value = new \stdClass();
        $value->property = 'test';

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($value, $this->isInstanceOf(NotBlank::class))
            ->willReturn(new ConstraintViolationList());

        $this->patchValidator->validate($value, $constraint);
    }
}
