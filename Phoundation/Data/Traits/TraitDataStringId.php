<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataStringId
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait TraitDataStringId
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
