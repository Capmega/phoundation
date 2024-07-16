<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

interface CliFormInterface
{
    /**
     * Displays a CLI form for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     *
     * @return static
     */
    public function displayCliForm(?string $key_header = null, ?string $value_header = null): static;
}
