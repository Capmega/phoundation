<?php

/**
 * Class Import
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Database
 */


declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataDriver;
use Phoundation\Data\Traits\TraitDataFile;
use Phoundation\Data\Traits\TraitDataHost;
use Phoundation\Data\Traits\TraitDataPort;
use Phoundation\Data\Traits\TraitDataTimeout;
use Phoundation\Data\Traits\TraitDataUserPass;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Commands\Databases\MySql;
use Phoundation\Utils\Numbers;


class Import
{
    use TraitDataTimeout;
    use TraitDataDriver;
    use TraitDataPort;
    use TraitDataHost;
    use TraitDataUserPass;
    use TraitDataDebug;
    use TraitDataFile;
    use TraitDataConnector {
        setConnector       as __setConnector;
        setConnectorObject as __setConnectorObject;
    }
    use TraitDataRestrictions;

    /**
     * The database that will be dumped
     *
     * @var string|null $database
     */
    protected ?string $database = null;

    /**
     * Tracks if the database should be dropped before import
     *
     * @var bool $drop
     */
    protected bool $drop = true;


    /**
     * Exporter class constructor
     *
     * @param FsRestrictionsInterface|null $restrictions
     */
    public function __construct(?FsRestrictionsInterface $restrictions = null)
    {
        $this->restrictions = FsRestrictions::getRestrictionsOrDefault($restrictions, FsRestrictions::newWritable('/'));
    }


    /**
     * Sets the driver
     *
     * @note Overrides trait DataDriver::setDriver()
     *
     * @param string|null $driver
     *
     * @return static
     */
    public function setDriver(?string $driver): static
    {
        if ($this->o_connector) {
            // Connector was specified separately, this driver must match connector driver
            if ($driver and ($driver !== $this->o_connector->getDriver())) {
                throw new OutOfBoundsException(tr('Specified driver ":driver" does not match driver for already specified connector ":connector"', [
                    ':connector' => $this->o_connector->getDriver(),
                    ':driver'    => $driver,
                ]));
            }

        } else {
            $this->driver = get_null($driver);
        }

        return $this;
    }


    /**
     * Sets if the database should be dropped before import
     *
     * @return bool
     */
    public function getDrop(): bool
    {
        return $this->drop;
    }


    /**
     * Sets if the database should be dropped before import
     *
     * @param bool $drop
     *
     * @return static
     */
    public function setDrop(bool $drop): static
    {
        $this->drop = $drop;

        return $this;
    }


    /**
     * Returns the database that will be imported
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }


    /**
     * Sets the database that will be imported
     *
     * @param string|null $database
     *
     * @return static
     */
    public function setDatabase(?string $database): static
    {
        $this->database = $database;

        return $this;
    }


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @return static
     */
    public function import(): static
    {
        switch ($this->driver) {
            case 'mysql':
                Log::information(tr('Importing ":size" MySQL dump file ":file" to database ":database", this may take a while...', [
                    ':size'     => Numbers::getHumanReadableAndPreciseBytes($this->file->getSize()),
                    ':file'     => $this->file->getRootname(),
                    ':database' => $this->database,
                ]));

                MySql::new()
                     ->setTimeout($this->timeout)
                     ->setConnectorObject($this->getConnectorObject())
                     ->drop($this->drop ? $this->database : null)
                     ->create($this->database)
                     ->import($this->file);

                Log::success(tr('Finished importing MySQL dump file ":file" to database ":database"', [
                    ':file'     => $this->file,
                    ':database' => $this->database,
                ]));
                break;

            case 'redis':
                // no break
            case 'mongo':
                // no break
            case 'mongodb':
                // no break
            case 'elastic':
                // no break
            case 'elasticsearch':
                // no break
                throw new UnderConstructionException();

            case null:
                throw new OutOfBoundsException(tr('Cannot import, no driver specified'));

            default:
                throw new OutOfBoundsException(tr('Cannot import, unknown driver ":driver" specified', [
                    ':driver' => $this->driver
                ]));
        }

        return $this;
    }


    /**
     * Sets the database connector by name, and initializes the connector object to ensure the driver is set as well
     *
     * @param string      $connector
     * @param string|null $database
     *
     * @return $this
     */
    public function setConnector(string $connector, ?string $database = null): static
    {
        $this->__setConnector($connector, $database)
             ->getConnectorObject();

        return $this;
    }


    /**
     * Sets the source
     *
     * @param ConnectorInterface $o_connector
     * @param string|null        $database
     *
     * @return static
     */
    public function setConnectorObject(ConnectorInterface $o_connector, ?string $database = null): static
    {
        $this->__setConnectorObject($o_connector, $database);

        if ($this->getDriver()) {
            // Driver was specified separately, must match driver for this connector
            if ($this->getDriver() !== $this->o_connector->getDriver()) {
                throw new OutOfBoundsException(tr('Specified connector is for driver ":connector", however a different driver ":driver" has already been specified separately', [
                    ':connector' => $this->o_connector->getDriver(),
                    ':driver'    => $this->getDriver(),
                ]));
            }

        } else {
            $this->driver = get_null($this->o_connector->getDriver());
        }

        return $this;
    }


    /**
     * Returns a new Export object
     *
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public static function new(?FsRestrictionsInterface $restrictions = null): static
    {
        return new static($restrictions);
    }
}
