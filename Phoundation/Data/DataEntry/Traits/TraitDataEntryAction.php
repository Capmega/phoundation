<?php

/**
 * Trait TraitDataEntryAction
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


trait TraitDataEntryAction
{
    /**
     * Returns the action for this object
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->getTypesafe('string', 'action');
    }


    /**
     * Sets the action for this object
     *
     * @param string|null $action
     *
     * @return static
     */
    public function setAction(?string $action): static
    {
        return $this->set($action, 'action');
    }
}
