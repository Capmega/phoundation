<?php

/**
 * Page 403
 *
 * This is the page that will be shown when a users access to a certain resource was prohibited
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Core;
use Phoundation\Web\Html\Pages\Page;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


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
    ':h2'     => '403',
    ':h3'     => tr('Forbidden'),
    ':img'    => Url::new('backgrounds/404/large.jpg')->makeImg(),
    ':p'      => tr('You do not have access to this page. Please contact the system administrator if you think this was in error'),
    ':type'   => 'warning',
    ':search' => tr('Search'),
    ':action' => Url::new('search/')->makeWww(),
]);
