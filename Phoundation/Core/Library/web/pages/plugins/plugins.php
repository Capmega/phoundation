<?php

/**
 * Page plugins/plugins
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */


declare(strict_types=1);

use Phoundation\Core\Plugins\FilterForm;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Button clicked?
if (Request::isPostRequestMethod()) {
    try {
        // Process buttons
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Scan'):
                $count = Plugins::scan();
                Response::getFlashMessagesObject()->addSuccess(tr('Finished scanning for libraries, found and registered ":count" new libraries', [':count' => $count]));
                Response::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Build plugins card
$plugins_card = Card::new()
               ->setTitle('Available plugins')
               ->setReloadSwitch(true)
               ->setContent(Plugins::new()
                                   ->getHtmlDataTableObject([
                                       'id'          => tr('Id'),
                                       'vendor'      => tr('Vendor'),
                                       'name'        => tr('Name'),
                                       'status'      => tr('Status'),
                                       'priority'    => tr('Priority'),
                                       'blacklisted' => tr('Blacklisted'),
                                       'description' => tr('Description'),
                                   ])
                                   ->setRowUrl('/plugins/plugin+:ROW.html'))
               ->useForm(true)
               ->setButtons(Buttons::new()->addButton(tr('Scan')));

$plugins_card->getForm()
             ->setAction(Url::getCurrent())
             ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::getWww('/development/slow-pages.html') . '">' . tr('Slow pages') . '</a><br>
                                   <a href="' . Url::getWww('/security/security.html') . '">' . tr('Security management') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Plugins'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Plugins'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($plugins_card                       , EnumDisplaySize::nine)
           ->addGridColumn($relevant_card . $documentation_card, EnumDisplaySize::three);
