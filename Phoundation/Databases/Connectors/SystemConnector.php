<?php

/**
 * SystemConnector class
 *
 * This class represents the system connector, the one connector that always exists and is always active
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Connectors;


class SystemConnector extends Connector
{
    /**
     * SystemConnector class constructor
     */
    public function __construct()
    {
        $this->connector = 'system';

        parent::__construct('system', false, false);

        $source = $this->loadFromConfiguration(static::getConfigurationPath(), 'system');

        $this->setSource($source)
             ->setReadonly(true);
    }


    /**
     * Returns the configuration path for this DataEntry object, if it has one, or NULL instead
     *
     * @return string|null
     */
    public static function getConfigurationPath(): ?string
    {
        return 'databases.connectors';
    }


    /**
     * @inheritDoc
     */
    public function getConnector(): string
    {
        return 'system';
    }


    /**
     * @inheritDoc
     */
    public function getConnectorObject(): static
    {
        return $this;
    }
}
