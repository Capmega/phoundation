<?php

namespace Phoundation\Data\Traits;

/**
 * Trait DataIntId
 *
 *
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait DataIntId
{
    /**
     * Object id
     *
     * @var int|null $id
     */
    protected ?int $id = null;

    /**
     * Returns the integer id for this object or null
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}