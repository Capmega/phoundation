<?php

/**
 * Trait TraitDataStringId
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


    /**
     * Sets the string id for this object or null
     *
     * @param string|null $id
     * @return TraitDataStringId
     */
    protected function setId(?string $id = null): static
    {
        $this->id = get_null($id);
        return $this;
    }
}
