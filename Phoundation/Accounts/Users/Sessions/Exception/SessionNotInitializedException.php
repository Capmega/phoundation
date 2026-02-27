<?php

/**
 * Class SessionNotInitializedException
 *
 * This exception is thrown whenever session data is accessed while the session itself has not yet been initialized
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions\Exception;

class SessionNotInitializedException extends SessionException
{
}
