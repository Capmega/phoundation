<?php

/**
 * Trait TraitDataEntryConnector
 *
 * This trait contains methods for DataEntry objects that require a connector
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryConnector
{
    /**
     * Returns the connector for this object
     *
     * @return string|null
     */
    public function getConnector(): ?string
    {
        return $this->getTypesafe('string', 'connector');
    }


    /**
     * Sets the connector for this object
     *
     * @param string|null $connector
     *
     * @return static
     */
    public function setConnector(?string $connector): static
    {
        return $this->set($connector, 'connector');
    }
}
