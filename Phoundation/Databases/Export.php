<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataConnector;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Data\Traits\DataDriver;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataHost;
use Phoundation\Data\Traits\DataPort;
use Phoundation\Data\Traits\DataTimeout;
use Phoundation\Data\Traits\DataUserPass;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Os\Processes\Commands\Databases\MysqlDump;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


/**
 * Class Export
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Export
{
    use DataTimeout;
    use DataDriver;
    use DataPort;
    use DataHost;
    use DataUserPass;
    use DataDebug;
    use DataConnector;
    use DataRestrictions;


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
     * If enabled, will gzip the dump file
     *
     * @var bool $gzip
     */
    protected bool $gzip = true;


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
     * Returns a new Export object
     *
     * @param RestrictionsInterface|null $restrictions
     * @return static
     */
    public static function new(?RestrictionsInterface $restrictions = null): static
    {
        return new static($restrictions);
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
     * @return static
     */
    public function setDumpDate(bool $dump_date): static
    {
        $this->dump_date = $dump_date;
        return $this;
    }


    /**
     * Returns if dump file will be gzipped
     *
     * @return bool
     */
    public function getGzip(): bool
    {
        return $this->gzip;
    }


    /**
     * Sets if dump file will be gzipped
     *
     * @param bool $gzip
     * @return static
     */
    public function setGzip(bool $gzip): static
    {
        $this->gzip = $gzip;
        return $this;
    }


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param string|null $file
     * @param EnumExecuteMethodInterface $method
     * @return string
     * @throws SqlExceptionInterface
     */
    public function dump(?string $file, EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): string
    {
        switch ($this->driver) {
            case null:
                throw new OutOfBoundsException(tr('No export driver specified'));

            case 'mysql':
                Log::information(tr('Exporting to MySQL dump file ":file" from databases ":database", this may take a while...', [
                    ':file'     => $file,
                    ':database' => Strings::force($this->database, ', '),
                ]));

                return MysqlDump::new($this->restrictions)
                    ->setConnector($this->connector)
                    ->setTimeout($this->timeout)
                    ->setDatabases($this->database)
                    ->dump($file);

            default:
                throw new UnderConstructionException();
        }
    }
}
