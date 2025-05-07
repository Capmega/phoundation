<?php

/**
 * Trait TraitDataUrl
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
use Stringable;


trait TraitDataUrl
{
    /**
     * The url for this object
     *
     * @var string|null $url
     */
    protected ?string $url = null;


    /**
     * Returns the url
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }


    /**
     * Returns the URL as an object
     *
     * @return UrlInterface|null
     */
    public function getUrlObject(): ?UrlInterface
    {
        return Url::new($this->url);
    }


    /**
     * Sets the url
     *
     * @param UrlInterface|string|null $url
     *
     * @return static
     */
    public function setUrl(UrlInterface|string|null $url): static
    {
        if ($url instanceof UrlInterface) {
            $url = $url->getSource();
        }

        $this->url = get_null($url);
        return $this;
    }
}
