<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataDriver;
use Phoundation\Data\Traits\TraitDataGzip;
use Phoundation\Data\Traits\TraitDataHost;
use Phoundation\Data\Traits\TraitDataPort;
use Phoundation\Data\Traits\TraitDataTimeout;
use Phoundation\Data\Traits\TraitDataUserPass;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Commands\Databases\MysqlDump;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Utils\Strings;

/**
 * Class Export
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */
class Export
{
    use TraitDataGzip;
    use TraitDataTimeout;
    use TraitDataDriver;
    use TraitDataPort;
    use TraitDataHost;
    use TraitDataUserPass;
    use TraitDataDebug;
    use TraitDataConnector {
        setConnector as __setConnector;
    }
    use TraitDataRestrictions;

    /**
     * The databases that will be dumped
     *
     * @var string|null $database
     */
    protected ?string $database = null;

    /**
     * The tables that will be dumped
     *
     * @var array $tables
     */
    protected array $tables = [];

    /**
     * If true disables keys on import
     *
     * @var bool $disable_keys
     */
    protected bool $disable_keys = true;

    /**
     * If true will dump stored procedures as well. This option requires the global SELECT privilege.
     *
     * @var bool $routines
     */
    protected bool $routines = true;

    /**
     * If true will include Event Scheduler events for the dumped databases in the output. This option requires the
     * EVENT privileges for those databases.
     *
     * @var bool $events
     */
    protected bool $events = true;

    /**
     * If true will add CREATE DATABASE statements
     *
     * @var bool $create_databases
     */
    protected bool $create_databases = false;

    /**
     * If true will add CREATE TABLE statements
     *
     * @var bool $create_tables
     */
    protected bool $create_tables = true;

    /**
     * Write INSERT statements using multiple-row syntax that includes several VALUES lists. This results in a smaller
     * dump file and speeds up inserts when the file is reloaded.
     *
     * @var bool $extended_insert
     */
    protected bool $extended_insert = true;

    /**
     * If the comments option is enabled, mysqldump produces a comment at the end of the dump of the following form:
     *
     * -- Dump completed on DATE
     *
     * @var bool $dump_date
     */
    protected bool $dump_date = true;

    /**
     * Write additional information in the dump file such as program version, server version, and host.
     *
     * @var bool $comments
     */
    protected bool $comments = true;


    /**
     * Exporter class constructor
     *
     * @param RestrictionsInterface|null $restrictions
     */
    public function __construct(?RestrictionsInterface $restrictions = null)
    {
        $this->restrictions = Restrictions::default($restrictions, Restrictions::writable('/', 'Mysql exporter'));
    }


    /**
     * Returns the databases that will be dumped
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }


    /**
     * Sets the databases that will be dumped
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
     * Returns if for each table, surround the INSERT statements with /*!40000 ALTER TABLE tbl_name DISABLE KEYS * /;
     * and / *!40000 ALTER TABLE tbl_name ENABLE KEYS * /; statements. This makes loading the dump file faster because
     * the indexes are created after all rows are inserted. This option is effective only for nonunique indexes of
     * MyISAM tables.
     *
     * @return bool
     */
    public function getDisableKeys(): bool
    {
        return $this->disable_keys;
    }


    /**
     * Sets if for each table, surround the INSERT statements with /*!40000 ALTER TABLE tbl_name DISABLE KEYS * /; and
     * / *!40000 ALTER TABLE tbl_name ENABLE KEYS * /; statements. This makes loading the dump file faster because the
     * indexes are created after all rows are inserted. This option is effective only for nonunique indexes of MyISAM
     * tables.
     *
     * @param bool $disable_keys
     *
     * @return static
     */
    public function setDisableKeys(bool $disable_keys): static
    {
        $this->disable_keys = $disable_keys;

        return $this;
    }


    /**
     * Returns if  stored routines (procedures and functions) will be included in the dumped databases in the output.
     * This option requires the global SELECT privilege.
     *
     * @return bool
     */
    public function getRoutines(): bool
    {
        return $this->routines;
    }


    /**
     * Sets if  stored routines (procedures and functions) will be included in the dumped databases in the output. This
     * option requires the global SELECT privilege.
     *
     * @param bool $routines
     *
     * @return static
     */
    public function setRoutines(bool $routines): static
    {
        $this->routines = $routines;

        return $this;
    }


    /**
     * Returns if Event Scheduler events are included for the dumped databases in the output. This option requires the
     * EVENT privileges for those databases.
     *
     * @return bool
     */
    public function getEvents(): bool
    {
        return $this->events;
    }


    /**
     * Sets if Event Scheduler events are included for the dumped databases in the output. This option requires the
     * EVENT privileges for those databases.
     *
     * @param bool $events
     *
     * @return static
     */
    public function setEvents(bool $events): static
    {
        $this->events = $events;

        return $this;
    }


    /**
     * Returns if the output file will contain CREATE DATABASE statements
     *
     * @return bool
     */
    public function getCreateDatabases(): bool
    {
        return $this->create_databases;
    }


    /**
     * Sets if the output file will contain CREATE DATABASE statements
     *
     * @param bool $create_databases
     *
     * @return static
     */
    public function setCreateDatabases(bool $create_databases): static
    {
        $this->create_databases = $create_databases;

        return $this;
    }


    /**
     * Returns if the output file will contain CREATE TABLE statements
     *
     * @return bool
     */
    public function getCreateTables(): bool
    {
        return $this->create_tables;
    }


    /**
     * Sets if the output file will contain CREATE TABLE statements
     *
     * @param bool $create_tables
     *
     * @return static
     */
    public function setCreateTables(bool $create_tables): static
    {
        $this->create_tables = $create_tables;

        return $this;
    }


    /**
     * Returns if writing INSERT statements using multiple-row syntax that includes several VALUES lists. This results
     * in a smaller dump file and speeds up inserts when the file is reloaded.
     *
     * @return bool
     */
    public function getExtendedInsert(): bool
    {
        return $this->extended_insert;
    }


    /**
     * Sets if writing INSERT statements using multiple-row syntax that includes several VALUES lists. This results
     * in a smaller dump file and speeds up inserts when the file is reloaded.
     *
     * @param bool $extended_insert
     *
     * @return static
     */
    public function setExtendedInsert(bool $extended_insert): static
    {
        $this->extended_insert = $extended_insert;

        return $this;
    }


    /**
     * Returns if additional information will be written in the dump file such as program version, server version,
     * and host.
     *
     * @return bool
     */
    public function getComments(): bool
    {
        return $this->comments;
    }


    /**
     * Sets if additional information will be written in the dump file such as program version, server version,
     * and host.
     *
     * @param bool $comments
     *
     * @return static
     */
    public function setComments(bool $comments): static
    {
        $this->comments = $comments;

        return $this;
    }


    /**
     * Returns if mysqldump produces a comment at the end of the dump, only if the comments option is enabled too
     *
     * @return bool
     */
    public function getDumpDate(): bool
    {
        return $this->dump_date;
    }


    /**
     * Sets if mysqldump produces a comment at the end of the dump, only if the comments option is enabled too
     *
     * @param bool $dump_date
     *
     * @return static
     */
    public function setDumpDate(bool $dump_date): static
    {
        $this->dump_date = $dump_date;

        return $this;
    }


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param string|null                $file
     * @param EnumExecuteMethodInterface $method
     *
     * @return string
     * @throws SqlExceptionInterface
     */
    public function dump(?string $file, EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): string
    {
        switch ($this->driver ?? $this->connector->getDriver()) {
            case null:
                throw new OutOfBoundsException(tr('No export driver specified'));
            case 'mysql':
                $file = MysqlDump::new($this->restrictions)
                                 ->setConnector($this->connector)
                                 ->setTimeout($this->timeout)
                                 ->setDatabases($this->database)
                                 ->dump($file, $method);
                Log::success(tr('Exported to MySQL dump file ":file" from databases ":database", this may take a while...', [
                    ':file'     => $file,
                    ':database' => Strings::force($this->database, ', '),
                ]));

                return $file;
            default:
                throw new UnderConstructionException();
        }
    }


    /**
     * Sets the source
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $ignore_sql_exceptions
     *
     * @return static
     */
    public function setConnector(ConnectorInterface|string|null $connector, bool $ignore_sql_exceptions = false): static
    {
        $this->__setConnector($connector, $ignore_sql_exceptions);
        if ($this->getDriver()) {
            // Driver was specified separately, must match driver for this connector
            if ($this->getDriver() !== $this->connector->getDriver()) {
                throw new OutOfBoundsException(tr('Specified connector is for driver ":connector", however a different driver ":driver" has already been specified separately', [
                    ':connector' => $this->connector->getDriver(),
                    ':driver'    => $this->getDriver(),
                ]));
            }

        } else {
            $this->setDriver($this->connector->getDriver());
        }

        return $this;
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
        if ($driver and $this->connector) {
            // Connector was specified separately, this driver must match connector driver
            if ($driver !== $this->connector->getDriver()) {
                throw new OutOfBoundsException(tr('Specified driver ":driver" does not match driver for already specified connector ":connector"', [
                    ':connector' => $this->connector->getDriver(),
                    ':driver'    => $driver,
                ]));
            }
        }
        $this->driver = get_null($driver);

        return $this;
    }


    /**
     * Returns a new Export object
     *
     * @param RestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public static function new(?RestrictionsInterface $restrictions = null): static
    {
        return new static($restrictions);
    }
}
