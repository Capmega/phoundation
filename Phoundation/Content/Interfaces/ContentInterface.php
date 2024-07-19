<?php

declare(strict_types=1);

namespace Phoundation\Content\Interfaces;

use Phoundation\Filesystem\Interfaces\FsFileInterface;

interface ContentInterface extends FsFileInterface
{
    /**
     * View the object file
     *
     * @return void
     */
    public function view(): void;
}
