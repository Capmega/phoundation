<?php

declare(strict_types=1);

namespace Phoundation\Templates\Interfaces;

use Phoundation\Storage\Interfaces\PageInterface;

interface TemplateInterface extends PageInterface
{
    /**
     * Returns the template text
     *
     * @return string|null
     */
    public function getText(): ?string;

    /**
     * Set the template text
     *
     * @param string|null $text
     * @return static
     */
    public function setText(?string $text): static;

    /**
     * @param array $source
     * @return string
     */
    public function render(array $source): string;
}
