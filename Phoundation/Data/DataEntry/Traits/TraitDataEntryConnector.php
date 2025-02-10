<?php

/**
 * Trait TraitDataEntryConnector
 *
 * This trait contains methods for DataEntry objects that require a connector
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryConnector
{
    use TraitDataEntryDatabase;


    /**
     * Returns the connector for this object
     *
     * @return string
     */
    public function getConnector(): string
    {
        return $this->getTypesafe('string', 'connector');
    }


    /**
     * Sets the connector for this object
     *
     * @param string|null $connector
     * @param string|null $database
     *
     * @return static
     */
    public function setConnector(?string $connector, ?string $database = null): static
    {
        if ($database) {
            return $this->set(get_null($connector), 'connector')
                        ->set(get_null($database) , 'database');
        }

        return $this->set(get_null($connector), 'connector');
    }
}
