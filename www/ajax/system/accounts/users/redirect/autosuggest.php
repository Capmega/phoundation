<?php

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Json\AutoSuggestRequest;
use Phoundation\Web\Http\UrlBuilder;
use Plugins\Medinet\Programs\Programs;


/**
 * AJAX REST request medinet/sources/programs/autosuggest
 *
 * This request will return a list of available programs
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Ensure we'll have auto suggest data
AutoSuggestRequest::init(true);


// Validate
$get = GetValidator::new()->validate();


// Reply
$reply = Json::encode([
    (string) UrlBuilder::getWww('/force-password-update.html')
]);

$reply = AutoSuggestRequest::getCallback() . '(' . $reply . ')';

Json::reply($reply);

