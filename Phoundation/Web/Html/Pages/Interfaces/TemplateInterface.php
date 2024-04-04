<?php

/**
 * Interface TemplateInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Templates
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Pages\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;

interface TemplateInterface extends RenderInterface
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
     *
     * @return static
     */
    public function setText(?string $text): static;
}
