<?php

declare(strict_types=1);

use Phoundation\Core\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Get the user and alter the default user form
$password = Session::getUser()->getPassword();


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        // Update the password and remove the user redirect
        $password
            ->apply()
            ->save()
            ->getUser()
                ->setRedirect()
                ->save();

        Page::getFlashMessages()->addSuccessMessage(tr('Your password has been updated'));
        Page::redirect('referer');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Build the user form
$card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('Update your password here'))
    ->setContent($password->getHtmlForm()->render())
    ->setButtons(Buttons::new()->addButton('Save'));


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($card->render())
    ->setSize(9)
    ->useForm(true);


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/profile.html') . '">' . tr('Your profile') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/settings.html') . '">' . tr('Your settings') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/sign-in-history.html') . '">' . tr('Your sign in history') . '</a>');


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
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Change password'));
Page::setHeaderSubTitle($password->getUser()->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'             => tr('Home'),
    '/profile.html' => tr('My profile'),
    ''              => tr('Change password')
]));
