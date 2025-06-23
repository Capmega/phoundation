<?php

/**
 * Page 401
 *
 * This is the page that will be shown when the system encounters an internal error from which it could not recover
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Web\Requests\Response;


Response::setHttpCode(401);
Response::redirect('signout');
