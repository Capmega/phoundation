<?php

/**
 * Trait TraitDataUrlObject
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
use Phoundation\Web\Http\Url;


trait TraitDataUrlObject
{
    /**
     * The url for this object
     *
     * @var UrlInterface|null $_url
     */
    protected ?UrlInterface $_url = null;


    /**
     * Returns the url
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->_url->getSource();
    }


    /**
     * Sets the url
     *
     * @param string|null $url
     *
     * @return static
     */
    public function setUrl(?string $url): static
    {
        $this->_url = Url::new($url);
        return $this;
    }


    /**
     * Returns the url
     *
     * @return UrlInterface|null
     */
    public function getUrlObject(): ?UrlInterface
    {
        return $this->_url;
    }


    /**
     * Sets the url
     *
     * @param UrlInterface|string|null $_url
     *
     * @return static
     */
    public function setUrlObject(UrlInterface|string|null $_url): static
    {
        if (is_string($_url)) {
            $_url = new Url($_url);
        }

        $this->_url = get_null($_url);
        return $this;
    }
}
