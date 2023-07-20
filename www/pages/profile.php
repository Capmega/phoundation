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
$user        = Session::getUser();
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


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    if (PostValidator::getSubmitButton() === tr('Submit')) {
        try {
            // Update user
            $user->apply()->save();

            // Go back to where we came from
// TODO Implement timers
//showdie(Timers::get('query'));

            Page::getFlashMessages()->addSuccessMessage(tr('Your profile has been updated'));
            Page::redirect('referer');

        } catch (ValidationFailedException $e) {
            // Oops! Show validation errors and remain on page
            Page::getFlashMessages()->addMessage($e);
            $user->forceApply();
        }
    }
}


// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit');


// Build the user form
$card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('Manage your profile information here'))
    ->setContent($user->getHtmlForm()->render())
    ->setButtons($buttons);


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
    ->addContent($card->render())
    ->setSize(9)
    ->useForm(true);


// Build profile picture card
//showdie($user->getPicture());
$picture = Card::new()
    ->setTitle(tr('My profile picture'))
    ->setContent(Img::new()
        ->setSrc($user->getPicture())
        ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
        ->setAlt(tr('My profile picture')));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/settings.html') . '">' . tr('Your settings') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/api-access.html') . '">' . tr('Your API access') . '</a><br>
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
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('My profile'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('My profile')
]));
