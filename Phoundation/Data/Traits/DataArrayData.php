<?php

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;
use Phoundation\Filesystem\Filesystem;


/**
 * Trait DataArrayData
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataArrayData
{
    /**
     * The data for this object
     *
     * @var array|null $data
     */
    protected ?array $data = null;



    /**
     * Returns the data
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }


    /**
     * Sets the data
     *
     * @param array|null $data
     * @return static
     */
    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }
}