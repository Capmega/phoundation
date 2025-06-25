<?php

/**
 * Page 401
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


JsonPage::new()
        ->setResponse(EnumJsonResponse::signin)
        ->addFlashMessageSections(FlashMessage::new()
                                              ->setMode(EnumDisplayMode::error)
                                              ->setTitle(tr('Unauthorized'))
                                              ->setMessage(tr('You need to sign-in to be able to see the requested resource')))
        ->replyWithHttpCode(401, ['message' => $data ?? tr('unauthorized')]);
