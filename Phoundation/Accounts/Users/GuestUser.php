<?php

/**
 * Class GuestUser
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\GuestUserInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;

class GuestUser extends User implements GuestUserInterface
{
    /**
     * GuestUser class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     * @param bool                               $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null, bool $init = true)
    {
        parent::__construct('guest', 'email', false, $init);

        $this->set(null   , 'redirect');
        $this->set('guest', 'email');
        $this->set('Guest', 'nickname');
    }
}
