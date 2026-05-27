<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http\RestApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\Contracts\Collection\ResultType;
use TeamMatePro\UseCaseBundle\Http\RestApi\HttpStatusCodeResolver;

#[CoversClass(HttpStatusCodeResolver::class)]
final class HttpStatusCodeResolverTest extends TestCase
{
    private HttpStatusCodeResolver $sut;

    protected function setUp(): void
    {
        $this->sut = new HttpStatusCodeResolver();
    }

    public function testResolvesSuccessTypes(): void
    {
        $this->assertSame(200, $this->sut->resolve(ResultType::SUCCESS));
        $this->assertSame(201, $this->sut->resolve(ResultType::SUCCESS_CREATED));
        $this->assertSame(202, $this->sut->resolve(ResultType::ACCEPTED));
        $this->assertSame(204, $this->sut->resolve(ResultType::SUCCESS_NO_CONTENT));
    }

    public function testResolvesClientErrorTypes(): void
    {
        $this->assertSame(400, $this->sut->resolve(ResultType::FAILURE));
        $this->assertSame(401, $this->sut->resolve(ResultType::UNAUTHORIZED));
        $this->assertSame(403, $this->sut->resolve(ResultType::FORBIDDEN));
        $this->assertSame(404, $this->sut->resolve(ResultType::NOT_FOUND));
        $this->assertSame(409, $this->sut->resolve(ResultType::DUPLICATED));
        $this->assertSame(410, $this->sut->resolve(ResultType::GONE));
        $this->assertSame(410, $this->sut->resolve(ResultType::EXPIRED));
        $this->assertSame(412, $this->sut->resolve(ResultType::PRECONDITION_FAILED));
        $this->assertSame(422, $this->sut->resolve(ResultType::UNPROCESSABLE));
        $this->assertSame(423, $this->sut->resolve(ResultType::LOCKED));
        $this->assertSame(429, $this->sut->resolve(ResultType::TOO_MANY_REQUESTS));
    }

    public function testResolvesServerErrorTypes(): void
    {
        $this->assertSame(503, $this->sut->resolve(ResultType::SERVICE_UNAVAILABLE));
    }
}
