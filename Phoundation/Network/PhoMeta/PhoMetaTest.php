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
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaTestInterface;

class PhoMetaTest extends DataEntry implements PhoMetaTestInterface
{
    use TraitDataEntryHostnamePort;


    /**
     * PhoMetaTest class constructor
     *
     * @param int|array|string|DataEntryInterface|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(int|array|string|DataEntryInterface|null $identifier = null, ?bool $meta_enabled = null, bool $init = true) {

    Log::checkpoint();

        parent::__construct($identifier, $meta_enabled, $init);

    Log::checkpoint();

        $this->setService("test1")
            ->setDatabaseConnector("test2")
            ->setDatabaseSelector("test3")
            ->setHostname("test4")
            ->setPort(5)
            ->setKey("test6");
    }


    /**
     * Returns the service property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getService(): ?string
    {
        return $this->getTypesafe('string', 'service');
    }


    /**
     * Sets the service property for this PhoMetaTest object
     *
     * @param string|null $service
     *
     * @return $this
     */
    public function setService(?string $service): static
    {
        if ($service == null) {
            return $this;
        }

        return $this->set($service, 'service');
    }


    /**
     * Returns the database_connector property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getDatabaseConnector(): ?string
    {
        return $this->getTypesafe('string', 'database_connector');
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
        return $this->getTypesafe('string', 'database_selector');
    }


    /**
     * Sets the database_selector property for this PhoMetaTest object
     *
     * @param string|null $database_selector
     *
     * @return $this
     */
    public function setDatabaseSelector(?string $database_selector): static
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
        return $this->getTypesafe('string', 'action');
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
        return $this->getTypesafe('string', 'key');
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
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'network_meta_test_meta';
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
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newCode($this, 'database_connector')
                                           ->setMaxlength(32)
                                           ->setLabel('Database connector driver'))

                    ->add(DefinitionFactory::newCode($this, 'database_selector')
                                           ->setMaxlength(32)
                                           ->setLabel('Identify which database (int or string) to use'))

                    ->add(DefinitionFactory::newCode($this, 'hostname')
                                          ->setMaxlength(32)
                                          ->setLabel('The hostname to connect to the database on'))

                    ->add(DefinitionFactory::newCode($this, 'port')
                                           ->setMaxlength(32)
                                           ->setLabel('The hostname to connect to the database on'))

                    ->add(DefinitionFactory::newCode($this, 'service')
                                           ->setMaxlength(32)
                                           ->setLabel('Which service this test is meant to stop at'))

                    ->add(DefinitionFactory::newCode($this, 'action')
                                           ->setMaxlength(32)
                                           ->setLabel('The action of this test'))

                    ->add(DefinitionFactory::newCode($this, 'key')
                                           ->setMaxlength(64)
                                           ->setLabel('UUID for this test object. Will be used to check if test was successful'));
    }
}
