<?php

namespace Phoundation\Filesystem\Filesystems\Btrfs\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoPathInterface;

interface BtrfsInterface
{
    /**
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $_path): static;


    /**
     * Returns the version of the btrfs-progs
     *
     * @return string
     */
    public function getVersion(): string;


    /**
     * Formats the current path with a BTRFS filesystem
     *
     * @return static
     */
    public function format(): static;
}
