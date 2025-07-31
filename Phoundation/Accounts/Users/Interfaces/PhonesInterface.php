<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Exception\NotExistsException;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;

interface PhonesInterface extends DataIteratorInterface
{
    /**
     * Sets the parent
     *
     * @param DataEntryInterface|RenderInterface|UrlInterface|null $o_parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface|RenderInterface|UrlInterface|null $o_parent): static;


    /**
     * Returns a Phones Iterator object with phones for the specified user.
     *
     * @param array|string|int|null $identifiers
     * @param bool                  $like
     *
     * @return static
     * @throws SqlMultipleResultsException | NotExistsException
     */
    public function load(array|string|int|null $identifiers = null, bool $like = false): static;
}
