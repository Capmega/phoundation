<?php

declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Requests\Response;


/**
 * Close the session and redirect back to the previous page
 */
Session::signOut();
Response::redirect('/');
