<?php

use Phoundation\Core\Session;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Json;
use Templates\Mdb\Components\ProfileImage;
use Templates\Mdb\TemplateMenus;


/**
 * Ajax system/sign-in
 *
 * This call can sign a user in and start a session
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Mdb\Accounts
 */
PostValidator::new()
    ->select('email')->isEmail()
    ->select('password')->isPassword()
    ->validate();

// Attempt to sign in and if all okay, return an updated profile image with menu
$user  = Session::signIn($_POST['email'], $_POST['password']);

$menu  = TemplateMenus::getPrimaryMenu();

$image = ProfileImage::new()
    ->setImage(Session::getUser()->getPicture())
    ->setMenu(null)
    ->setUrl(null);

Json::reply([
    'topMenu'      => $menu->render(),
    'profileImage' => $image->render()
]);
