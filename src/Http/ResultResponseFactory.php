<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use InvalidArgumentException;
use TeamMatePro\Contracts\Collection\Result;
use Stringable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Context\Encoder\CsvEncoderContextBuilder;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

use function base64_encode;

final readonly class ResultResponseFactory implements ResponseAsBlobCsvInterface, ResponseAsBlobInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    /**
     * @template TResult of array<int|string, mixed>|object
     * @param Result<TResult> $result
     * @param list<string>|string|null $serializationGroups
     */
    public function createCsvResponse(Result $result, bool $base64 = true, string $delimiter = ';', array|string|null $serializationGroups = null): Response
    {
        $contextBuilder = (new ObjectNormalizerContextBuilder());

        if ($serializationGroups !== null) {
            $contextBuilder = $contextBuilder->withGroups($serializationGroups);
        }

        $contextBuilder = (new CsvEncoderContextBuilder())
            ->withContext($contextBuilder)
            ->withDelimiter($delimiter);

        $res = $this->serializer->serialize(
            data: $result->getResult(),
            format: 'csv',
            context: $contextBuilder->toArray()
        );

        return new Response(
            content: $base64 ? base64_encode($res) : $res,
            status: 200,
            headers: [
                'Content-Type' => 'text/csv'
            ]
        );
    }

    /**
     * Accepts any Result to stay compatible with ResponseAsBlobInterface; the payload is
     * checked at runtime and must be Stringable.
     *
     * @template TResult of array<int|string, mixed>|object
     * @param Result<TResult> $result
     */
    public function createBlobResponse(Result $result, bool $base64 = true, string $mime = 'application/octet-stream'): Response
    {
        $item = $result->getResult();

        if (!$item instanceof Stringable) {
            throw new InvalidArgumentException('Result item must be a stringable');
        }

        return new Response(
            content: $base64 ? base64_encode((string)$item) : (string)$item,
            status: 200,
            headers: [
                'Content-Type' => $mime,
            ]
        );
    }
}
