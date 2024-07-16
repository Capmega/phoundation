<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception\Interfaces;

interface DataEntryReadonlyExceptionInterface extends DataEntryExceptionInterface
{
    /**
     * Add a single action or a list of actions that are allowed
     *
     * @param string|array $allow
     *
     * @return $this
     */
    public function setAllow(string|array $allow): static;


    /**
     * Returns the list of actions that are allowed
     *
     * @return array
     */
    public function getAllow(): array;
}
