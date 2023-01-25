<?php

namespace Phoundation\Developer\SlowPages;

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
    public function __construct(int|string|null $identifier = null)
    {
        $this->setType('slow_page');
        parent::__construct($identifier);
    }
}
