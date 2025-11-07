<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;

final class FakeSecurityService extends Security
{
    public function __construct()
    {
        parent::__construct(
            new class implements ContainerInterface {

                public function get(string $id)
                {
                }

                public function has(string $id): bool
                {
                }
            }
        );
    }

    public function isGranted(mixed $attributes, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        return true;
    }

    public function getToken(): ?TokenInterface
    {
        return new NullToken();
    }
}
