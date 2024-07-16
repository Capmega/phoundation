<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks\Interfaces;

interface HookInterface
{
    /**
     * Attempts to execute the specified hooks
     *
     * @param array|string $hooks
     *
     * @return $this
     */
    public function execute(array|string $hooks, ?array $source = null): static;
}
