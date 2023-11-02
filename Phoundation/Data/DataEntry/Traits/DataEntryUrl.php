<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Stringable;

/**
 * Trait DataEntryUrl
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryUrl
{
    /**
     * Returns the url for this object
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->getSourceFieldValue('string', 'url');
    }


    /**
     * Sets the url for this object
     *
     * @param Stringable|string|null $url
     * @return static
     */
    public function setUrl(Stringable|string|null $url): static
    {
        return $this->setSourceValue('url', (string) $url);
    }
}
