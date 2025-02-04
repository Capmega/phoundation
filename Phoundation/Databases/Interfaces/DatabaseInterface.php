<?php

declare(strict_types=1);

namespace Phoundation\Databases\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;


interface DatabaseInterface extends DatastoreInterface
{
    /**
     * Returns true if this database interface is connected to a database server
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Connects to this database and executes a test query
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function import(PhoFileInterface $file): static;


    /**
     * Connects to this database and executes a test query
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function export(PhoFileInterface $file): static;
}
