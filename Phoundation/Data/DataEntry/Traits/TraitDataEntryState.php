<?php

/**
 * Trait TraitDataEntryState
 *
 * This trait contains methods for DataEntry objects that require GEO state data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\States\State;

trait TraitDataEntryState
{
    /**
     * Returns the states_id for this user
     *
     * @return int|null
     */
    public function getStatesId(): ?int
    {
        return $this->getTypesafe('int', 'states_id');
    }


    /**
     * Sets the states_id for this user
     *
     * @param int|null $states_id
     *
     * @return static
     */
    public function setStatesId(?int $states_id): static
    {
        return $this->set($states_id, 'states_id');
    }


    /**
     * Returns the state for this user
     *
     * @return State|null
     */
    public function getState(): ?State
    {
        $states_id = $this->getTypesafe('int', 'states_id');
        if ($states_id) {
            return new State($states_id);
        }

        return null;
    }


    /**
     * Returns the states_name for this user
     *
     * @return string|null
     */
    public function getStatesName(): ?string
    {
        return $this->getTypesafe('string', 'states_name');
    }


    /**
     * Sets the states_name for this user
     *
     * @param string|null $states_name
     *
     * @return static
     */
    public function setStatesName(?string $states_name): static
    {
        return $this->set($states_name, 'states_name');
    }
}
