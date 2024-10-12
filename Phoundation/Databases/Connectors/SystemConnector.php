<?php

/**
 * SystemConnector class
 *
 * This class represents the system connector, the one connector that always exists and is always active
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        $this->configuration_path = 'databases.connectors';
        $this->connector          = 'system';

        parent::__construct('system', false, false);

        $source = $this->loadFromConfiguration($this->configuration_path, 'system');

        $this->setSource($source)
             ->setReadonly(true);
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
