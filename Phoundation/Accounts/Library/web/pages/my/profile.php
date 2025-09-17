<?php

/**
 * Page my/profile.php
 *
 * This is the user profile page where the user can manage their own profile data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Uploads\UploadHandler;


// No get parameters allowed
GetValidator::new()->validate();


// Get the user and alter the default user form
$user = Session::getUserObject();
$user->getDefinitionsObject()->setRenderMeta(false)
                             ->setDefinitionSize('last_sign_in'           , 4)
                             ->setDefinitionSize('sign_in_count'          , 4)
                             ->setDefinitionSize('authentication_failures', 4)
                             ->setDefinitionSize('keywords'               , 3)
                             ->setDefinitionSize('url'                    , 6)
                             ->setDefinitionSize('phone'                  , 6)
                             ->setDefinitionRender('update_password'      , false)
                             ->setDefinitionRender('locked_until'         , false)
                             ->setDefinitionRender('username'             , false)
                             ->setDefinitionRender('nickname'             , false)
                             ->setDefinitionRender('latitude'             , false)
                             ->setDefinitionRender('longitude'            , false)
                             ->setDefinitionRender('keywords'             , false)
                             ->setDefinitionRender('url'                  , false)
                             ->setDefinitionRender('accuracy'             , false)
                             ->setDefinitionRender('verified_on'          , false)
                             ->setDefinitionRender('description'          , false)
                             ->setDefinitionRender('data'                 , false)
                             ->setDefinitionRender('comments'             , false)
                             ->setDefinitionRender('is_leader'            , false)
                             ->setDefinitionRender('leaders_id'           , false)
                             ->setDefinitionRender('code'                 , false)
                             ->setDefinitionRender('type'                 , false)
                             ->setDefinitionRender('priority'             , false)
                             ->setDefinitionRender('offset_latitude'      , false)
                             ->setDefinitionRender('offset_longitude'     , false)
                             ->setDefinitionRender('domain'               , false)
                             ->setDefinitionRender('redirect'             , false)
                             ->setDefinitionRender('redirect-divider'     , false)
                             ->setDefinitionRender('zipcode'              , false)
                             ->setDefinitionRender('address'              , false)
                             ->setDefinitionRender('countries_id'         , false)
                             ->setDefinitionRender('states_id'            , false)
                             ->setDefinitionRender('cities_id'            , false)
                             ->setDefinitionRender('timezones_id'         , false)
                             ->setDefinitionRender('languages_id'         , false)
                             ->setDefinitionReadonly('email'              , true)
                             ->setDefinitionReadonly('comments'           , true)
                             ->setDefinitionReadonly('domain'             , true)
                             ->setDefinitionReadonly('offset_longitude'   , true)
                             ->setDefinitionReadonly('is_leader'          , true)
                             ->setDefinitionReadonly('leaders_id'         , true)
                             ->setDefinitionReadonly('code'               , true)
                             ->setDefinitionReadonly('type'               , true)
                             ->setDefinitionReadonly('priority'           , true)
                             ->setDefinitionReadonly('offset_latitude'    , true);


// Define the drag/drop upload selector
Request::getFileUploadHandlersObject()
       ->add(UploadHandler::new('image')
                          ->getDropZoneObject()
                              ->setUrl(Url::new('my/profile/image/upload')->makeAjax())
                              ->setSelector('#profile-picture-card')
                              ->setMaxFiles(0)
                              ->getHandlerObject()
       )->process();


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    switch (PostValidator::new()->getSubmitButton()) {
        case tr('Save'):
            try {
                // Update user
                $user->apply()->save();

                Response::getFlashMessagesObject()->addSuccess(tr('Your profile has been updated'));
                Response::redirect();

            } catch (ValidationFailedException $e) {
                // Oops! Show validation errors and remain on page
                Response::getFlashMessagesObject()->addMessage($e);
                $user->forceApply();
            }

            break;

        default:
            throw new ValidationFailedException(tr('Unknown submit button ":button" specified', [
                ':button' => PostValidator::new()->getSubmitButton()
            ]));
    }
}


// Build the buttons
$buttons = Buttons::new()->addButton('Save', right: true);


// Build the "user" form
$o_card = Card::new()
            ->setCollapseSwitch(true)
            ->setTitle(tr('My profile information'))
            ->setContent($user->getHtmlDataEntryFormObject())
            ->setButtonsObject($buttons);


// Build the grid column with a form containing the user and roles cards
$column = GridColumn::new()
                    ->addContent($o_card->render())
                    ->setSize(9)
                    ->useForm(true);


// Build profile picture card
$picture = Card::new()
               ->setTitle(tr('My profile picture'))
               ->setId('profile-picture-card')
               ->setCenter(true)
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
                ->setContent(AnchorBlock::new(Url::new('/my/settings.html')->makeWww(), tr('Manage my settings')) .
                             AnchorBlock::new(Url::new('/my/favorite-diagnostics.html')->makeWww(), tr('Manage my favorite diagnostics')) .
                             AnchorBlock::new(Url::new('/my/password.html')->makeWww(), tr('Change my password')) .
                             AnchorBlock::new(Url::new('/mfa/create.html')->makeWww()->addRedirect(Url::newCurrent()), tr('Setup multi factor authentication')) .
                             hr(AnchorBlock::new(Url::new('/profiles/profile+' . $user->getId(false) . '.html')->makeWww(), tr('My public profile page'))));


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('<p>On this page you can review and modify your profile information.</p>
                                   <p>Please ensure your information is up to date.</p>');


// Set page meta data
Response::setHeaderTitle(tr('My profile'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('My profile')),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($column)
           ->addGridColumn($picture . $relevant . $documentation, EnumDisplaySize::three);
