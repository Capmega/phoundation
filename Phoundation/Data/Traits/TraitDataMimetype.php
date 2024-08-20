<?php

/**
 * Trait TraitDataMimetype
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


trait TraitDataMimetype
{
    /**
     * The mimetype for this object
     *
     * @var string|null $mimetype
     */
    protected ?string $mimetype = null;


    /**
     * Returns the mimetype
     *
     * @return string|null
     */
    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }


    /**
     * Sets the mimetype
     *
     * @param string|null $mimetype
     *
     * @return static
     */
    public function setMimetype(string|null $mimetype): static
    {
        $this->mimetype = get_null((string) $mimetype);

        return $this;
    }
}
