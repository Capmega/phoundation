<?php

/**
 * Close the session and redirect back to the previous page
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Requests\Response;
Session::signOut();
Response::redirect('/');
