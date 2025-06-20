<?php

/**
 * Page 404
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


Response::setHttpCode(404);


JsonPage::new()
        ->setResponse(EnumJsonResponse::error)
        ->addFlashMessageSections(FlashMessage::new()
                                              ->setMode(EnumDisplayMode::error)
                                              ->setTitle(tr('Page Not Found'))
                                              ->setMessage(tr('We could not find the page you were looking for. If you think this was in error, please contact your system administrator')))
        ->replyWithHttpCode(404, ['message' => $data ?? tr('not found')]);