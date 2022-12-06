<?php

use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Json;
use Phoundation\Web\WebPage;



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
PostValidator::new()
    ->select('email')->isEmail()
    ->select('password')->isPassword()
    ->validate();

$user  = Session::signIn($_POST['email'], $_POST['password']);
$image = Webpage::getTemplate()->getComponents()->buildProfileImage();

Log::printr(['html' => $image]);
Json::reply(['html' => $image]);
