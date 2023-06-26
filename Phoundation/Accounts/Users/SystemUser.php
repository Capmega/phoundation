<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\GuestUserInterface;
use Phoundation\Accounts\Users\Interfaces\SystemUserInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


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
class SystemUser extends User implements SystemUserInterface
{
    /**
     * GuestUser class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = false)
    {
        parent::__construct($identifier, $init);

        $this->data['id'] = null;
        $this->setNickname('System');
    }
}