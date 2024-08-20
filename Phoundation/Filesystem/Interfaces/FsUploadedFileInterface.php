<?php

namespace Phoundation\Filesystem\Interfaces;

interface FsUploadedFileInterface extends FsFileInterface
{
    /**
     * Returns the error code for this file
     *
     * @return int
     */
    public function getError(): int;


    /**
     * Returns the real_name code for this file
     *
     * @return string
     */
    public function getRealName(): string;
}
