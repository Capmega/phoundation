<?php

/**
 * Trait TraitDataStringVersion
 *
 * This trait adds support for managing a $version string in your class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Traits;


trait TraitDataStringVersion
{
    /**
     * The path to use
     *
     * @var string|null $version
     */
    protected ?string $version = null;


    /**
     * Returns the version file
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }


    /**
     * Sets the version file
     *
     * @param string|null $version
     *
     * @return static
     */
    public function setVersion(?string $version): static
    {
        $this->version = $version;
        return $this;
    }
}
