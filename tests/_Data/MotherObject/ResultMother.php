<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Tests\_Data\MotherObject;

use TeamMatePro\Contracts\Collection\Result;
use TeamMatePro\Contracts\Collection\ResultType;
use stdClass;

final class ResultMother
{
    public static function success(?string $message = null): Result
    {
        return Result::create(ResultType::SUCCESS, $message);
    }

    public static function successWithData(mixed $data, ?string $message = null): Result
    {
        return self::success($message)->with($data);
    }

    public static function successWithMetadata(array $metadata): Result
    {
        $result = self::success();
        foreach ($metadata as $key => $value) {
            $result->withMeta($key, $value);
        }
        return $result;
    }

    public static function failure(string $message = 'Operation failed'): Result
    {
        return Result::create(ResultType::FAILURE, $message);
    }

    public static function failureWithErrorCode(string $errorCode, string $message = 'Operation failed'): Result
    {
        return self::failure($message)->withErrorCode($errorCode);
    }

    public static function notFound(string $message = 'Resource not found'): Result
    {
        return Result::create(ResultType::NOT_FOUND, $message);
    }

    public static function created(mixed $data, string $message = 'Resource created'): Result
    {
        return Result::create(ResultType::SUCCESS_CREATED, $message)->with($data);
    }

    public static function accepted(string $message = 'Request accepted'): Result
    {
        return Result::create(ResultType::ACCEPTED, $message);
    }

    public static function duplicated(string $message = 'Resource already exists'): Result
    {
        return Result::create(ResultType::DUPLICATED, $message);
    }

    public static function locked(string $message = 'Resource is locked'): Result
    {
        return Result::create(ResultType::LOCKED, $message);
    }

    public static function gone(string $message = 'Resource is gone'): Result
    {
        return Result::create(ResultType::GONE, $message);
    }

    public static function expired(string $message = 'Resource expired'): Result
    {
        return Result::create(ResultType::EXPIRED, $message);
    }

    public static function noContent(): Result
    {
        return Result::create(ResultType::SUCCESS_NO_CONTENT);
    }

    public static function withUser(): Result
    {
        $user = new stdClass();
        $user->id = 123;
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        return self::successWithData($user, 'User retrieved');
    }

    public static function withEmptyArray(): Result
    {
        return self::successWithData([], 'Empty result');
    }

    public static function withCollection(int $count = 5): Result
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $item = new stdClass();
            $item->id = $i;
            $item->name = "Item $i";
            $items[] = $item;
        }

        return self::successWithData($items, 'Collection retrieved');
    }
}
