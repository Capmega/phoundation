<?php

/**
 * Page phoundation/libraries
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Requests\Response;


// Build the page content
echo Card::new()
         ->setContent(Libraries::new()->getHtmlDataTableObject()))
         ->render();


// Set page meta data
Response::setHeaderTitle(tr('Libraries'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'            => tr('Home'),
                                                           '/phoundation' => tr('Phoundation'),
                                                           ''             => tr('Libraries'),
                                                       ]));
