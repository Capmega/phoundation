<?php

/**
 * Page my/profile.php
 *
 * This is the user profile page where the user can manage their own profile data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\UploadHandler;


// Get the user and alter the default user form
$user        = Session::getUserObject();
$definitions = $user->getDefinitionsObject();
$definitions->get('last_sign_in')->setSize(4);
$definitions->get('sign_in_count')->setSize(4);
$definitions->get('authentication_failures')->setSize(4);
$definitions->get('locked_until')->setRender(false);
$definitions->get('username')->setRender(false);
$definitions->get('nickname')->setRender(false);
$definitions->get('latitude')->setRender(false);
$definitions->get('longitude')->setRender(false);
$definitions->get('keywords')->setRender(false);
$definitions->get('url')->setRender(false);
$definitions->get('accuracy')->setRender(false);
$definitions->get('comments')->setReadonly(true)->setRender(false);
$definitions->get('is_leader')->setReadonly(true)->setRender(false);
$definitions->get('leaders_id')->setReadonly(true)->setRender(false);
$definitions->get('code')->setReadonly(true)->setRender(false);
$definitions->get('type')->setReadonly(true)->setRender(false);
$definitions->get('priority')->setReadonly(true)->setRender(false);
$definitions->get('offset_latitude')->setReadonly(true)->setRender(false);
$definitions->get('email')->setReadonly(true);
$definitions->get('offset_longitude')->setReadonly(true)->setRender(false);
$definitions->get('domain')->setReadonly(true)->setRender(false);
$definitions->get('verified_on')->setRender(false);
$definitions->get('keywords')->setSize(3);
$definitions->get('redirect')->setRender(false)->setReadonly(true);
$definitions->get('url')->setSize(6);
$definitions->get('description')->setRender(false);
$definitions->get('data')->setRender(false);
$definitions->moveBeforeKey('zipcode', 'address');


// Define the drag/drop upload selector
Request::getFileUploadHandlersObject()
    ->add(UploadHandler::new('image')
        ->getDropZoneObject()
        ->setUrl(Url::getAjax('my/profile/image/upload'))
        ->setSelector('#profile-picture-card')
        ->setMaxFiles(0)
        ->getHandler()
    )->process();


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    if (PostValidator::new()->getSubmitButton() === tr('Submit')) {
        try {
            // Update user
            $user->apply()->save();

            // Go back to where we came from
// TODO Implement timers
//showdie(Timers::get('query'));

            Response::getFlashMessagesObject()->addSuccess(tr('Your profile has been updated'));
            Response::redirect('referer');

        } catch (ValidationFailedException $e) {
            // Oops! Show validation errors and remain on page
            Response::getFlashMessagesObject()->addMessage($e);
            $user->forceApply();
        }
    }
}


// Build the buttons
$buttons = Buttons::new()->addButton('Submit');


// Build the user form
$card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('Manage your profile information here'))
            ->setContent($user->getHtmlDataEntryFormObject()->render())
            ->setButtons($buttons);


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
                    ->addContent($card->render())
                    ->setSize(9)
                    ->useForm(true);


// Build profile picture card
$picture = Card::new()
               ->setTitle(tr('My profile picture'))
               ->setId('profile-picture-card')
               ->setContent(Session::getUserObject()
                                   ->getProfileImageObject()
                                       ->getHtmlImgObject()
                                           ->setId('profile-picture')
                                           ->addClasses('w100')
                                           ->setAlt(tr('My profile picture')));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::getWww('/my/password.html') . '">' . tr('Change my password') . '</a><br>
                              <a href="' . Url::getWww('/my/settings.html') . '">' . tr('Manage my settings') . '</a><br>
                              <a href="' . Url::getWww('/my/api-access.html') . '">' . tr('Manage my API access') . '</a><br>
                              <a href="' . Url::getWww('/my/authentication-history.html') . '">' . tr('Review my authentication history') . '</a><br>
                              <a href="' . Url::getWww('/profiles/profile+' . $user->getId() . '.html') . '">' . tr('View my public profile') . '</a><br>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                                   <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                                   <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Set page meta data
Response::setHeaderTitle(tr('My profile'));
Response::setHeaderSubTitle($user->getName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('My profile'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($column)
           ->addGridColumn($picture->render() . '<br>' .
                       $relevant->render() . '<br>' .
                       $documentation->render(), EnumDisplaySize::three);
