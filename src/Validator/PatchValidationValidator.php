<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TeamMatePro\Contracts\Dto\Undefined;
use function array_filter;
use function assert;

/**
 * Validator for PATCH request validation with Undefined value handling.
 *
 * This validator applies nested constraints to a value, with special handling for Undefined values:
 * - If value is Undefined and no NotBlank constraint exists, validation is skipped
 * - If value is Undefined and NotBlank constraint exists, it's converted to null for that constraint
 * - All other constraints validate the value as-is
 *
 * @see PatchValidation
 * @see Undefined
 */
final class PatchValidationValidator extends ConstraintValidator
{
    private readonly ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param PatchValidation $constraint
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        assert($constraint instanceof PatchValidation);

        $hasNotBlank = count(
                array_filter($constraint->constraints, fn($constraint) => $constraint instanceof NotBlank)
            ) > 0;

        // Skip validation if value is Undefined and no NotBlank constraint
        if ($value instanceof Undefined && $hasNotBlank === false) {
            return;
        }

        foreach ($constraint->constraints as $innerConstraint) {
            // Convert Undefined to null only for NotBlank constraint validation
            $valueToValidate = ($innerConstraint instanceof NotBlank && $value instanceof Undefined)
                ? null
                : $value;

            $violations = $this->validator->validate($valueToValidate, $innerConstraint);
            foreach ($violations as $violation) {
                /** @phpstan-ignore-next-line */
                $this->context->buildViolation($violation->getMessage())
                    ->addViolation();
            }
        }
    }
}
