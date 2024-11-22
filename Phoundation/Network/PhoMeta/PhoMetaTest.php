<?php

/**
 * Class PhoMetaTest
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Network\PhoMeta;

use Phoundation\Core\Core;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryHostnamePort;
use Phoundation\Data\Entry;
use Phoundation\Data\EntryCore;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Data\Traits\TraitDataHostnamePort;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestNoDatabaseException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestNoUUIDException;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaTestInterface;
use Phoundation\Utils\Config;

class PhoMetaTest extends EntryCore implements PhoMetaTestInterface
{
    /**
     * PhoMetaTest class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null) {
        if (!empty($source)) {
            $this->setSource($source);
        }
    }


    /**
     * Returns a new PhoMetaTest object
     *
     * @param ArrayableInterface|array|null $source
     *
     * @return PhoMetaTestInterface
     */
    public static function new(ArrayableInterface|array|null $source = null): PhoMetaTestInterface
    {
        return new static($source);
    }


    /**
     * Returns the component property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getComponent(): ?string
    {
        return $this->get('component');
    }


    /**
     * Sets the component property for this PhoMetaTest object
     *
     * @param string|null $component
     *
     * @return $this
     */
    public function setComponent(?string $component): static
    {
        Log::checkpoint("set component to " . $component);
        if ($component == null) {
            return $this;
        }

        return $this->set($component, 'component');
    }


    /**
     * Returns the database_connector property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getDatabaseConnector(): ?string
    {
        return $this->get('database_connector');
    }


    /**
     * Sets the database_connector property for this PhoMetaTest object
     *
     * @param string|null $database_connector
     *
     * @return $this
     */
    public function setDatabaseConnector(?string $database_connector): static
    {
        if ($database_connector == null) {
            return $this;
        }

        return $this->set($database_connector, 'database_connector');
    }


    /**
     * Returns the database_selector property for this PhoMetaTest object
     *
     * @return string|int|null
     */
    public function getDatabaseSelector(): string|int|null
    {
        return $this->get('database_selector');
    }


    /**
     * Sets the database_selector property for this PhoMetaTest object
     *
     * @param string|int|null $database_selector
     *
     * @return $this
     */
    public function setDatabaseSelector(string|int|null $database_selector): static
    {
        if ($database_selector == null) {
            return $this;
        }

        return $this->set($database_selector, 'database_selector');
    }


    /**
     * Returns the action property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->get('action');
    }


    /**
     * Sets the action property for this PhoMetaTest object
     *
     * @param string|null $action
     *
     * @return $this
     */
    public function setAction(?string $action): static
    {
        if ($action == null) {
            return $this;
        }

        return $this->set($action, 'action');
    }


    /**
     * Returns the key property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->get('key');
    }


    /**
     * Sets the key property for this PhoMetaTest object
     *
     * @param string|null $key
     *
     * @return $this
     */
    public function setKey(?string $key): static
    {
        if ($key == null) {
            return $this;
        }

        return $this->set($key, 'key');
    }


    /**
     * Records a test entry into a database, with all info specified in a PhoMetaTest object
     *
     * @return static
     */
    public function recordTest(): static
    {
        $component          = $this->getComponent();
        $key                = get_null($this->getKey());
        $database_connector = get_null($this->getDatabaseConnector());
        $database_selector  = get_null($this->getDatabaseSelector());

        if ($key == null) {
            throw PhoMetaTestNoUUIDException::new(tr('UUID Missing from PhoMetaTest source'));
        }

        if (($database_connector or $database_selector)  == null) {
            throw PhoMetaTestNoDatabaseException::new(tr('Database Info Missing from PhoMetaTest source'));
        }

        $connector   = Config::get('databases.connectors.' . $database_connector);
        $o_connector = Connector::new($connector)->setDatabase($database_selector);

        Log::action(tr('Now recording key ":key" in ":connector" database at ":domain::port" db ":db_number" for component ":component"', [
            ':key'       => $key,
            ':connector' => $database_connector,
            ':domain'    => $o_connector->getHostname(),
            ':port'      => (string) $o_connector->getPort(),
            ':db_number' => (string) $database_selector,
            ':component' => $component,

        ]));

        Redis::new($o_connector)->set($component, $key)->close();

        return $this;
    }
}