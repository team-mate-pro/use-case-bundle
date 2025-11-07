<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for PATCH request properties.
 *
 * This constraint allows wrapping multiple validation constraints and applies special
 * handling for Undefined values in PATCH requests. Useful when you want optional fields
 * to be validated only when they are explicitly provided.
 *
 * Example usage:
 * ```php
 * class UpdateUserRequest extends AbstractValidatedRequest
 * {
 *     #[PatchValidation([
 *         new NotBlank(),
 *         new Email(),
 *         new Length(min: 5, max: 100)
 *     ])]
 *     public string|Undefined $email = new Undefined();
 * }
 * ```
 *
 * @see PatchValidationValidator
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PatchValidation extends Constraint
{
    public string $message = 'Invalid value.';

    /**
     * @var Constraint[]
     */
    public array $constraints;

    /**
     * @param Constraint[] $constraints Array of constraints to apply to the value
     */
    public function __construct(array $constraints)
    {
        $this->constraints = $constraints;
        parent::__construct();
    }

    public function validatedBy(): string
    {
        return PatchValidationValidator::class;
    }
}
