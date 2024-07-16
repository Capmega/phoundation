<?php

declare(strict_types=1);

namespace Phoundation\Content\Interfaces;

use Phoundation\Filesystem\Interfaces\FileInterface;

interface ContentInterface extends FileInterface
{
    /**
     * View the object file
     *
     * @return void
     */
    public function view(): void;
}
