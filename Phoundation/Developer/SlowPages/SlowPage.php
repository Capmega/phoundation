<?php

declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Data\Interfaces\InterfaceDataEntry;
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
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        $this->setType('slow_page');
        parent::__construct($identifier);
    }
}
