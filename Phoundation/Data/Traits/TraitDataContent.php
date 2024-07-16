<?php

/**
 * Trait TraitDataContent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencontent.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataContent
{
    /**
     * The content to use
     *
     * @var string|null $content
     */
    protected ?string $content = null;


    /**
     * Returns the content
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }


    /**
     * Sets the content
     *
     * @param string|null $content
     *
     * @return static
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }
}