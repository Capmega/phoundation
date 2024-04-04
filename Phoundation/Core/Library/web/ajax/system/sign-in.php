<?php

use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Widgets\ProfileImage;
use Plugins\Phoundation\Phoundation\Components\Menu;


/**
 * Ajax system/sign-in
 *
 * This call can sign a user in and start a session
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */

$post = Session::validateSignIn();

// Attempt to sign in and if all okay, return an updated profile image with menu
$user  = Session::signIn($post['email'], $post['password']);
$menu  = Menu::getPrimaryMenu();
$image = ProfileImage::new()
                     ->setImage(Session::getUser()->getPicture())
                     ->setMenu(null)
                     ->setUrl(null);

Log::printr([
                'topMenu'      => $menu->render(),
                'profileImage' => $image->render(),
            ]);

Json::reply([
                'topMenu'      => $menu->render(),
                'profileImage' => $image->render(),
            ]);
