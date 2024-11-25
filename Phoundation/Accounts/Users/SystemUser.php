<?php

/**
 * Class SystemUser
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

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Interfaces\SystemUserInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


class SystemUser extends User implements SystemUserInterface
{
    /**
     * SystemUser class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        parent::__construct('system');

        $this->roles  = Roles::new(['god' => 'god']);
        $this->rights = Rights::new(['god' => 'god']);
    }
}
