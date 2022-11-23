<?php

use Phoundation\Data\Validator\PostValidator;
use Phoundation\Utils\Json;

show($_GET);


PostValidator::new()
    ->select('test')->isNumeric()
    ->validate();

show($_GET);
showdie($_POST);

Json::reply();