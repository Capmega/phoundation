<?php

/**
 * Page /try
 *
 * This is the "try and see if it works" page
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use Phoundation\Web\Http\Exception\Http404Exception;
use Phoundation\Web\Requests\Response;


// Can only use this on development!
if (PLATFORM === 'production') {
    throw new Http404Exception(tr('The "try" page is not available on production platforms'));
}


// Set page meta data
Response::setPageTitle(tr('Try page'));
Response::setHeaderTitle(tr('Try page'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Try'),
]));


// Start your tweedling here!

// 'id,right,description'

$user = Session::getUserObject();
$table = $user->getRightsObject(true, true)
              ->getHtmlDataTableObject('id,right,description')
//                  ->setLengthChangeEnabled(false)
//                  ->setSearchingEnabled(false)
//                  ->setPagingEnabled(false)
//                  ->setButtons('copy,csv,excel,pdf,print')
//                  ->setOrder([0 => 'asc'])
//                  ->setColumnsOrderable([
//                      0 => true,
//                      1 => false,
//                  ])
//                  ->setInfoEnabled(false)
                  ->setCheckboxSelectors(EnumTableIdColumn::hidden);

echo $table->render();
