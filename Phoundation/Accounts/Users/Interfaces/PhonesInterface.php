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
    public function setParent(DataEntryInterface $parent): static;

    /**
     * Returns Phones list object with phones for the specified user.
     *
     * @param bool $clear
     * @param bool $only_if_empty
     * @return static
     * @throws SqlMultipleResultsException , NotExistsException
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static;
}
