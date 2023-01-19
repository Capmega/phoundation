<?php

use Phoundation\Core\Session;
use Phoundation\Web\Page;



/**
 * Close the session and redirect back to the previous page
 */
Session::destroy();
Page::redirect();