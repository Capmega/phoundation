<?php

/**
 * Trait TraitDataEntryContent
 *
 * This trait contains methods for DataEntry objects that require a content
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Stringable;


trait TraitDataEntryContent
{
    /**
     * Returns the content for this object
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->getTypesafe('string', 'content');
    }


    /**
     * Sets the content for this object
     *
     * @param Stringable|string|float|int|null $content $content
     *
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content): static
    {
        return $this->set((string) $content, 'content');
    }
}
