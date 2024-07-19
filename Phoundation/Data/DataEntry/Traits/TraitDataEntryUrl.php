<?php

/**
 * Trait TraitDataEntryUrl
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Stringable;

trait TraitDataEntryUrl
{
    /**
     * Returns the url for this object
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->getTypesafe('string', 'url');
    }


    /**
     * Sets the url for this object
     *
     * @param Stringable|string|null $url
     *
     * @return static
     */
    public function setUrl(Stringable|string|null $url): static
    {
        return $this->set((string) $url, 'url');
    }
}
