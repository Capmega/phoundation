<?php

/**
 * Page sign-out
 *
 * Closes the session and tries to redirect the user back to the previous page
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */

declare(strict_types=1);

use Phoundation\Web\Requests\Response;


Response::signOut();
