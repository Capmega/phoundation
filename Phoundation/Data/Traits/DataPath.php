<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;
use Phoundation\Filesystem\Filesystem;

/**
 * Trait DataPath
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataPath
{
    /**
     * The path for this object
     *
     * @var string|null $path
     */
    protected ?string $path = null;


    /**
     * Returns the path
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }


    /**
     * Sets the path
     *
     * @param string|null $path
     * @param string|null $prefix
     * @param bool $must_exist
     * @return static
     */
    public function setPath(?string $path, string $prefix = null, bool $must_exist = true): static
    {
        if ($path) {
            $this->path = Strings::slash(Filesystem::absolute($path, $prefix, $must_exist));
        } else {
            $this->path = null;
        }

        return $this;
    }
}