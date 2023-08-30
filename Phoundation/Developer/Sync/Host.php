<?php

declare(strict_types=1);

namespace Phoundation\Developer\Sync;


/**
 * Host class
 *
 * This class contains functionalities to manage the remote host whilst syncing
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Host
{
    /**
     * Sets if the system will initialize after syncing
     *
     * @var bool $init
     */
    protected bool $init = true;

    /**
     * Sets if the system will use locking or not
     *
     * @var bool $lock
     */
    protected bool $lock = true;


    /**
     * Returns new Sync object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Returns if the system will initialize after syncing
     *
     * @return bool
     */
    public function getInit(): bool
    {
        return $this->init;
    }


    /**
     * Sets if the system will initialize after syncing
     *
     * @param bool $init
     * @return Remote
     */
    public function setInit(bool $init): Remote
    {
        $this->init = $init;
        return $this;
    }


    /**
     * Returns if the sync should use locking or not
     *
     * @return bool
     */
    public function getLock(): bool
    {
        return $this->lock;
    }


    /**
     * Sets if the sync should use locking or not
     *
     * @param bool $lock
     * @return Remote
     */
    public function setLock(bool $lock): Remote
    {
        $this->lock = $lock;
        return $this;
    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function lockSystem(): void
    {

    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function unlockSystem(): void
    {

    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function lockRemoteSqlDatabase(string $database): void
    {

    }


    /**
     * Lock specified SQL database in readonly for this projects so that we can dump them
     *
     * @param string $database
     * @return void
     */
    protected function unlockRemoteSqlDatabase(string $database): void
    {

    }


    /**
     * Dumps all the databases for this project
     *
     * @return void
     */
    protected function dumpDatabases(): void
    {
        $this->dumpAllSql();
        $this->dumpAllMongo();
        $this->dumpAllRedis();
    }


    /**
     * Dumps the SQL databases for this project
     *
     * @return void
     */
    protected function dumpAllSql(): void
    {

    }


    /**
     * Dumps the SQL databases for this project
     *
     * @return void
     */
    protected function dumpSql(): void
    {

    }


    /**
     * Dumps the Mongo databases for this project
     *
     * @return void
     */
    protected function dumpAllMongo(): void
    {

    }


    /**
     * Dumps the Mongo databases for this project
     *
     * @return void
     */
    protected function dumpMongo(): void
    {

    }


    /**
     * Dumps the Redis databases for this project
     *
     * @return void
     */
    protected function dumpAllRedis(): void
    {

    }


    /**
     * Dumps the Redis databases for this project
     *
     * @return void
     */
    protected function dumpRedis(): void
    {

    }


    /**
     * Copy all databases
     *
     * @return void
     */
    protected function copyDatabases(): void
    {

    }


    /**
     * Imports all the databases for this project
     *
     * @return void
     */
    protected function importDatabases(): void
    {
        $this->lockSystem();
        $this->importSql();
        $this->importMongo();
        $this->importRedis();
        $this->lockSystem();
    }


    /**
     * Imports the SQL databases for this project
     *
     * @return void
     */
    protected function importSql(): void
    {

    }


    /**
     * Imports the Mongo databases for this project
     *
     * @return void
     */
    protected function importMongo(): void
    {

    }


    /**
     * Imports the Redis databases for this project
     *
     * @return void
     */
    protected function importRedis(): void
    {

    }
}
