<?php

namespace Phoundation\Data\Traits;


/**
 * Trait DataStringId
 *
 *
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait DataStringId
{
    /**
     * Object id
     *
     * @var string|null $id
     */
    protected ?string $id = null;


    /**
     * Returns the string id for this object or null
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
