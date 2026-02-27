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

use Phoundation\Core\Core;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Html\Pages\Page;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


try {
    // Move the warning message to a flash message, then redirect
    Response::getFlashMessagesObject()->addWarning(Request::get('message'));
    Response::setHttpCode(401);
    Response::redirect('signout');

} catch (Throwable $e) {
    Incident::new($e)->save();

    // Get the exception
    $e = Core::readRegister('e');


    // Set page meta-data
    Response::setHttpCode(403);
    Response::setRenderMainWrapper(false);
    Response::setPageTitle('403 - Forbidden');
    Response::setHeaderTitle(tr('403 - Error'));
    Response::setDescription(tr('You do not have access to the specified resource'));
    Response::setBreadcrumbs();


    // Render and return the system page
    return Page::new('system/http-error')->addTextsObject([
        ':h2'     => '401',
        ':h3'     => tr('Unauthorized'),
        ':img'    => Url::new('backgrounds/404/large.jpg')->makeImg(),
        ':p'      => tr('Access to this page requires that you authenticate yourself first. Please contact the system administrator if you think this was in error'),
        ':type'   => 'warning',
        ':search' => tr('Search'),
        ':action' => Url::new('search/')->makeWww(),
    ]);
}
