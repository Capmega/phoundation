<?php
try {
    switch ($type) {
        case 'normal':
            $_SESSION['mobile']['site'] = false;
            break;

        case 'mobile':
            $_SESSION['mobile']['site'] = true;
            break;

        default:
            throw new CoreException('switch_type(): Unknown type "'.$type.'" specified', 'unknown');
    }

    if (!empty($redirect)) {
        if (isset_get($_GET['redirect']) != $redirect) {
            /*
             * Remember this one to avoid endless redirecting (Lookin at you there, google talk!)
             */
            $_GET['redirect'] = $redirect;
            redirect($redirect, 302, false);
        }

        /*
         * Going for an endless loop, clear all, and go to main page
         */
        unset($_GET['redirect']);
    }

    if (!empty($_SERVER['HTTP_REFERER'])) {
        if (isset_get($_GET['redirect']) != $_SERVER['HTTP_REFERER']) {
            /*
             * Remember this one to avoid endless redirecting (Lookin at you there, google talk!)
             */
            $_GET['redirect'] = $_SERVER['HTTP_REFERER'];
            redirect($_SERVER['HTTP_REFERER'], 302, false);
        }

        /*
         * Going for an endless loop, clear all, and go to main page
         */
        unset($_GET['redirect']);
    }

    if (!empty($_GET['redirect'])) {
        redirect($_GET['redirect']);
    }

    redirect();

}catch(Exception $e) {
    throw new CoreException('switch_type(): Failed', $e);
}
?>
