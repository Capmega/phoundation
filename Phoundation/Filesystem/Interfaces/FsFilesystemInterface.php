<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

interface FsFilesystemInterface
{
    /**
     * Returns the total space in bytes for this filesystem
     *
     * @return int
     */
    public function getTotalSpace(): int;

    /**
     * Returns the available space in bytes for this filesystem
     *
     * @return int
     */
    public function getAvailableSpace(): int;

    /**
     * Returns the used space in bytes for this filesystem
     *
     * @return int
     */
    public function getUsedSpace(): int;
}
