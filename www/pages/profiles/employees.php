<?php

use Phoundation\Accounts\Users\Users;
use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Contacts page
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Set page meta data
Page::setHeaderTitle(tr('Employees'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'              => tr('Home'),
    '/profiles.html' => tr('Profiles'),
    ''               => tr('Employees')
]));


$template = '   <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
                  <div class="card bg-light d-flex flex-fill">
                    <div class="card-header text-muted border-bottom-0">
                      :role
                    </div>
                    <div class="card-body pt-0">
                      <div class="row">
                        <div class="col-7">
                          <h2 class="lead"><b>:name</b></h2>
                          <p class="text-muted text-sm"><b>About: </b> :about</p>
                          <ul class="ml-4 mb-0 fa-ul text-muted">
                            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Address: :address</li>
                            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Phone #: :phone</li>
                            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-email"></i></span> Email @: :email</li>
                          </ul>
                        </div>
                        <div class="col-5 text-center">
                        ' . Session::getUser()->getPicture()
                                ->getHtmlElement()
                                ->setSrc(UrlBuilder::getImg("img/profiles/default.png"))
                                ->setClass("img-circle img-fluid")
                                ->setAlt(tr("Profile picture for :user", [":user" => Html::safe(Session::getUser()->getDisplayName())]))
                                ->render() . '                          
                        </div>
                      </div>
                    </div>
                    <div class="card-footer">
                      <div class="text-right">
                        <a href="#" class="btn btn-sm bg-teal">
                          <i class="fas fa-comments"></i>
                        </a>
                        <a href="' . UrlBuilder::getWww("profiles/profile+:id.html") . '" class="btn btn-sm btn-primary">
                          <i class="fas fa-user"></i> View Profile
                        </a>
                      </div>
                    </div>
                  </div>
                </div>';

// Build users content
$users   = Users::new()->load();
$content = '';

foreach ($users as $user) {
    $user_content = $template;
    $user_content = str_replace(':role'   , (null                    ?? '-'), $user_content);
    $user_content = str_replace(':id'     , ($user->getId())                , $user_content);
    $user_content = str_replace(':name'   , ($user->getDisplayName() ?? '-'), $user_content);
    $user_content = str_replace(':address', ($user->getAddress()     ?? '-'), $user_content);
    $user_content = str_replace(':about'  , (tr('Not available'))      , $user_content);
    $user_content = str_replace(':email'  , ($user->getEmail()       ? '<a href="mailto:' . $user->getEmail() . '">' . $user->getEmail() . '</a>' : '-'), $user_content);
    $user_content = str_replace(':phone'  , ($user->getPhone()       ? '<a href="tel:'    . $user->getPhone() . '">' . $user->getPhone() . '</a>' : '-'), $user_content);

    $content     .= $user_content;
}


// Build card
echo $card = Card::new()
    ->setContent('<div class="row">' . $content . '</div>')
    ->render();
