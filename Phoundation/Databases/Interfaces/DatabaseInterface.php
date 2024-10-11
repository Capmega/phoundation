<?php

declare(strict_types=1);

namespace Phoundation\Databases\Interfaces;

use Phoundation\Filesystem\Interfaces\FsFileInterface;


interface DatabaseInterface extends DatastoreInterface
{
    /**
     * Connects to this database and executes a test query
     *
     * @param FsFileInterface $file
     *
     * @return static
     */
    public function import(FsFileInterface $file): static;


    /**
     * Connects to this database and executes a test query
     *
     * @param FsFileInterface $file
     *
     * @return static
     */
    public function export(FsFileInterface $file): static;
}
