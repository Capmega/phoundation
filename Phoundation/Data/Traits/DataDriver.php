<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataDriver
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataDriver
{
    /**
     * The driver for this object
     *
     * @var string|null $driver
     */
    protected ?string $driver = null;


    /**
     * Returns the driver
     *
     * @return string|null
     */
    public function getDriver(): ?string
    {
        return $this->driver;
    }


    /**
     * Sets the driver
     *
     * @param string|null $driver
     * @return static
     */
    public function setDriver(?string $driver): static
    {
        $this->driver = get_null($driver);
        return $this;
    }
}
