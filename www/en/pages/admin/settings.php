<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



$user = User::get(Session::getUser()->getId());

// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {


    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->add($e);
        $user->modify($_POST);
    }
}



// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit');



// Alter the default user form
$user
    ->modifyKeys('comments'  , ['display'  => false])
    ->modifyKeys('is_leader' , ['disabled' => true])
    ->modifyKeys('leaders_id', ['disabled' => true]);



// Build the form
$card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Manage your settings here'))
    ->setContent('')
    ->setButtons($buttons);



// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($card->render())
    ->setSize(9)
    ->useForm(true);



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/profile.html') . '">' . tr('Your profile') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/api-access.html') . '">' . tr('Your API access') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode('info')
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('My settings'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('My settings')
]));
