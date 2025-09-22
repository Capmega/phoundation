<?php

/**
 * Class LostPasswordPage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


class LostPasswordPage extends Page
{
    /**
     * LostPasswordPage class constructor
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null) {
        // This page must build its own body
        // Set page meta-data
        Response::setPageTitle(tr('Request a new password'));
        Response::setHeaderTitle(tr('Request a new password'));

        $this->setUrl(Url::new('sign-in')->makeWww()->addRedirect(array_get_safe($get, 'redirect'))->addQuery(array_get_safe($get, 'email'), 'email'), 'back-to-sign-in');
        $this->setUrl(Url::new('backgrounds/signin.jpg')->makeImg()                                                                        , 'image-background');

        parent::__construct($name);
    }
}
