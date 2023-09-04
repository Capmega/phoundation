<?php

namespace Phoundation\Templates\Interfaces;

use Phoundation\Storage\Interfaces\PageInterface;


/**
 * Interface TemplateInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Templates
 */
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
