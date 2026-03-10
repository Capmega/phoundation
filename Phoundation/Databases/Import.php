<?php

/**
 * Class Import
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
use Phoundation\Data\Traits\TraitDataUserPassword;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Enums\EnumSqlVendor;
use Phoundation\Date\PhoTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Commands\Databases\MySql;
use Phoundation\Utils\Numbers;


class Import
{
    use TraitDataTimeout;
    use TraitDataDriver;
    use TraitDataPort;
    use TraitDataHost;
    use TraitDataUserPassword;
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
     * @param PhoRestrictionsInterface|null $restrictions
     */
    public function __construct(?PhoRestrictionsInterface $restrictions = null)
    {
        $this->_restrictions = PhoRestrictions::getRestrictionsOrDefault($restrictions, PhoRestrictions::newWritable('/'));
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
        if ($this->_connector) {
            // Connector was specified separately, this driver must match connector driver
            if ($driver and ($driver !== $this->_connector->getDriver())) {
                throw new OutOfBoundsException(tr('Specified driver ":driver" does not match driver for already specified connector ":connector"', [
                    ':connector' => $this->_connector->getDriver(),
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
     * Imports the MySQL database
     *
     * @see https://kedar.nitty-witty.com/blog/a-unique-foreign-key-issue-in-mysql-8-4
     * @return static
     */
    public function import(): static
    {
        $this->_file->checkExists();
        switch ($this->driver) {
            case 'mysql':
                Log::information(ts('Importing ":size" MySQL dump file ":file" to database ":database", this may take a while...', [
                    ':size'     => Numbers::getHumanReadableAndPreciseBytes($this->_file->getSize()),
                    ':file'     => $this->_file->getRootname(),
                    ':database' => $this->database ?? $this->getConnectorObject()->getDatabase(),
                ]));

                sql()->disableRestrictFkOnNonStandardKeys();

                $_timer = MySql::new()
                               ->setTimeout($this->timeout)
                               ->setConnectorObject($this->getConnectorObject())
                               ->drop($this->drop ? ($this->database ?? ($this->getConnectorObject()->getDatabase())) : null)
                               ->create($this->database ?? $this->getConnectorObject()->getDatabase())
                               ->import($this->_file);

                Log::success(ts('Finished importing database ":database" in ":time"', [
                    ':database' => $this->database ?? $this->getConnectorObject()->getDatabase(),
                    ':time'     => $_timer->getDifference(),
                ]), 10);

                // Re-enable strict FK key checks on MySQL
                sql()->enableRestrictFkOnNonStandardKeys(function () {
                    // But first make sure that all non-UNIQUE indices are fixed!
                    Log::warning(ts('Detected MySQL version >8.4, checking and fixing any foreign key target columns without unique indexm this may take a second...'), 10);
                    sql()->fixFkOnNonStandardKeys();
                });

                Log::success(ts('Finished importing MySQL dump file ":file" to database ":database"', [
                    ':file'     => $this->_file,
                    ':database' => $this->database ?? $this->getConnectorObject()->getDatabase(),
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
     * @return static
     */
    public function setConnector(string $connector, ?string $database = null): static
    {
        $this->__setConnector($connector, $database)
             ->getConnectorObject();

        return $this;
    }


    /**
     * Sets the database connector object
     *
     * @param ConnectorInterface|null $_connector
     * @param string|int|null         $database
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $_connector, string|int|null $database = null): static
    {
        $this->__setConnectorObject($_connector, $database);

        if ($this->getDriver()) {
            // Driver was specified separately, must match driver for this connector
            if ($this->getDriver() !== $this->_connector->getDriver()) {
                throw new OutOfBoundsException(tr('Specified connector is for driver ":connector", however a different driver ":driver" has already been specified separately', [
                    ':connector' => $this->_connector->getDriver(),
                    ':driver'    => $this->getDriver(),
                ]));
            }

        } else {
            $this->driver = get_null($this->_connector->getDriver());
        }

        return $this;
    }


    /**
     * Returns a new Export object
     *
     * @param PhoRestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public static function new(?PhoRestrictionsInterface $restrictions = null): static
    {
        return new static($restrictions);
    }
}
