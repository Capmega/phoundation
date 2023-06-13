<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Data\Interfaces\DataEntryInterface;


/**
 * Class GuestUser
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class GuestUser extends User
{
    /**
     * GuestUser class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null)
    {
        parent::__construct(null);
        $this->id = -1;
        $this->setNickname('Guest');
    }
}