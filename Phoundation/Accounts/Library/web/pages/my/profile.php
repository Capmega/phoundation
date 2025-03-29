<?php

/**
 * Page my/profile.php
 *
 * This is the user profile page where the user can manage their own profile data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
$user = Session::getUserObject();
$user->getDefinitionsObject()->setRenderMeta(false)
                             ->setSize('last_sign_in'           , 4)
                             ->setSize('sign_in_count'          , 4)
                             ->setSize('authentication_failures', 4)
                             ->setSize('keywords'               , 3)
                             ->setSize('url'                    , 6)
                             ->setSize('default_page'           , 12)
                             ->setRender('locked_until'         , false)
                             ->setRender('username'             , false)
                             ->setRender('nickname'             , false)
                             ->setRender('latitude'             , false)
                             ->setRender('longitude'            , false)
                             ->setRender('keywords'             , false)
                             ->setRender('url'                  , false)
                             ->setRender('accuracy'             , false)
                             ->setRender('verified_on'          , false)
                             ->setRender('description'          , false)
                             ->setRender('data'                 , false)
                             ->setRender('comments'             , false)
                             ->setRender('is_leader'            , false)
                             ->setRender('leaders_id'           , false)
                             ->setRender('code'                 , false)
                             ->setRender('type'                 , false)
                             ->setRender('priority'             , false)
                             ->setRender('offset_latitude'      , false)
                             ->setRender('offset_longitude'     , false)
                             ->setRender('domain'               , false)
                             ->setRender('redirect'             , false)
                             ->setReadonly('email'              , true)
                             ->setReadonly('comments'           , true)
                             ->setReadonly('domain'             , true)
                             ->setReadonly('offset_longitude'   , true)
                             ->setReadonly('is_leader'          , true)
                             ->setReadonly('leaders_id'         , true)
                             ->setReadonly('code'               , true)
                             ->setReadonly('type'               , true)
                             ->setReadonly('priority'           , true)
                             ->setReadonly('offset_latitude'    , true)
                             ->moveBeforeKey('zipcode', 'address');


// Define the drag/drop upload selector
Request::getFileUploadHandlersObject()
    ->add(UploadHandler::new('image')
        ->getDropZoneObject()
        ->setUrl(Url::new('my/profile/image/upload')->makeAjax())
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


// Build the "user" form
$card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('Manage your profile information here'))
            ->setContent($user->getHtmlDataEntryFormObject())
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
                ->setContent('<a href="' . Url::new('/my/password.html')->makeWww() . '">' . tr('Change my password') . '</a><br>
                              <a href="' . Url::new('/my/settings.html')->makeWww() . '">' . tr('Manage my settings') . '</a><br>
                              <a href="' . Url::new('/my/authentication-history.html')->makeWww() . '">' . tr('Review my authentication history') . '</a><br>
                              <a href="' . Url::new('/mfa/create.html')->makeWww()->addRedirect(Url::newCurrent()) . '">' . tr('Setup multi factor authentication') . '</a><br>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('<p>On this page you can review and modify your profile information.</p>
                                   <p>Please ensure your information is up to date.</p>');


// Set page meta data
Response::setHeaderTitle(tr('My profile'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('My profile'),
]));


// Render and return the page grid
return Grid::new()
           ->addGridColumn($column)
           ->addGridColumn($picture . '<br>' .
                           $relevant . '<br>' .
                           $documentation, EnumDisplaySize::three);
