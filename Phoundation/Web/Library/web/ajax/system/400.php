<?php

/**
 * Page 400
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


// Show a 400 - Bad request flash message on the desktop
JsonPage::new()
        ->setResponse(EnumJsonResponse::error)
        ->addFlashMessageSections(FlashMessage::new()
                                              ->setMode(EnumDisplayMode::error)
                                              ->setTitle(tr('Bad Request'))
                                              ->setMessage(tr('You sent incorrect or invalid information and your request was denied. If you think this was in error, please contact your system administrator')))
        ->replyWithHttpCode(400, ['message' => $data ?? tr('bad request')]);