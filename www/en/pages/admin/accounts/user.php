<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Templates\Mdb\Layouts\GridColumn;



// Validate
GetValidator::new()
    ->select('id')->isId()
    ->validate();



// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton('Cancel', 'secondary', '/admin/accounts/users.html', true);



// Build the user form
$user = User::get($_GET['id']);
$form = User::get($_GET['id'])->getHtmlForm();
$card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for User :name', [':name' => $user->getDisplayName()]))
    ->setContent($form->render())
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
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . Url::build('/admin/accounts/roles.html')->www() . '">' . tr('Roles management') . '</a><br>
                         <a href="' . Url::build('/admin/accounts/rights.html')->www() . '">' . tr('Rights management') . '</a>');



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
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), 3);

echo $grid->render();


// Set page meta data
WebPage::setHeaderTitle(tr('User'));
WebPage::setHeaderSubTitle($user->getName());
WebPage::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/admin/'                    => tr('Home'),
    '/admin/accounts/users.html' => tr('Users'),
    ''                           => $user->getDisplayName()
]));
