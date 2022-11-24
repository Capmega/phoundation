<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Json;

PostValidator::new()
    ->select('email')->isEmail()
    ->select('password')->isPassword()
    ->validate();

$user = User::authenticate($_POST['email'], $_POST['password']);
show($_GET);


PostValidator::new()
    ->select('test')->isNumeric()
    ->validate();

show($_GET);
showdie($_POST);

Json::reply();