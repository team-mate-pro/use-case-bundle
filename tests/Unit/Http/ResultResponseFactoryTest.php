<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\UseCaseBundle\Http\ResultResponseFactory;
use TeamMatePro\UseCaseBundle\Serializer\SerializationGroup;
use TeamMatePro\UseCaseBundle\Tests\_Data\FakeObjectToPopulate;
use TeamMatePro\Contracts\Collection\Result;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(ResultResponseFactory::class)]
final class ResultResponseFactoryTest extends TestCase
{
    public function testCreateBlobCsvResponse(): void
    {
        $useCaseResult = Result::create()->with([
            ['1', '2', '3', 'something_nested' => ['hih1', 'hih2']]
        ]);

        $serializer = new Serializer(
            normalizers: [],
            encoders: [new CsvEncoder()]
        );

        $sut = new ResultResponseFactory($serializer);
        $res = $sut->createCsvResponse(result: $useCaseResult, base64: false);

        $this->assertSame('text/csv', $res->headers->all('content-type')[0] ?? null);
        $this->assertStringContainsString('1;2;3', $res->getContent());
    }

    public function testCreateBlobCsvResponseWithSerializationGroups(): void
    {
        $obj = new FakeObjectToPopulate();
        $obj->name = 'name';
        $obj->age = 2;
        $useCaseResult = Result::create()->with([$obj]);

        $serializer = new Serializer(
            normalizers: [new ObjectNormalizer()],
            encoders: [new CsvEncoder()]
        );

        $sut = new ResultResponseFactory($serializer);
        $res = $sut->createCsvResponse(result: $useCaseResult, base64: false, delimiter: ',', serializationGroups: [SerializationGroup::CSV]);

        $this->assertStringContainsString(',name,2,', $res->getContent());
    }
}
