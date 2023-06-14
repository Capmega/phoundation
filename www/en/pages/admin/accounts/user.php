<?php

declare(strict_types=1);


use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Button;
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
        switch (PostValidator::getSubmitButton()) {
            case tr('Submit'):
                // Update user
                $user->apply()->save();

                // Go back to where we came from
// TODO Implement timers
//showdie(Timers::get('query'));

                Page::getFlashMessages()->add(tr('Success'), tr('User ":user" has been updated', [':user' => $user->getDisplayName()]), 'success');
                Page::redirect('referer');

            case tr('Impersonate'):
                $user->impersonate();
                Page::getFlashMessages()->add(tr('Success'), tr('You are now impersonating ":user"', [':user' => $user->getDisplayName()]), 'success');
                Page::redirect('root');

            case tr('Delete'):
                $user->delete();
                Page::getFlashMessages()->add(tr('Success'), tr('The user ":user" has been deleted', [':user' => $user->getDisplayName()]), 'success');
                Page::redirect();

            case tr('Undelete'):
                $user->undelete();
                Page::getFlashMessages()->add(tr('Success'), tr('The user ":user" has been undeleted', [':user' => $user->getDisplayName()]), 'success');
                Page::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->add($e);
        $user->forceApply();
    }
}


// Impersonate button. We must have the right to impersonate, we cannot impersonate ourselves, and we cannot impersonate
// god users
if ($user->canBeImpersonated()) {
    $impersonate = Button::new()
        ->setRight(true)
        ->setMode(DisplayMode::danger)
        ->setContent(tr('Impersonate'));
}


// Delete button. We cannot delete god users
if (!$user->canBeStatusChanged()) {
    $delete = Button::new()
        ->setRight(true)
        ->setMode(DisplayMode::warning)
        ->setContent(tr('Delete'));
}


// Build the user form
$user_card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for user :name', [':name' => $user->getDisplayName()]))
    ->setContent($user->getHtmlForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Submit'))
        ->addButton(tr('Back'), 'secondary', '/accounts/users.html', true)
        ->addButton(tr('Audit'), 'green', '/audit/meta-' . $user->getMeta() . '.html', false, true)
        ->addButton(isset_get($delete))
        ->addButton(isset_get($impersonate)));


// Build the roles list management section
if ($user->getId()) {
    $roles_card = Card::new()
        ->setTitle(tr('Roles for this user'))
        ->setContent($user->getRolesHtmlForm()
            ->setAction('#')
            ->setMethod('POST')
            ->render())
        ->setButtons(Buttons::new()
            ->addButton(tr('Submit'))
            ->addButton(tr('Back'), 'secondary', '/accounts/users.html', true));
}


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($user_card->render() . (isset($roles_card) ? $roles_card->render() : ''))
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
