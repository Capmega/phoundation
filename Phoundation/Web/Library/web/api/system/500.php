<?php

/**
 * Page 500
 *
 * This is the page that will be shown when a users access to a certain resource was prohibited
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Utils\Enums\EnumJsonResponse;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessage;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Requests\JsonPage;
use Phoundation\Web\Requests\Response;


Response::setHttpCode(403);


JsonPage::new()
        ->setResponse(EnumJsonResponse::error)
        ->replyWithHttpCode(500, ['message' => $data ?? tr('internal server error')]);
