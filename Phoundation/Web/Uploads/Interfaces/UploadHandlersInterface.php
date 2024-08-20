<?php

namespace Phoundation\Web\Uploads\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;


interface UploadHandlersInterface extends IteratorInterface
{
    /**
     * Returns the files that were uploaded
     *
     * @return IteratorInterface
     */
    public function getUploadedFiles(): IteratorInterface;

    /**
     * Returns the files that were processed
     *
     * @return IteratorInterface
     */
    public function getProcessedFiles(): IteratorInterface;

    /**
     * Returns the files that were rejected
     *
     * @return IteratorInterface
     */
    public function getRejectedFiles(): IteratorInterface;

    /**
     * Returns if any files were uploaded
     *
     * @return bool
     */
    public function hasUploadedFiles(): bool;

    /**
     * Returns if any files were processed
     *
     * @return bool
     */
    public function hasProcessedFiles(): bool;

    /**
     * Returns if any files were rejected
     *
     * @return bool
     */
    public function hasRejectedFiles(): bool;
}
