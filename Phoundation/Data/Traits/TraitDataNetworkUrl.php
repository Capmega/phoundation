<?php

/**
 * Class TraitDataNetworkUrl
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Http\Interfaces\UrlInterface;

trait TraitDataNetworkUrl
{
    /**
     * @var string|null
     */
    protected ?string $url = null;


    /**
     * Returns the path for this object
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }


    /**
     * Set the path for this object
     *
     * @param UrlInterface|string $url
     *
     * @return static
     */
    public function setUrl(UrlInterface|string $url): static
    {
        $this->url = get_null((string) $url);

        return $this;
    }
}