<?php

/**
 * AJAX REST request system/accounts/users/redirect/autosuggest
 *
 * This request will return a list of redirect URLs
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Json\AutoSuggestRequest;
use Phoundation\Web\Requests\JsonPage;


// Ensure we'll have auto suggest data
AutoSuggestRequest::init(true);


// Validate
$get = GetValidator::new()
                   ->validate();


// Reply
JsonPage::new()->reply(['/force-password-update.html']);
