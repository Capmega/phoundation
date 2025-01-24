<?php

/**
 * Page my/password
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordNotChangedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Get current user and password objects
$user     = User::new(Session::getUserObject()->getId())->load();
$password = $user->getPassword();
$password->getDefinitionsObject()->setMetaVisible(false);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    if (PostValidator::new()->getSubmitButton() === tr('Save')) {
        try {
            $post = PostValidator::new()
                                 ->select('current')->isPassword()
                                 ->select('password')->isPassword()
                                 ->select('passwordv')->isPassword()
                                 ->validate();

            // First, ensure the current password is correct
            User::authenticate(['email' => $user->getEmail()], $post['current'], EnumAuthenticationAction::authentication);

            // Update user password
            $user->changePassword($post['password'], $post['passwordv']);

            Response::getFlashMessagesObject()->addSuccess(tr('Your password has been updated'));
            Response::redirect(Url::new(Url::newPrevious('/my/profile.html')->makeWww()));

        } catch (PasswordTooShortException | NoPasswordSpecifiedException) {
            Response::getFlashMessagesObject()->addWarning(tr('Please specify at least 10 characters for the password'));

        } catch (AuthenticationException $e) {
            // Oops! The Current password was wrong
            Response::getFlashMessagesObject()->addWarning(tr('Please specify at least ":count" characters for the password', [
                ':count' => Config::getInteger('security.passwords.size.minimum', 10),
            ]));

        } catch (ValidationFailedException $e) {
            // Oops! Show validation errors and remain on this page
            Response::getFlashMessagesObject()->addWarning($e);

        } catch (PasswordNotChangedException $e) {
            Response::getFlashMessagesObject()->addWarning(tr('You provided your current password. Please update your account to have a new and secure password'));
        }
    }
}


// Build the buttons
$buttons = Buttons::new()
                  ->addButton(tr('Save'))
                  ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/my/profile.html'), true);


// Build the "user" form
$card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('Change your password'))
            ->setContent($password->getHtmlDataEntryFormObject())
            ->setButtons($buttons);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::new('/my/profile.html')->makeWww() . '">' . tr('Manage my profile') . '</a><br>
                                   <a href="' . Url::new('/my/settings.html')->makeWww() . '">' . tr('Manage my settings') . '</a><br>
                                   <a href="' . Url::new('/my/api-access.html')->makeWww() . '">' . tr('Manage my API access') . '</a><br>
                                   <a href="' . Url::new('/my/authentication-history.html')->makeWww() . '">' . tr('Review my authentication history') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('<p>Here you can update your password</p>
                                        <p>Please first supply your current password to be sure that it\'s you.</p>
                                        <p>Then please supply your new password twice to avoid typos.</p>');


// Set page meta data
Response::setHeaderTitle(tr('Change your password'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                => tr('Home'),
    '/my/profile.html' => tr('My profile'),
    ''                 => tr('Change my password'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($card                               , EnumDisplaySize::nine, true)
           ->addGridColumn($relevant_card . $documentation_card, EnumDisplaySize::three);
