<?php

declare(strict_types=1);

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
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

// Get current user and password objects
$user     = User::load(Session::getUser()->getId());
$password = $user->getPassword();


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
            User::authenticate($user->getEmail(), $post['current']);

            // Update user password
            $user->changePassword($post['password'], $post['passwordv']);

            Response::getFlashMessages()->addSuccess(tr('Your password has been updated'));
            Response::redirect(Url::getWww(Url::getPrevious('/my/profile.html')));

        } catch (PasswordTooShortException | NoPasswordSpecifiedException) {
            Response::getFlashMessages()->addWarning(tr('Please specify at least 10 characters for the password'));

        } catch (AuthenticationException $e) {
            // Oops! The Current password was wrong
            Response::getFlashMessages()->addWarning(tr('Please specify at least ":count" characters for the password', [
                ':count' => Config::getInteger('security.passwords.size.minimum', 10),
            ]));

        } catch (ValidationFailedException $e) {
            // Oops! Show validation errors and remain on this page
            Response::getFlashMessages()->addWarning($e);

        } catch (PasswordNotChangedException $e) {
            Response::getFlashMessages()->addWarning(tr('You provided your current password. Please update your account to have a new and secure password'));
        }
    }
}


// Build the buttons
$buttons = Buttons::new()
                  ->addButton(tr('Save'))
                  ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::getPrevious('/my/profile.html'), true);


// Build the user form
$card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('Change your password'))
            ->setContent($password->getHtmlDataEntryFormObject()->render())
            ->setButtons($buttons);


// Build the grid column with a form containing the password card
$column = GridColumn::new()
                    ->addContent($card->render())
                    ->setSize(9)
                    ->useForm(true);


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/my/profile.html') . '">' . tr('Manage Your profile') . '</a><br>
                         <a href="' . Url::getWww('/my/settings.html') . '">' . tr('Manage Your settings') . '</a><br>
                         <a href="' . Url::getWww('/my/api-access.html') . '">' . tr('Manage Your API access') . '</a><br>
                         <a href="' . Url::getWww('/my/sign-in-history.html') . '">' . tr('Review Your sign-in history') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($column)
            ->addColumn($relevant->render() . '<br>' . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Change your password'));
Response::setHeaderSubTitle($user->getName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                  => tr('Home'),
    '/your/profile.html' => tr('Your profile'),
    ''                   => tr('Change your password'),
]));
