<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;
use Phoundation\Filesystem\Filesystem;


/**
 * Trait DataDirectory
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataDirectory
{
    /**
     * The directory for this object
     *
     * @var string|null $directory
     */
    protected ?string $directory = null;


    /**
     * Returns the directory
     *
     * @return string|null
     */
    public function getDirectory(): ?string
    {
        return $this->directory;
    }


    /**
     * Sets the directory
     *
     * @param string|null $directory
     * @param string|null $prefix
     * @param bool $must_exist
     * @return static
     */
    public function setDirectory(?string $directory, string $prefix = null, bool $must_exist = true): static
    {
        if ($directory) {
            $this->directory = Strings::slash(Filesystem::absolute($directory, $prefix, $must_exist));

        } else {
            $this->directory = null;
        }

        return $this;
    }
}