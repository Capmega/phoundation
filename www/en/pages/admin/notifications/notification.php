<?php

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Notifications\Notification;
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

$notification = Notification::get($_GET['id']);
$notification->setStatus('READ');


// Build the notification form
$notification_card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for notification :name', [':name' => $notification->getTitle()]))
    ->setContent($notification->getHtmlForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Back'), 'secondary', '/accounts/notifications.html', true)
        ->addButton(isset_get($delete))
        ->addButton(isset_get($impersonate)));



// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/security/incidents.html') . '">' . tr('Security incidents') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/development/incidents.html') . '">' . tr('Development incidents') . '</a>');



// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');



// Build and render the grid
$grid = Grid::new()
    ->addColumn($notification_card, 9)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Notification'));
Page::setHeaderSubTitle($notification->getTitle());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                       => tr('Home'),
    '/notifications/all.html' => tr('Notifications'),
    ''                        => $notification->getTitle()
]));
