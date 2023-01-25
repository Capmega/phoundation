<?php

namespace Phoundation\Developer;



/**
 * Sync Page
 *
 * This class contains functionalities to sync different environment with each other, facilitating development work that
 * sometimes requires to work with production data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Sets the target (or source) environment where to sync to/from
     *
     * @var string|null $environment
     */
    protected ?string $environment = null;



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
     * Returns the environment to work with
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }



    /**
     * Sets the environment to work with
     *
     * @param string $environment
     * @return Sync
     */
    public function setEnvironment(string $environment): Sync
    {
        $this->environment = $environment;
        return $this;
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
        $this->unlockDatabases();
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
        $this->lockSystem();
        $this->lockDatabases();
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
    protected function lockDatabases(): void
    {

    }



    /**
     * Lock all databases in readonly for this projects so that we can dump them
     *
     * @return void
     */
    protected function unlock(): void
    {
        $this->unlockSystem();
        $this->unlockDatabases();
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
    protected function unlockDatabases(): void
    {

    }



    /**
     * Dumps all the databases for this project
     *
     * @return void
     */
    protected function dumpDatabases(): void
    {
        $this->lock();
        $this->dumpSql();
        $this->dumpMongo();
        $this->dumpRedis();
        $this->unlock();
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
    protected function dumpMongo(): void
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