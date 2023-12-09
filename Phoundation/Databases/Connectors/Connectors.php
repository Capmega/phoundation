<?php

namespace Phoundation\Databases\Connectors;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Seo\Seo;
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
     * DataList class constructor
     */
    public function __construct(?array $ids = null)
    {
        $this->query = 'SELECT * FROM `databases_connectors`';
    }


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

        // Get connectors from the configuration
        $connectors = Config::getArray(Connector::new()->getConfigPath());
        $count      = 0;

        // Load all connectors by type
        foreach ($connectors as $type => $type_connectors) {
            foreach ($type_connectors as $name => &$connector) {
                $connector['id']       = --$count;
                $connector['type']     = $type;
                $connector['name']     = $name;
                $connector['seo_name'] = Seo::string($name);

                $this->source[$count] = Connector::fromSource($connector);
            }
        }

        return $this;
    }
}