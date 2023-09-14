<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\PasswordNotChangedException;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Get current user and password objects
$user        = User::get(Session::getUser()->getId());
$password    = $user->getPassword();


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    if (PostValidator::new()->getSubmitButton() === tr('Save')) {
        try {
            $post = PostValidator::new()
                ->select('current')->isPassword()
                ->select('password')->isPassword()
                ->select('passwordv')->isPassword()
                ->validate();

            // First ensure the current password is correct
            User::authenticate($user->getEmail(), $post['current']);

            // Update user password
            $user->setPassword($post['password'], $post['passwordv']);

            Page::getFlashMessages()->addSuccessMessage(tr('Your password has been updated'));
            Page::redirect(UrlBuilder::getWww(UrlBuilder::getPrevious('/my/profile.html')));

        } catch (AuthenticationException $e) {
            // Oops! Current password was wrong
            Page::getFlashMessages()->addWarningMessage(tr('Your current passwors was incorrect'));

        } catch (ValidationFailedException $e) {
            // Oops! Show validation errors and remain on page
            Page::getFlashMessages()->addWarningMessage($e);

        }catch (PasswordNotChangedException $e) {
            Page::getFlashMessages()->addWarningMessage(tr('You provided your current password. Please update your account to have a new and secure password'));
        }
    }
}


// Build the buttons
$buttons = Buttons::new()
    ->addButton(tr('Save'))
    ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/my/profile.html'), true);


// Build the user form
$card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('Change your password'))
    ->setContent($password->getHtmlForm()->render())
    ->setButtons($buttons);


// Build the grid column with a form containing the password card
$column = GridColumn::new()
    ->addContent($card->render())
    ->setSize(9)
    ->useForm(true);


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/my/profile.html') . '">' . tr('Manage Your profile') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/my/settings.html') . '">' . tr('Manage Your settings') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/my/api-access.html') . '">' . tr('Manage Your API access') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/my/sign-in-history.html') . '">' . tr('Review Your sign-in history') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('Change your password'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                  => tr('Home'),
    '/your/profile.html' => tr('Your profile'),
    ''                   => tr('Change your password')
]));
