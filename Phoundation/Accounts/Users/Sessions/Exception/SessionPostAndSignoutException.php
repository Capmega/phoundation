<?php

/**
 * Class SessionPostAndSignoutException
 *
 * This exception is thrown when issues were detected with the post and signout process
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions\Exception;

use Phoundation\Data\Validator\Exception\ValidationFailedException;


class SessionPostAndSignoutException extends ValidationFailedException
{
}
