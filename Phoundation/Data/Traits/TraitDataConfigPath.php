<?php

/**
 * Trait TraitDataConfigPath
 *
 * This adds config_path state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataConfigPath
{
    /**
     * If specified tracks from which configuration path this DataEntry object can be loaded
     *
     * @var ?string $configuration_path
     */
    protected ?string $configuration_path = null;


    /**
     * Returns which configuration path this DataEntry object can be loaded, if any.
     *
     * Returns NULL if this object cannot be loaded from configuration
     *
     * @return string|null
     */
    public function getConfigurationPath(): ?string
    {
        return $this->configuration_path;
    }


    /**
     * Sets which configuration path this DataEntry object can be loaded, if any.
     *
     * Set NULL if this object cannot be loaded from configuration
     *
     * @param string|null $configuration_path
     *
     * @return static
     */
    public function setConfigurationPath(?string $configuration_path): static
    {
        $this->configuration_path = $configuration_path;

        return $this;
    }
}
