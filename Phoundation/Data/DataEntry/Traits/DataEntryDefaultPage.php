<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Phoundation\Web\Http\UrlBuilder;

/**
 * Trait DataEntryDefaultPage
 *
 * This trait contains methods for DataEntry objects that require a default_page
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryDefaultPage
{
    /**
     * Returns the default_page for this object
     *
     * @return string|null
     */
    public function getDefaultPage(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'default_page');
    }


    /**
     * Sets the default_page for this object
     *
     * @param string|null $default_page
     * @return static
     */
    public function setDefaultPage(?string $default_page): static
    {

        return $this->setSourceValue('default_page', $default_page ? (string) UrlBuilder::getWww($default_page) : null);
    }
}
