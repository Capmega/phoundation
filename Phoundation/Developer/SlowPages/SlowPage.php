<?php

declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Developer\Incidents\Incident;

/**
 * SlowPage class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class SlowPage extends Incident
{
    /**
     * SlowPage class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool|null $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        $this->setType('slow_page');
        parent::__construct($identifier, $column, $meta_enabled);
    }
}
