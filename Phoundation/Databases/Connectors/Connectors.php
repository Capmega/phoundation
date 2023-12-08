<?php

namespace Phoundation\Databases\Connectors;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Utils\Config;


/**
 * SqlConnectors class
 *
 * This class represents a list of SqlConnector objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Connectors extends DataList implements ConnectorsInterface
{
    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'databases_connectors';
    }

    /**
     * @inheritDoc
     */
    public static function getEntryClass(): string
    {
        return Connector::class;
    }

    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * Load the id list from the database
     *
     * @param bool $clear
     * @return $this
     */
    public function load(bool $clear = true): static
    {
        parent::load($clear);

        // Load connectors from the configuration
        $connectors = Config::getArray('databases.sql.connectors');

        foreach ($connectors as $name => &$connector) {
            $this->source[$name] = Connector::fromSource($connector);
        }

        return $this;
    }
}