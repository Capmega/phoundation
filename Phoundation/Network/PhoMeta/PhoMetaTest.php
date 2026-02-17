<?php

/**
 * Class PhoMetaTest
 *
 *
 * @todo      Update this to extend DataEntry instead of EntryCore, adjust DataEntry to support redis, mongo, etc
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\PhoMeta;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryData;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestNoDatabaseException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestNoUUIDException;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaTestInterface;


class PhoMetaTest extends DataEntry implements PhoMetaTestInterface
{
    use TraitDataEntryData;

    /**
     * PhoMetaTest class constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->meta_enabled = false;
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'network_tests';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Phoundation network test metadata');
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
     * @return static
     */
    public function setComponent(?string $component): static
    {
        return $this->set($component, 'component');
    }


    /**
     * Returns the connector_name property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getConnectorName(): ?string
    {
        return $this->get('connector_name');
    }


    /**
     * Sets the connector_name property for this PhoMetaTest object
     *
     * @param string|null $connector_name
     *
     * @return static
     */
    public function setConnectorName(?string $connector_name): static
    {
        return $this->set($connector_name, 'connector_name');
    }


    /**
     * Returns the database_name property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getDatabaseName(): ?string
    {
        return $this->get('database_name');
    }


    /**
     * Sets the database_name property for this PhoMetaTest object
     *
     * @param string|int|null $database_name
     *
     * @return static
     */
    public function setDatabaseName(string|int|null $database_name): static
    {
        return $this->set((string) $database_name, 'database_name');
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
     * @return static
     */
    public function setKey(?string $key): static
    {
        if ($key == null) {
            return $this;
        }

        return $this->set($key, 'key');
    }


    /**
     * Returns the meta_id property for this PhoMetaTest object
     *
     * @return int|null
     */
    public function getMetaId(): ?int
    {
        return $this->get('meta_id');
    }


    /**
     * Sets the meta_id property for this PhoMetaTest object
     *
     * @param int|null $meta_id
     *
     * @return static
     */
    public function setMetaId(?int $meta_id): static
    {
        if ($meta_id == null) {
            return $this;
        }

        return $this->set($meta_id, 'meta_id');
    }


    /**
     * Returns the duration property for this PhoMetaTest object
     *
     * @return float|null
     */
    public function getDuration(): ?float
    {
        return $this->get('duration');
    }


    /**
     * Sets the duration property for this PhoMetaTest object
     *
     * @param float|null $duration
     *
     * @return static
     */
    public function setDuration(?float $duration): static
    {
        if ($duration == null) {
            return $this;
        }

        return $this->set($duration, 'duration');
    }


    /**
     * Records a test entry into a database, with all info specified in a PhoMetaTest object
     * Returns true if saved successfully
     *
     * @return static
     */
    public function finish(): static
    {
        $component          = $this->getComponent();
        $key                = get_null($this->getKey());
        $database_connector = get_null($this->getConnectorName());
        $database_selector  = get_null($this->getDatabaseName());

        if ($key == null) {
            throw PhoMetaTestNoUUIDException::new(tr('UUID Missing from PhoMetaTest source'));
        }

        if (($database_connector or $database_selector)  == null) {
            throw PhoMetaTestNoDatabaseException::new(tr('Database Info Missing from PhoMetaTest source'));
        }

        $_connector = Connector::new($database_connector)->setDatabase($database_selector);

        Log::action(ts('Saving key ":key" in database ":connector" at ":domain::port" database number ":db_number" for HL7 component ":component"', [
            ':key'       => $key,
            ':connector' => $database_connector,
            ':domain'    => $_connector->getHostname(),
            ':port'      => $_connector->getPort(),
            ':db_number' => $database_selector,
            ':component' => $component,

        ]));

        Redis::new($_connector)->set($component, $key)->close();

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->add(DefinitionFactory::newVariable('component')
                                             ->setMaxLength(32)
                                             ->setLabel('Tested component'))

                      ->add(DefinitionFactory::newVariable('connector_name')
                                           ->setMaxLength(64)
                                           ->setLabel('Connector'))

                      ->add(DefinitionFactory::newCode('database_name')
                                           ->setMinLength(1)
                                           ->setMaxLength(64)
                                           ->setLabel('Database'))

                    ->add(DefinitionFactory::newCode('key')
                                           ->setMaxLength(64)
                                           ->setLabel('Test UUID'))

                    ->add(DefinitionFactory::newNumber('duration')
                                           ->setMin(0)
                                           ->setLabel('Test duration in microseconds'));

        return $this;
    }
}
