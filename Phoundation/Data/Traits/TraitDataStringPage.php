<?php

/**
 * Trait TraitDataPage
 *
 * This trait adds support for a string containing a page
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataStringPage
{
    /**
     * Registers if this object is page or not
     *
     * @var string|null $page
     */
    protected ?string $page = null;


    /**
     * Returns if this object is page or not
     *
     * @return string|null
     */
    public function getPage(): ?string
    {
        return $this->page;
    }


    /**
     * Returns if this object is page or not
     *
     * @param string|null $page
     *
     * @return static
     */
    public function setPage(?string $page): static
    {
        $this->page = $page;
        return $this;
    }
}
