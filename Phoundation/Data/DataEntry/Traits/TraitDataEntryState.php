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



use Phoundation\Geo\States\Interfaces\StateInterface;
use Phoundation\Geo\States\State;

trait TraitDataEntryState
{
    /**
     * State object cache
     *
     * @var StateInterface|null $o_state
     */
    protected ?StateInterface $o_state;


    /**
     * Returns the states_id for this object
     *
     * @return int|null
     */
    public function getStatesId(): ?int
    {
        return $this->getTypesafe('int', 'states_id');
    }


    /**
     * Sets the states_id for this object
     *
     * @param int|null $states_id
     *
     * @return static
     */
    public function setStatesId(?int $states_id): static
    {
        $this->o_state = null;
        return $this->set($states_id, 'states_id');
    }


    /**
     * Returns the state for this object
     *
     * @return StateInterface|null
     */
    public function getStateObject(): ?StateInterface
    {
        if (empty($this->o_state)) {
            $this->o_state = State::new($this->getTypesafe('int', 'states_id'))->loadOrNull();
        }

        return $this->o_state;
    }


    /**
     * Sets the state for this object
     *
     * @param StateInterface|null $o_state
     * @return TraitDataEntryState
     */
    public function setStateObject(?StateInterface $o_state): static
    {
        $this->setStatesId($o_state?->getId());

        $this->o_state = $o_state;
        return $this;
    }


    /**
     * Returns the states_name for this object
     *
     * @return string|null
     */
    public function getStatesName(): ?string
    {
        return $this->getStateObject()->getName();
    }


    /**
     * Returns the states_name for this object
     *
     * @param string|null $states_name
     *
     * @return static
     */
    public function setStatesName(?string $states_name): static
    {
        return $this->setStateObject(State::new(['name' => $states_name])->loadOrNull());
    }
}
