<?php
/**
 * Page sign-out
 *
 * Closes the session and tries to redirect the user back to the previous page
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Get a redirect URL and sign the user out
$previous = Url::newPrevious('/');
$test     = clone $previous;
$user     = Session::signOut();


// Redirect URL may NOT be sign-out!s
if ($test->removeAllQueries()->getSource() === Url::new('signout')->makeWww()->removeAllQueries()->getSource()) {
    $previous = null;
}


// Redirect to the sign-in page
Response::redirect(Url::new('signin')->makeWww()->addRedirect($previous)
                      ->addQueries('email=' . $user->getEmail()));
