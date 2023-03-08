<?php

namespace Phoundation\Business\Companies\Branches;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


/**
 * Class Branch
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Branch extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Department class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name = 'company branch';
        $this->table      = 'business_branches';

        parent::__construct($identifier);
    }



    /**
     * @inheritDoc
     */
    public function save(?string $comments = null): static
    {
        // TODO: Implement save() method.
    }



    /**
     * @inheritDoc
     */
    public static function getFieldDefinitions(): array
    {
        // TODO: Implement getFieldDefinitions() method.
    }



    /**
     * @inheritDoc
     */
    protected function load(int|string $identifier): void
    {
        // TODO: Implement load() method.
    }
}