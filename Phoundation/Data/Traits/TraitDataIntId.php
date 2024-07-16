<?php

/**
 * Trait TraitDataIntId
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataIntId
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
