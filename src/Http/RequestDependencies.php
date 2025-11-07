<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class RequestDependencies
{
    public function __construct(
        public ValidatorInterface $validator,
        public RequestStack $requestStack,
        public Security $security,
        public ?SerializerInterface $serializer = null,
    ) {
    }
}
