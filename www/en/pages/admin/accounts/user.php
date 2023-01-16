<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Templates\Mdb\Layouts\GridColumn;



// Validate GET
GetValidator::new()
    ->select('id')->isId()
    ->validate();

$user = User::get($_GET['id']);

// Validate POST and submit
if (WebPage::isRequestMethod('POST')) {
    try {
        PostValidator::new()
            ->select('username')->isOptional()->isName()
            ->select('domain')->isOptional()->isDomain()
            ->select('title')->isOptional()->isName()
            ->select('firstname')->isOptional()->isName()
            ->select('lastname')->isOptional()->isName()
            ->select('nickname')->isOptional()->isName()
            ->select('email')->isEmail()
            ->select('type')->isOptional()->isName()
            ->select('keywords')->isOptional()->sanitizeForceArray(' ')->each()->isWord()
            ->select('phones')->isOptional()->sanitizeForceArray(',')->each()->isPhone()
            ->select('address')->isOptional()->isPrintable()
            ->select('priority')->isOptional()->isNatural()->isBetween(1, 10)
            ->select('is_leader')->isOptional()->isBoolean()
            ->select('leaders_id')->isOptional()->isId()
            ->select('latitude')->isOptional()->isLatitude()
            ->select('longitude')->isOptional()->isLongitude()
            ->select('accuracy')->isOptional()->isFloat()->isBetween(0, 10)
            ->select('countries_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id'])
            ->select('states_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `countries_id` = :countries_id AND `status` IS NULL', [':id' => 'states_id', ':countries_id' => '$countries_id'])
            ->select('cities_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `states_id`    = :states_id    AND `status` IS NULL', [':id' => 'cities_id', ':states_id'    => '$states_id'])
            ->select('redirect')->isOptional()->isUrl()
            ->select('language')->isQueryColumn('SELECT `code_639_1` FROM `languages` WHERE `code_639_1` = :code_639_1 AND `status` IS NULL', [':code_639_1' => '$language'])
            ->select('gender')->isOptional()->inArray(['unknown', 'male', 'female', 'other'])
            ->select('birthday')->isOptional()->isDate()
            ->select('description')->isOptional()->isPrintable()->hasMaxCharacters(65_530)
            ->select('comments')->isOptional()->isPrintable()->hasMaxCharacters(16_777_200)
            ->select('website')->isOptional()->isUrl()
            ->select('timezone')->isOptional()->isTimezone()
    ->validate();
        // Update user
        $user = User::get($_GET['id']);
        $user->modify($_POST);
        $user->save();
showdie($user);

        // Go back to where we came from
        WebPage::getFlashMessages()->add(tr('Success'), tr('User ":user" has been updated', [':user' => $user->getDisplayName()]), 'success');
        WebPage::redirect('prev');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        WebPage::getFlashMessages()->add($e);
    }
}



// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton('Cancel', 'secondary', '/admin/accounts/users.html', true);



// Build the user form
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
