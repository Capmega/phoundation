<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
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

// Validate GET and get requested user and password
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();

$user     = User::load($get['id'], 'id');
$password = $user->getPassword();


// Hide the "current" field as its not required for password updates by admin
$definitions = $password->getDefinitionsObject();
$definitions->get('current')->setRender(false);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    if (PostValidator::new()->getSubmitButton() === tr('Save')) {
        try {
            $post = PostValidator::new()
                                 ->select('password')->isPassword()
                                 ->select('passwordv')->isPassword()
                                 ->validate();

            // Update user password
            $user->changePassword($post['password'], $post['passwordv']);

            Response::getFlashMessages()->addSuccess(tr('The password for user ":user" has been updated', [':user' => $user->getDisplayName()]));
            Response::redirect(Url::getPrevious('accounts/user+' . $user->getId() . '.html'));

        } catch (PasswordTooShortException|NoPasswordSpecifiedException) {
            Response::getFlashMessages()->addWarning(tr('Please specify at least ":count" characters for the password', [
                ':count' => Config::getInteger('security.passwords.size.minimum', 10),
            ]));

        } catch (ValidationFailedException $e) {
            // Oops! Show validation errors and remain on this page
            Response::getFlashMessages()->addMessage($e);

        } catch (PasswordNotChangedException $e) {
            Response::getFlashMessages()->addWarning(tr('Specified password is the same as the current password for this user. Please update the password for this account to have a new and secure password'));
        }
    }
}


// Build the buttons
$buttons = Buttons::new()
                  ->addButton(tr('Save'))
                  ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::getPrevious('/accounts/users.html'), true);


// Build the user form
$card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('Change password for :name', [':name' => $user->getDisplayName()]))
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
                ->setContent('<a href="' . Url::getWww('/accounts/user+' . $user->getId() . '.html') . '">' . tr('Modify profile for this user') . '</a><br>
                         <a href="' . Url::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . Url::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


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
Response::setHeaderTitle(tr('Change password'));
Response::setHeaderSubTitle($user->getName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'                                          => tr('Home'),
                                                           '/accounts/users.html'                       => tr('Users'),
                                                           '/accounts/user+' . $user->getId() . '.html' => $user->getDisplayName(),
                                                           ''                                           => tr('Modify password'),
                                                       ]));
