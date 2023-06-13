<?php

declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Data\Interfaces\DataEntryInterface;
use Phoundation\Developer\Incidents\Incident;

/**
 * SlowPage class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class SlowProcess extends Incident
{
    /**
     * SlowPage class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null)
    {
        $this->entry_name  = 'incident';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'developer_slow_processes';
    }
}
