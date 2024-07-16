<?php

declare(strict_types=1);

namespace Phoundation\Web\Uploads\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface UploadHandlerInterface
{
    /**
     * Returns a list of all upload handlers
     *
     * @return IteratorInterface
     */
    public function getHandlersObject(): IteratorInterface;

    /**
     * Returns the maximum number of files that will be allowed to be uploaded
     *
     * @return int
     */
    public function getMaxFiles(): int;

    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param int $max_files
     * @return static
     */
    public function setMaxFiles(int $max_files): static;
}