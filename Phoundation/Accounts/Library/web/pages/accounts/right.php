<?php

/**
 * Page accounts/right.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();


// Build the page content
$right = Right::load($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Update right
                $right
                    ->apply()
                    ->save();

// TODO Implement timers
//showdie(Timers::get('query'));

                Response::getFlashMessagesObject()->addSuccess(tr('Right ":right" has been saved', [':right' => $right->getName()]));
                Response::redirect('referer');

            case tr('Delete'):
                $right->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The right ":right" has been deleted', [':right' => $right->getName()]));
                Response::redirect();

            case tr('Undelete'):
                $right->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The right ":right" has been undeleted', [':right' => $right->getName()]));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
        $right->forceApply();
    }
}


// Audit button.
if (!$right->isNew()) {
    $audit = Button::new()
                   ->setFloatRight(true)
                   ->setMode(EnumDisplayMode::information)
                   ->setAnchorUrl('/audit/meta+' . $right->getMetaId() . '.html')
                   ->setFloatRight(true)
                   ->setValue(tr('Audit'))
                   ->setContent(tr('Audit'));

    $delete = Button::new()
                    ->setFloatRight(true)
                    ->setMode(EnumDisplayMode::warning)
                    ->setOutlined(true)
                    ->setValue(tr('Delete'))
                    ->setContent(tr('Delete'));
}


// Build the right card
$form = $right->getHtmlDataEntryFormObject();
$card = Card::new()
            ->setTitle(tr('Edit data for right :name', [':name' => $right->getName()]))
            ->setContent($form->render())
            ->setButtons(Buttons::new()
                                ->addButton(tr('Save'))
                                ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::getPrevious('/accounts/rights.html'), true)
                                ->addButton(isset_get($delete))
                                ->addButton(isset_get($audit)));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/accounts/users.html') . '">' . tr('Users management') . '</a><br>
                         <a href="' . Url::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Right'));
Response::setHeaderSubTitle($right->getName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                     => tr('Home'),
    '/accounts/rights.html' => tr('Rights'),
    ''                      => $right->getName() ?? tr('[NEW]'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($card, EnumDisplaySize::nine, true)
           ->addGridColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);
