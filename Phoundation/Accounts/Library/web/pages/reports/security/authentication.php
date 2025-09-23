<?php

/**
 * Page security/authentication.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyauthentication Copyauthentication (c) 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Authentication;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->select('date_range')->isOptional()->isDateRange()
                   ->validate();


// Build the page content
$authentication = Authentication::new()->load($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Delete'):
                $authentication->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The authentication ":authentication" has been deleted', [
                    ':authentication' => $authentication->getDisplayId()
                ]));

                Response::redirect();

            case tr('Undelete'):
                $authentication->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The authentication ":authentication" has been undeleted', [
                    ':authentication' => $authentication->getDisplayId()
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on the page
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
                   ->setContent(tr('Audit'))
                   ->setContent(tr('Audit'));

    $delete = Button::new()
                    ->setFloatRight(true)
                    ->setMode(EnumDisplayMode::warning)
                    ->setOutlined(true)
                    ->setContent(tr('Delete'))
                    ->setContent(tr('Delete'));
}


// Build the "authentication" card
$form = $authentication->getHtmlDataEntryFormObject();
$o_card = Card::new()
            ->setTitle(tr('Edit data for authentication :id', [':id' => $authentication->getId()]))
            ->setContent($form)
            ->setButtonsObject(Buttons::new()
                                      ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/authentications.html'), true)
                                      ->addButton(isset_get($delete))
                                      ->addButton(isset_get($audit)));


// Build relevant links
$o_relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(AnchorBlock::new(Url::new('/reports/security/authentications.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Authentications management')) .
                                  AnchorBlock::new(Url::new('/reports/security/incidents.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Incidents management')) .
                                  hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                     AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                     AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$o_documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
$url = Url::new('/reports/security/authentications.html')->makeWww()->addQueries(
    $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
)->getSource();

Response::setHeaderTitle(tr('Authentication details'));
Response::setHeaderSubTitle($authentication->getDisplayId());
Response::setBreadcrumbs([
    Breadcrumb::new('/'             , tr('Home')),
    Breadcrumb::new('/security.html', tr('Security')),
    Breadcrumb::new($url            , tr('Authentications management')),
    Breadcrumb::new(''              , $authentication->getDisplayId()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_card                               , EnumDisplaySize::nine, true)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
