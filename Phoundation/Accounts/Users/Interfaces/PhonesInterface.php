<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;

interface PhonesInterface extends DataIteratorInterface
{
    /**
     * Sets the parent
     *
     * @param DataEntryInterface $parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface $parent): static;


    /**
     * Returns a Phones Iterator object with phones for the specified user.
     *
     * @param array|null $identifiers
     * @param bool       $clear
     * @param bool       $only_if_empty
     *
     * @return static
     * @throws SqlMultipleResultsException , NotExistsException
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;
}
