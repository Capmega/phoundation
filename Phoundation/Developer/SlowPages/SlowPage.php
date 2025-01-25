<?php

/**
 * SlowPage class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\SlowPages;

use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;
use Phoundation\Security\Incidents\Incident;


class SlowPage extends Incident
{
    /**
     * SlowPage class constructor
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|null $identifier = null)
    {
        $this->setType('slow_page');
        parent::__construct($identifier);
    }
}
