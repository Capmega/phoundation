<?php

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



// Validate GET
GetValidator::new()
    ->select('id')->isOptional()->isId()
    ->validate();

$notification = Notification::get($_GET['id']);



// Build the notification form
$notification_card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for notification :name', [':name' => $notification->getDisplayName()]))
    ->setContent($notification->getHtmlForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Submit'))
        ->addButton(tr('Cancel'), 'secondary', '/accounts/notifications.html', true)
        ->addButton(tr('Audit'), 'green', '/audit/meta-' . $notification->getMeta() . '.html', false, true)
        ->addButton(isset_get($delete))
        ->addButton(isset_get($impersonate)));



// Build the roles list management section
if ($notification->getId()) {
    $roles_card = Card::new()
        ->setTitle(tr('Roles for this notification'))
        ->setContent($notification->getHtmlForm()
            ->setAction('#')
            ->setMethod('POST')
            ->render())
        ->setButtons(Buttons::new()
            ->addButton(tr('Submit'))
            ->addButton(tr('Cancel'), 'secondary', '/accounts/notifications.html', true));
}



// Build the grid column with a form containing the notification and roles cards
$column = GridColumn::new()
    ->addContent($notification_card->render() . (isset($roles_card) ? $roles_card->render() : ''))
    ->setSize(9)
    ->useForm(true);



// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('Notification profile picture'))
    ->setContent(Img::new()
        ->setSrc($notification->getPicture())
        ->setAlt(tr('Profile picture for :notification', [':notification' => $notification->getDisplayName()])));



// Build relevant links
$relevant = Card::new()
    ->setMode('info')
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/accounts/password-' . $notification->getId() . '.html') . '">' . tr('Change password for this notification') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/roles.html') . '">' . tr('Roles management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/accounts/rights.html') . '">' . tr('Rights management') . '</a>');



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
Page::setHeaderTitle(tr('Notification'));
Page::setHeaderSubTitle($notification->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                       => tr('Home'),
    '/notifications/all.html' => tr('Notifications'),
    ''                        => $notification->getDisplayName()
]));
