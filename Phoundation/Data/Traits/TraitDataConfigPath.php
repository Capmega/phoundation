<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataConfigPath
 *
 * This adds config_path state registration to objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataConfigPath
{
    /**
     * If specified tracks from which configuration path this DataEntry object can be loaded
     *
     * @var ?string $config_path
     */
    protected ?string $config_path = null;


    /**
     * Returns which configuration path this DataEntry object can be loaded, if any.
     *
     * Returns NULL if this object cannot be loaded from configuration
     *
     * @return string|null
     */
    public function getConfigPath(): ?string
    {
        return $this->config_path;
    }


    /**
     * Sets which configuration path this DataEntry object can be loaded, if any.
     *
     * Set NULL if this object cannot be loaded from configuration
     *
     * @param string|null $config_path
     * @return static
     */
    public function setConfigPath(?string $config_path): static
    {
        $this->config_path = $config_path;
        return $this;
    }
}
