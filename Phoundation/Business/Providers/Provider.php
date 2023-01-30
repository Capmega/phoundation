<?php

namespace Phoundation\Business\Providers;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryCategory;
use Phoundation\Data\DataEntry\DataEntryCode;
use Phoundation\Data\DataEntry\DataEntryEmail;
use Phoundation\Data\DataEntry\DataEntryNameDescription;
use Phoundation\Data\DataEntry\DataEntryPhones;
use Phoundation\Data\DataEntry\DataEntryUrl;


/**
 * Provider class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Provider extends DataEntry
{
    use DataEntryNameDescription;
    use DataEntryCategory;
    use DataEntryEmail;
    use DataEntryPhones;
    use DataEntryCode;
    use DataEntryUrl;



    /**
     * Providers class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name = 'providers';
        $this->table      = 'business_providers';

        parent::__construct($identifier);
    }



    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }



    /**
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }



    /**
     * @inheritDoc
     */
    protected function load(int|string $identifier): void
    {
        // TODO: Implement load() method.
    }
}