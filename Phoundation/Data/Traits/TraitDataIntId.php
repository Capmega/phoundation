<?php

/**
 * Trait TraitDataIntId
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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


    /**
     * Sets the integer ID for this object
     *
     * @param int|null $id
     * @return $this
     */
    protected function setId(?int $id): static
    {
        $this->id = get_null($id);
        return $this;
    }
}
