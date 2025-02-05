<?php

/**
 * Class SystemUser
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Interfaces\SystemUserInterface;
use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;


class SystemUser extends User implements SystemUserInterface
{
    /**
     * SystemUser class constructor
     *
     * @note $identifier is ignored for this class
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = null)
    {
        // System user is readonly and also does not register meta requests
        $this->readonly     = true;
        $this->meta_enabled = false;

        parent::__construct();

        $this->setEmail('system')
             ->source['status'] = 'system';

        $this->roles  = Roles::new()->load(['name' => 'god']);
        $this->rights = Rights::new()->load(['name' => 'god']);
    }
}
