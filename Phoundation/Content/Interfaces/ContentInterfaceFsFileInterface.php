<?php

declare(strict_types=1);

namespace Phoundation\Content\Interfaces;

use Phoundation\Filesystem\Interfaces\FsFileInterface;

interface ContentInterfaceFsFileInterface extends FsFileInterface
{
    /**
     * View the object file
     *
     * @return void
     */
    public function view(): void;
}
