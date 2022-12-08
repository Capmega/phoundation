<?php

use Phoundation\Core\Session;
use Phoundation\Web\WebPage;



/**
 * Close the session and redirect back to the previous page
 */
Session::destroy();
WebPage::redirect();