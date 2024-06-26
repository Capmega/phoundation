<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;

/**
 * Class MysqlDump
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
interface MysqlDumpInterface
{
    /**
     * Returns if for each table, surround the INSERT statements with /*!40000 ALTER TABLE tbl_name DISABLE KEYS * /;
     * and / *!40000 ALTER TABLE tbl_name ENABLE KEYS * /; statements. This makes loading the dump file faster because
     * the indexes are created after all rows are inserted. This option is effective only for nonunique indexes of
     * MyISAM tables.
     *
     * @return bool
     */
    public function getDisableKeys(): bool;


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
    public function setDisableKeys(bool $disable_keys): static;


    /**
     * Returns if  stored routines (procedures and functions) will be included in the dumped databases in the output.
     * This option requires the global SELECT privilege.
     *
     * @return bool
     */
    public function getRoutines(): bool;


    /**
     * Sets if  stored routines (procedures and functions) will be included in the dumped databases in the output. This
     * option requires the global SELECT privilege.
     *
     * @param bool $routines
     *
     * @return static
     */
    public function setRoutines(bool $routines): static;


    /**
     * Returns if Event Scheduler events are included for the dumped databases in the output. This option requires the
     * EVENT privileges for those databases.
     *
     * @return bool
     */
    public function getEvents(): bool;


    /**
     * Sets if Event Scheduler events are included for the dumped databases in the output. This option requires the
     * EVENT privileges for those databases.
     *
     * @param bool $events
     *
     * @return static
     */
    public function setEvents(bool $events): static;


    /**
     * Returns if the output file will contain CREATE DATABASE statements
     *
     * @return bool
     */
    public function getCreateDatabases(): bool;


    /**
     * Sets if the output file will contain CREATE DATABASE statements
     *
     * @param bool $create_databases
     *
     * @return static
     */
    public function setCreateDatabases(bool $create_databases): static;


    /**
     * Returns if the output file will contain CREATE TABLE statements
     *
     * @return bool
     */
    public function getCreateTables(): bool;


    /**
     * Sets if the output file will contain CREATE TABLE statements
     *
     * @param bool $create_tables
     *
     * @return static
     */
    public function setCreateTables(bool $create_tables): static;


    /**
     * Returns if writing INSERT statements using multiple-row syntax that includes several VALUES lists. This results
     * in a smaller dump file and speeds up inserts when the file is reloaded.
     *
     * @return bool
     */
    public function getExtendedInsert(): bool;


    /**
     * Sets if writing INSERT statements using multiple-row syntax that includes several VALUES lists. This results
     * in a smaller dump file and speeds up inserts when the file is reloaded.
     *
     * @param bool $extended_insert
     *
     * @return static
     */
    public function setExtendedInsert(bool $extended_insert): static;


    /**
     * Returns if additional information will be written in the dump file such as program version, server version,
     * and host.
     *
     * @return bool
     */
    public function getComments(): bool;


    /**
     * Sets if additional information will be written in the dump file such as program version, server version,
     * and host.
     *
     * @param bool $comments
     *
     * @return static
     */
    public function setComments(bool $comments): static;


    /**
     * Returns if mysqldump produces a comment at the end of the dump, only if the comments option is enabled too
     *
     * @return bool
     */
    public function getDumpDate(): bool;


    /**
     * Sets if mysqldump produces a comment at the end of the dump, only if the comments option is enabled too
     *
     * @param bool $dump_date
     *
     * @return static
     */
    public function setDumpDate(bool $dump_date): static;


    /**
     * Returns if dump file will be gzipped
     *
     * @return bool
     */
    public function getGzip(): bool;


    /**
     * Sets if dump file will be gzipped
     *
     * @param bool $gzip
     *
     * @return static
     */
    public function setGzip(bool $gzip): static;


    /**
     * ExecuteExecuteInterface the rsync operation and return the PID (background) or -1
     *
     * @param FsFileInterface|null       $file
     * @param EnumExecuteMethod $method
     *
     * @return string
     */
    public function dump(?FsFileInterface $file, EnumExecuteMethod $method = EnumExecuteMethod::passthru): string;
}
