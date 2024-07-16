<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project\Interfaces;

interface ProjectInterface
{
    /**
     * Updates your Phoundation installation
     *
     * @param string|null $branch
     * @param string|null $message
     * @param bool        $signed
     * @param string|null $phoundation_path
     *
     * @return static
     */
    public function updateLocalProject(?string $branch, ?string $message = null, bool $signed = false, ?string $phoundation_path = null): static;
}
