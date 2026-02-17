<?php

/**
 * Trait TraitDataRedirectUrlObject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Http\Interfaces\UrlInterface;


trait TraitDataRedirectUrlObject
{
    /**
     * The redirect_url for this object
     *
     * @var UrlInterface|null $_redirect_url
     */
    protected ?UrlInterface $_redirect_url = null;


    /**
     * Returns the URL as an object
     *
     * @return UrlInterface|null
     */
    public function getRedirectUrlObject(): ?UrlInterface
    {
        return $this->_redirect_url;
    }


    /**
     * Sets the redirect_url
     *
     * @param UrlInterface|null $_redirect_url
     *
     * @return static
     */
    public function setRedirectUrlObject(UrlInterface|null $_redirect_url): static
    {
        $this->_redirect_url = $_redirect_url;
        return $this;
    }
}
