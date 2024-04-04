<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;


/**
 * interface PhonesInterface
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
interface PhonesInterface extends DataListInterface
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
     *
     * @return static
     * @throws SqlMultipleResultsException , NotExistsException
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static;
}
