<?php

/**
 * Page my/settings.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// No get parameters allowed
GetValidator::new()->validate();


// Get the user
$user = Session::getUserObject();


// Apply and save the changes
if (Request::isPostRequestMethod()) {
    switch (Request::getSubmitButton()) {
        case tr('Save'):
            $user->getConfigurationsObject()->apply()->save();
            Response::getFlashMessagesObject()->addSuccess(tr('Your settings have been saved'));
            Response::redirect();

        default:
            throw new ValidationFailedException(tr('Unknown button ":button" specified', [
                ':button' => Request::getSubmitButton()
            ]));
    }
}


// Build the "settings" card
$settings_card = Card::new()
                     ->setTitle(tr('Edit your account settings'))
                     ->setContent($user->getConfigurationsObject()->getHtmlDataEntryFormObject())
                     ->setButtons(Buttons::new()
                                         ->addButton(tr('Save'), right: true)
                                         ->addButton(isset_get($delete))
                                         ->addButton(isset_get($audit)));


// Build relevant links
$o_relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent(AnchorBlock::new(Url::new('/my/profile.html')->makeWww(), tr('Manage my profile')) .
                                  AnchorBlock::new(Url::new('/my/favorite-diagnostics.html')->makeWww(), tr('Manage my favorite diagnostics')) .
                                  AnchorBlock::new(Url::new('/my/password.html')->makeWww(), tr('Change my password')) .
                                  AnchorBlock::new(Url::new('/mfa/create.html')->makeWww()->addRedirect(Url::newCurrent()), tr('Setup multi factor authentication')));
                                //AnchorBlock::new(Url::new('/my/authentication-history.html')->makeWww(), tr('Review my authentication history')) .


// Build documentation
$o_documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('In this settings page you may configure various details about how your account behaves on this platform. These settings are unique to your account alone');


// Set page meta data
Response::setHeaderTitle(tr('My settings'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadcrumbs([
    Anchor::new('/'               , tr('Home')),
    Anchor::new('/my/profile.html', tr('My profile')),
    Anchor::new(''                , tr('My settings')),
]);


// Render and return the page grid
return Grid::new()
            ->addGridColumn($settings_card                               , EnumDisplaySize::nine, true)
            ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
