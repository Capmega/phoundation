<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Validate GET data
$get = GetValidator::new()
    ->select('id')->isDbId()
    ->validate();


// Get the user and alter the default user form
$user        = User::get($get['id'])->setReadonly(true);
$definitions = $user->getDefinitions();

$definitions->get('last_sign_in')
    ->setSize(4);

$definitions->get('sign_in_count')
    ->setSize(4);

$definitions->get('authentication_failures')
    ->setSize(4);

$definitions->get('locked_until')
    ->setVisible(false);

$definitions->get('username')
    ->setReadonly(true);

$definitions->get('comments')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('is_leader')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('leaders_id')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('code')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('type')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('priority')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('offset_latitude')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('email')
    ->setReadonly(true);

$definitions->get('offset_longitude')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('domain')
    ->setReadonly(true)
    ->setVisible(false);

$definitions->get('verified_on')
    ->setVisible(false);

$definitions->get('keywords')
    ->setSize(3);

$definitions->get('redirect')
    ->setVisible(false)
    ->setReadonly(true);

$definitions->get('url')
    ->setSize(9);

$definitions->get('description')
    ->setSize(12);


// Build the user form
$card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('View the profile information for :user here', [':user' => $user->getDisplayName()]))
    ->setContent($user->getHtmlDataEntryForm()->render());


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($card->render())
    ->setSize(9)
    ->useForm(true);


// Build profile picture card
//showdie($user->getPicture());
$picture = Card::new()
    ->setTitle(tr('The users profile picture'))
    ->setContent(Img::new()
        ->addClass('w100')
        ->setSrc($user->getPicture())
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
        ->setAlt(tr('My profile picture')));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/my/profile.html') . '">' . tr('Your own profile') . '</a>');


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
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('The profile for'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'               => tr('Home'),
    '/profiles.html'  => tr('Profiles'),
    ''                => $user->getDisplayName()
]));
