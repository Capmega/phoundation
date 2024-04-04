<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataStringData
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataStringData
{
    /**
     * The data for this object
     *
     * @var string|null $data
     */
    protected ?string $data = null;


    /**
     * Returns the data
     *
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }


    /**
     * Sets the data
     *
     * @param string|null $data
     *
     * @return static
     */
    public function setData(?string $data): static
    {
        $this->data = get_null($data);
        return $this;
    }
}