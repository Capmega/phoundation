<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks\Interfaces;

interface HookInterface
{
    /**
     * Attempts to execute the specified hooks
     *
     * @param string     $hook
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function execute(string $hook, ?array $arguments = null): mixed;
}
