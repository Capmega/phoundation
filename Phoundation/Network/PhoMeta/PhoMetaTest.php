<?php

/**
 * Class PhoMetaTest
 *
 *
 * @todo      Update this to extend DataEntry instead of EntryCore, adjust DataEntry to support redis, mongo, etc
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Network\PhoMeta;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryData;
use Phoundation\Data\DataEntry\Traits\TraitDataEntrySetCreatedBy;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestNoDatabaseException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestNoUUIDException;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaTestInterface;
use Phoundation\Utils\Config;


class PhoMetaTest extends DataEntry implements PhoMetaTestInterface
{
    use TraitDataEntryData;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'network_test_meta';
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
     * @return $this
     */
    public function setComponent(?string $component): static
    {
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
        return $this->set($database_connector, 'database_connector');
    }


    /**
     * Returns the database_selector property for this PhoMetaTest object
     *
     * @return string|null
     */
    public function getDatabaseSelector(): ?string
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
        return $this->set((string) $database_selector, 'database_selector');
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
     * @return $this
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
     * @return $this
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
     * @return bool
     */
    public function saveTest(): bool
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

        Log::action(tr('Saving key ":key" in database ":connector" at ":domain::port" database number ":db_number" for component ":component"', [
            ':key'       => $key,
            ':connector' => $database_connector,
            ':domain'    => $o_connector->getHostname(),
            ':port'      => $o_connector->getPort(),
            ':db_number' => $database_selector,
            ':component' => $component,

        ]));

        Redis::new($o_connector)->set($component, $key)->close();

        return true;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newCode($this, 'component')
                                           ->setMaxlength(32)
                                           ->setLabel('Component being tested'))

                    ->add(DefinitionFactory::newCode($this, 'database_connector')
                                           ->setMaxlength(32)
                                           ->setLabel('Which database connector to use'))

                    ->add(DefinitionFactory::newCode($this, 'database_selector')
                                           ->setMaxlength(32)
                                           ->setLabel('Which specific database number to use'))

                    ->add(DefinitionFactory::newCode($this, 'key')
                                           ->setMaxlength(128)
                                           ->setLabel('The UUID for this PhoMetaTest'))

                    ->add(DefinitionFactory::newCode($this, 'duration')
                                           ->setMaxlength(64)
                                           ->setLabel('The duraction for this PhoMetaTest'));
    }
}
