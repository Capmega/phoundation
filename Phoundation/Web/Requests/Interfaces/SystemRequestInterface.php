<?php

namespace Phoundation\Web\Requests\Interfaces;

use Throwable;


interface SystemRequestInterface
{
    /**
     * @param int            $http_code
     * @param Throwable|null $e
     * @param string|null    $message
     *
     * @return never
     */
    public function execute(int $http_code, ?Throwable $e = null, ?string $message = null): never;
}
