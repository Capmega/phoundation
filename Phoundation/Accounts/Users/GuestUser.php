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
use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;


class GuestUser extends User implements GuestUserInterface
{
    /**
     * GuestUser class constructor
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|null $identifier = null)
    {
        parent::__construct('guest');
    }
}
