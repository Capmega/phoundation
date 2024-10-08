<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Stringable;

/**
 * Trait TraitDataFile
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataFile
{
    /**
     * The file for this object
     *
     * @var string|null $file
     */
    protected ?string $file = null;


    /**
     * Returns the file
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }


    /**
     * Sets the file
     *
     * @param Stringable|string|null $file
     *
     * @return static
     */
    public function setFile(Stringable|string|null $file): static
    {
        $this->file = get_null((string) $file);

        return $this;
    }
}
