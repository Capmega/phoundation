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
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Passwords\Exception\NoPasswordSpecifiedException;
use Phoundation\Security\Passwords\Exception\PasswordNotChangedException;
use Phoundation\Security\Passwords\Exception\PasswordTooShortException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// No get parameters allowed
GetValidator::new()->validate();


// Get current user and password objects
$user     = Session::getUserObject();
$password = $user->getPasswordObject();
$password->getDefinitionsObject()->setRenderMeta(false);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    switch (PostValidator::new()->getSubmitButton()) {
        case tr('Save'):
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
                    ':count' => config()->getInteger('security.passwords.size.minimum', 10),
                ]));

            } catch (ValidationFailedException $e) {
                // Oops! Show validation errors and remain on this page
                Response::getFlashMessagesObject()->addWarning($e);

            } catch (PasswordNotChangedException $e) {
                Response::getFlashMessagesObject()->addWarning(tr('You provided your current password. Please update your account to have a new and secure password'));
            }

            break;

        default:
            throw new ValidationFailedException(tr('Unknown button ":button" specified', [
                ':button' => Request::getSubmitButton()
            ]));
    }
}


// Build the buttons
$buttons = Buttons::new()
                  ->addSaveButton(true);


// Build the "user" form
$_card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('Change your password'))
            ->setContent($password->getHtmlDataEntryFormObject())
            ->setButtonsObject($buttons);


// Build relevant links
$_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/my/profile.html')->makeWww(), tr('Manage my profile')) .
                                    AnchorBlock::new(Url::new('/my/settings.html')->makeWww(), tr('Manage my settings')));
                                  //AnchorBlock::new(Url::new('/my/authentication-history.html')->makeWww(), tr('Review my authentication history')));

// Build documentation
$_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('<p>Here you can update your password</p>
                                          <p>Please first supply your current password to be sure that it\'s you.</p>
                                          <p>Then please supply your new password twice to avoid typos.</p>');


// Set page meta-data
Response::setHeaderTitle(tr('Change your password'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/'               , tr('Home')),
    Breadcrumb::new('/my/profile.html', tr('My profile')),
    Breadcrumb::new(''                , tr('Change my password')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($_card                                   , EnumDisplaySize::nine, true)
           ->addGridColumn($_relevant_card . $_documentation_card, EnumDisplaySize::three);
