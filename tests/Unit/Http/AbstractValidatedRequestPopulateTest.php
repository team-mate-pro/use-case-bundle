<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use TeamMatePro\UseCaseBundle\Http\RequestDependencies;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeSecurityService;
use Symfony\Bundle\SecurityBundle\Security;

final class AbstractValidatedRequestPopulateTest extends TestCase
{
    #[Test]
    public function populatesFromJsonBody(): void
    {
        $deps = $this->createDeps(new Request(
            content: (string) json_encode(['name' => 'John']),
            server: ['CONTENT_TYPE' => 'application/json']
        ));

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public string $name = '';
        };

        self::assertSame('John', $sut->name);
    }

    #[Test]
    public function populatesFromQueryParams(): void
    {
        $deps = $this->createDeps(new Request(query: ['page' => '5']));

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public string $page = '1';
        };

        self::assertSame('5', $sut->page);
    }

    #[Test]
    public function populatesUserIdFromAuthenticatedUser(): void
    {
        $deps = $this->createDeps(new Request());

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public ?string $userId = null;
        };

        // FakeSecurityService returns a user with getId() = null
        self::assertNull($sut->userId);
    }

    #[Test]
    public function validationFailsThrowsException(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new ConstraintViolation('error', null, [], null, 'field', null),
        ]));

        $stack = new RequestStack();
        $stack->push(new Request());
        $security = new FakeSecurityService();
        $deps = new RequestDependencies($validator, $stack, $security);

        $this->expectException(ValidationFailedException::class);

        new class ($deps) extends AbstractValidatedRequest {
            public string $required = '';
        };
    }

    #[Test]
    public function populatesFromFormData(): void
    {
        $request = new Request(request: ['title' => 'Hello']);
        $deps = $this->createDeps($request);

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public string $title = '';
        };

        self::assertSame('Hello', $sut->title);
    }

    #[Test]
    public function getValueWithNamedTypeProperty(): void
    {
        $deps = $this->createDeps(new Request());

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public string $simple = 'test';

            public function getSimple(): string
            {
                return $this->getValue('simple'); // @phpstan-ignore return.type
            }
        };

        self::assertSame('test', $sut->getSimple());
    }

    #[Test]
    public function getValueWithObjectValue(): void
    {
        $deps = $this->createDeps(new Request());
        $obj = new \stdClass();

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public mixed $item = null;

            public function getItem(): mixed
            {
                return $this->getValue('item');
            }
        };
        $sut->item = $obj;

        self::assertSame($obj, $sut->getItem());
    }

    #[Test]
    public function populatesWithSerializerStrategy(): void
    {
        $request = new Request(
            content: (string) json_encode(['title' => 'Hello']),
            server: ['CONTENT_TYPE' => 'application/json']
        );

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $stack = new RequestStack();
        $stack->push($request);

        $serializer = new Serializer([new ObjectNormalizer()]);

        $deps = new RequestDependencies($validator, $stack, new FakeSecurityService(), $serializer);

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public string $title = '';

            protected function getPopulateStrategy(): string
            {
                return self::SERIALIZER_STRATEGY;
            }
        };

        self::assertSame('Hello', $sut->title);
    }

    #[Test]
    public function populatesUserIdFromAuthenticatedUserWithId(): void
    {
        $request = new Request();
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $stack = new RequestStack();
        $stack->push($request);

        $user = new class implements UserInterface {
            public function getId(): string
            {
                return 'user-123';
            }
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }
            public function eraseCredentials(): void
            {
            }
            public function getUserIdentifier(): string
            {
                return 'user-123';
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn(true);

        $deps = new RequestDependencies($validator, $stack, $security);

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public ?string $userId = null;
        };

        self::assertSame('user-123', $sut->userId);
    }

    #[Test]
    public function getValueWithSingleNamedTypeProperty(): void
    {
        $deps = $this->createDeps(new Request());

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public ?string $field = 'hello';

            public function getField(): ?string
            {
                return $this->getValue('field'); // @phpstan-ignore return.type
            }
        };

        self::assertSame('hello', $sut->getField());
    }

    #[Test]
    public function getValueWithSingleNamedTypePropertyNull(): void
    {
        $deps = $this->createDeps(new Request());

        $sut = new class ($deps) extends AbstractValidatedRequest {
            public ?string $field = null;

            public function getField(): ?string
            {
                return $this->getValue('field'); // @phpstan-ignore return.type
            }
        };

        self::assertNull($sut->getField());
    }

    #[Test]
    public function castingWorksWithUnionReturnType(): void
    {
        $deps = $this->createDeps(new Request());

        $sut = new \TeamMatePro\UseCaseBundle\Tests\_Data\FakeTestingRequest($deps);
        $sut->mixedStringBool = true;

        // getValueAsStringOrInt returns string|int (true union) - should cast bool to string
        $result = $sut->getValueAsStringOrInt('mixedStringBool');

        self::assertSame('1', $result);
    }

    private function createDeps(Request $request): RequestDependencies
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $stack = new RequestStack();
        $stack->push($request);

        return new RequestDependencies($validator, $stack, new FakeSecurityService());
    }
}
