<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Stringable;

/**
 * Trait DataEntryRedirect
 *
 * This trait contains methods for DataEntry objects that requires a redirect
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryRedirect
{
    /**
     * Returns the redirect for this object
     *
     * @return string|null
     */
    public function getRedirect(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'redirect');
    }


    /**
     * Sets the redirect for this object
     *
     * @param Stringable|string|null $redirect
     * @return static
     */
    public function setRedirect(Stringable|string|null $redirect): static
    {
        return $this->setSourceValue('redirect', (string) $redirect);
    }
}
