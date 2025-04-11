<?php

/**
 * Ajax my/profile/set-default-url.php
 *
 * This AJAX request allows the user to set their default page
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessage;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Requests\JsonPage;

// Get the POST arguments
$post = PostValidator::new()
                     ->select('url')->sanitizeMakeUrlObject(Session::getDomain())
                     ->validate();


// Update the user configuration
Session::getUserObject()->getConfigurationsObject()->set($post['url']->clearQueries(), 'default_page')->save();


// Notify the user that the default page has been updated
JsonPage::new()
        ->addFlashMessageSections(FlashMessage::new()
                                              ->setMode(EnumDisplayMode::success)
                                              ->setTitle(tr('Default page updated!'))
                                              ->setMessage(tr('This page is now your default page')))
        ->reply();
