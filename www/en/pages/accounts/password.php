<?php

declare(strict_types=1);


use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Validate GET
GetValidator::new()
    ->select('id')->isOptional()->isId()
    ->validate();

$user = User::get($_GET['id']);

// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        PostValidator::new()
            ->select('password1')->isPassword()
            ->select('password2')->isPassword()
        ->validate();

        // Update user password
        $user = User::get($_GET['id']);
        $user->setPassword($_POST['password1'] ,$_POST['password2']);

        Page::getFlashMessages()->add(tr('Success'), tr('The password for user ":user" has been updated', [':user' => $user->getDisplayName()]), DisplayMode::success);
        Page::redirect('this');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->add($e);
        $user->forceApply();
    }
}


// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton(tr('Back'), DisplayMode::secondary, '/accounts/users.html', true);


// Build the user form
$card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for User :name', [':name' => $user->getDisplayName()]))
    ->setContent($user->getHtmlForm()->render())
    ->setButtons($buttons);


// Build the roles list management section
$rights = Card::new()
    ->setTitle(tr('Roles for this user'))
    ->setContent($user->getRolesHtmlForm()
        ->setAction('#')
        ->setMethod('POST')
        ->render())
    ->setButtons($buttons);


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($card->render() . $rights->render())
    ->setSize(9)
    ->useForm(true);


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('User profile picture'))
    ->setContent(Img::new()
        ->setSrc($user->getPicture())
        ->setAlt(tr('Profile picture for :user', [':user' => $user->getDisplayName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/password-' . $user->getId() . '.html') . '">' . tr('Change password for this user') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the grid
$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), 3);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('User'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                    => tr('Home'),
    '/accounts/users.html' => tr('Users'),
    ''                     => $user->getDisplayName()
]));
