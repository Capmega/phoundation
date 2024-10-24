<?php

declare(strict_types=1);

namespace Phoundation\Content\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;

interface ContentFileInterface extends PhoFileInterface
{
    /**
     * View the object file
     *
     * @return void
     */
    public function view(): void;
}
