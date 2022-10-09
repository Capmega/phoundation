<?php

namespace Phoundation\Users;

use Phoundation\Core\Core;
use Phoundation\Exception\Exceptions;



/**
 * Class Authenticate
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Users
 */
class Authenticate
{
    public function __construct()
    {
        // Ensure that SEED has been configured
        // Todo Move this to a security class where its actually used. No need to check this every time when its not being used in 99% of the page calls
        if (!defined('SEED') or !SEED) {
            if (Core::readRegister('system', 'script')) !== 'setup') {
                throw Exceptions::outOfBoundsException(tr('startup: Configuration data in "ROOT/config/production.yaml"' . (ENVIRONMENT === 'production' ? '' : ' or "ROOT/config/' . ENVIRONMENT . '.yaml"') . ' has not been fully configured. Please ensure that security.seed is not empty'))->makeWarning();
            }
        }

    }
}