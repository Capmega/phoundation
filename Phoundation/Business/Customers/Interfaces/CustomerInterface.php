<?php

namespace Phoundation\Business\Customers\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;

interface CustomerInterface extends DataEntryInterface
{
    /**
     * Returns the address2 for this object
     *
     * @return string|null
     */
    public function getAddress2(): ?string;

    /**
     * Sets the address2 for this object
     *
     * @param string|null $address2
     *
     * @return static
     */
    public function setAddress2(?string $address2): static;

    /**
     * Returns the address3 for this object
     *
     * @return string|null
     */
    public function getAddress3(): ?string;

    /**
     * Sets the address3 for this object
     *
     * @param string|null $address3
     *
     * @return static
     */
    public function setAddress3(?string $address3): static;
}
