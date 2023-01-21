<?php

use Phoundation\Core\Session;
use Phoundation\Web\Page;



/**
 * Close the session and redirect back to the previous page
 */
Session::signOut();
Page::redirect('/sign-in.html');