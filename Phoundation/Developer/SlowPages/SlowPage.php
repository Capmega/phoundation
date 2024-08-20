<?php

/**
 * SlowPage class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Developer\Incidents\Incident;


class SlowPage extends Incident
{
    /**
     * SlowPage class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        $this->setType('slow_page');
        parent::__construct($identifier, $meta_enabled, $init);
    }
}
