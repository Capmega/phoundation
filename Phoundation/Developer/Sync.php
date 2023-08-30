<?php

declare(strict_types=1);

namespace Phoundation\Developer;


/**
 * Sync Page
 *
 * This class contains functionalities to sync different environment with each other, facilitating development work that
 * sometimes requires to work with production data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Sync
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
     * @return Sync
     */
    public function setInit(bool $init): Sync
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
     * @return Sync
     */
    public function setLock(bool $lock): Sync
    {
        $this->lock = $lock;
        return $this;
    }


    /**
     * Sync from the specified environment to this environment
     *
     * @param string $environment
     * @return void
     */
    public function fromEnvironment(string $environment): void
    {
        $this->lock();
        $this->dumpDatabases();
        $this->unlockRemoteSqlDatabase();
        $this->copyDumps();
        $this->cleanDumps();
        $this->importDatabases();
        $this->moveContent();
        $this->copyContent();
        $this->init();
        $this->clearCaches();
    }


    /**
     * Sync from this environment to the specified environment
     *
     * @param string $environment
     * @return void
     */
    public function toEnvironment(string $environment): void
    {

    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function lock(): void
    {
        $this->lockRemoteSystem();
        $this->lockRemoteSqlDatabase();
    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function unlock(): void
    {
        $this->unlockRemoteSystem();
        $this->unlockRemoteSqlDatabase();
    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function lockRemoteSystem(): void
    {

    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function unlockRemoteSystem(): void
    {

    }


    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @param string $database
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
        $this->lock();
        $this->importSql();
        $this->importMongo();
        $this->importRedis();
        $this->unlock();
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
