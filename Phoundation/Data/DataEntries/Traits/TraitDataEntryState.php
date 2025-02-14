<?php

/**
 * Trait TraitDataEntryState
 *
 * This trait contains methods for DataEntry objects that require a state
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Geo\States\State;
use Phoundation\Geo\States\Interfaces\StateInterface;



trait TraitDataEntryState
{
    /**
     * Setup virtual configuration for States
     *
     * @return static
     */
    protected function addVirtualConfigurationStates(): static
    {
        return $this->addVirtualConfiguration('states', State::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the states_id column
     *
     * @return int|null
     */
    public function getStatesId(): ?int
    {
        return $this->getVirtualData('states', 'int', 'id');
    }


    /**
     * Sets the states_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setStatesId(?int $id): static
    {
        return $this->setVirtualData('states', $id, 'id');
    }


    /**
     * Returns the states_code column
     *
     * @return string|null
     */
    public function getStatesCode(): ?string
    {
        return $this->getVirtualData('states', 'string', 'code');
    }


    /**
     * Sets the states_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setStatesCode(?string $code): static
    {
        return $this->setVirtualData('states', $code, 'code');
    }


    /**
     * Returns the states_name column
     *
     * @return string|null
     */
    public function getStatesName(): ?string
    {
        return $this->getVirtualData('states', 'string', 'name');
    }


    /**
     * Sets the states_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setStatesName(?string $name): static
    {
        return $this->setVirtualData('states', $name, 'name');
    }


    /**
     * Returns the State Object
     *
     * @return StateInterface|null
     */
    public function getStateObject(): ?StateInterface
    {
        return $this->getVirtualObject('states');
    }


    /**
     * Returns the states_id for this user
     *
     * @param StateInterface|null $o_object
     *
     * @return static
     */
    public function setStateObject(?StateInterface $o_object): static
    {
        return $this->setVirtualObject('states', $o_object);
    }
}
