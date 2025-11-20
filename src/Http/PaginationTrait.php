<?php

declare(strict_types=1);

namespace TeamMatePro\UseCaseBundle\Http;

use Symfony\Component\Validator\Constraints as Assert;
use TeamMatePro\Contracts\Collection\Pagination;
use TeamMatePro\UseCaseBundle\Validator\PatchValidation;

trait PaginationTrait
{
    #[PatchValidation([
        new Assert\Positive(),
        new Assert\Type('numeric'),
    ])]
    public string|int|null $page = null;

    #[PatchValidation([
        new Assert\Positive(),
        new Assert\Type('numeric'),
    ])]
    public string|int|null $perPage = null;

    public function getPagination(): Pagination
    {
        $page = $this->page !== null ? (int)$this->page : 1;
        $perPage = $this->perPage !== null ? (int)$this->perPage : 20;

        return Pagination::fromPage($page, $perPage);
    }
}
