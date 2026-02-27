<?php

/**
 * Trait TraitDataEntryRedirect
 *
 * This trait contains methods for DataEntry objects that requires a redirect
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;
use Stringable;


trait TraitDataEntryRedirect
{
    /**
     * Returns the redirect for this object
     *
     * @return string|null
     */
    public function getRedirect(): ?string
    {
        return $this->getTypesafe('string', 'redirect');
    }


    /**
     * Sets the redirect for this object
     *
     * @param Stringable|string|null $redirect
     *
     * @return static
     */
    public function setRedirect(Stringable|string|null $redirect): static
    {
        return $this->set(get_null((string) $redirect), 'redirect');
    }


    /**
     * Returns the redirect object for this object
     *
     * @return UrlInterface|null
     */
    public function getRedirectObject(): ?UrlInterface
    {
        return Url::newOrNull($this->getTypesafe('string', 'redirect'));
    }


    /**
     * Sets the redirect object for this object
     *
     * @param UrlInterface|null $redirect
     *
     * @return static
     */
    public function setRedirectObject(UrlInterface|null $redirect): static
    {
        return $this->set(get_null((string) $redirect), 'redirect');
    }
}
