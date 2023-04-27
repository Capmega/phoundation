<?php

use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\ProfileImage;
use Plugins\Phoundation\Components\Menu;

/**
 * Ajax system/sign-in
 *
 * This call can sign a user in and start a session
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
Session::validateSignIn();

// Attempt to sign in and if all okay, return an updated profile image with menu
$user  = Session::signIn($_POST['email'], $_POST['password']);

$menu  = Menu::getPrimaryMenu();

$image = ProfileImage::new()
    ->setImage(Session::getUser()->getPicture())
    ->setMenu(null)
    ->setUrl(null);

Log::printr([
    'topMenu'      => $menu->render(),
    'profileImage' => $image->render()
]);

Json::reply([
    'topMenu'      => $menu->render(),
    'profileImage' => $image->render()
]);
