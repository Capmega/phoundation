<?php

/**
 * Page security/authentication.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyauthentication Copyauthentication (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Authentication;
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
                   ->select('date_range')->isOptional()->isDateRange()
                   ->validate();


// Build the page content
$authentication = Authentication::load($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Save'):
                // Update authentication
                $authentication
                    ->apply()
                    ->save();

// TODO Implement timers
//showdie(Timers::get('query'));

                Response::getFlashMessagesObject()->addSuccess(tr('Authentication ":authentication" has been saved', [':authentication' => $authentication->getName()]));
                Response::redirect('referer');

            case tr('Delete'):
                $authentication->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The authentication ":authentication" has been deleted', [':authentication' => $authentication->getName()]));
                Response::redirect();

            case tr('Undelete'):
                $authentication->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The authentication ":authentication" has been undeleted', [':authentication' => $authentication->getName()]));
                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
        $authentication->forceApply();
    }
}


// Audit button.
if (!$authentication->isNew()) {
    $audit = Button::new()
                   ->setFloatRight(true)
                   ->setMode(EnumDisplayMode::information)
                   ->setAnchorUrl('/audit/meta+' . $authentication->getMetaId() . '.html')
                   ->setValue(tr('Audit'))
                   ->setContent(tr('Audit'));

    $delete = Button::new()
                    ->setFloatRight(true)
                    ->setMode(EnumDisplayMode::warning)
                    ->setOutlined(true)
                    ->setValue(tr('Delete'))
                    ->setContent(tr('Delete'));
}


// Build the "authentication" card
$form = $authentication->getHtmlDataEntryFormObject();
$card = Card::new()
            ->setTitle(tr('Edit data for authentication :id', [':id' => $authentication->getId()]))
            ->setContent($form)
            ->setButtons(Buttons::new()
                                ->addButton(tr('Save'))
                                ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::getPrevious('/accounts/authentications.html'), true)
                                ->addButton(isset_get($delete))
                                ->addButton(isset_get($audit)));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/security/authentications.html')->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : '') . '">' . tr('Authentications management') . '</a><br>
                              <a href="' . Url::getWww('/security/incidents.html')->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : '') . '">' . tr('Incidents management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
$url = Url::getWww('/security/authentications.html')->addQueries(
    $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
)->getSource();

Response::setHeaderTitle(tr('Authentication details'));
Response::setHeaderSubTitle($authentication->getId());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/security.html' => tr('Security'),
    $url             => tr('Authentications management'),
    ''               => $authentication->getId(),
]));


// Render and return the page grid
$grid = Grid::new()
            ->addGridColumn($card, EnumDisplaySize::nine, true)
            ->addGridColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

return $grid;
