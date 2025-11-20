<?php

declare(strict_types=1);

namespace App\Request;

use TeamMatePro\UseCaseBundle\Http\AbstractValidatedRequest;
use TeamMatePro\UseCaseBundle\Http\PaginationTrait;

/**
 * Example usage of PaginationTrait in a request
 */
class GetUsersRequest extends AbstractValidatedRequest
{
    use PaginationTrait;

    // The trait provides:
    // - public string|int|null $page = null;
    // - public string|int|null $perPage = null;
    // - public function getPagination(): Pagination

    // You can add other request properties here
    public ?string $search = null;
    public ?string $status = null;
}

/**
 * Example usage in a use case:
 *
 * class GetUsersUseCase
 * {
 *     public function execute(GetUsersRequest $request): Result
 *     {
 *         $pagination = $request->getPagination();
 *         // $pagination->getOffset() - returns the offset (e.g., 0, 20, 40...)
 *         // $pagination->getLimit() - returns the limit (e.g., 20, 50, 100...)
 *
 *         $users = $this->userRepository->findAll($pagination);
 *
 *         return Result::create(ResultType::SUCCESS)->with($users);
 *     }
 * }
 */

/**
 * Example API usage:
 *
 * GET /api/users?page=2&perPage=50
 * - Will automatically set $request->page = 2 and $request->perPage = 50
 * - Calling $request->getPagination() returns Pagination with offset=50, limit=50
 *
 * GET /api/users
 * - Will use defaults: page=1, perPage=20
 * - Calling $request->getPagination() returns Pagination with offset=0, limit=20
 *
 * GET /api/users?page=3
 * - Will use: page=3, perPage=20 (default)
 * - Calling $request->getPagination() returns Pagination with offset=40, limit=20
 */
