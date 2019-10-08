<?php
if(!$_SESSION['first_visit']){
    /*
     * Set first_visit to 2 so that it will still be 1 after the redirect back to the site
     */
    $_SESSION['first_visit'] = 2;
}

/*
 * Redirect back to the site.
 */
if(empty($_GET['redirect'])){
    log_file(tr('No return redirect for root_cookie script specified, showing 404 page instead as nobody should arrive here by hand'), 'root_cookie', 'warning');
    page_show(404);

}else{
    $_GET['redirect'] = urldecode($_GET['redirect']);

    /*
     * The redirect domain must be a sub domain of this root domain
     */
    if(!str_exists($_GET['redirect'], $_SESSION['domain'])){
        /*
         * Invalid redirect domain, ignore entire redirect and go to the
         * home page of the root domain
         */
        log_file(tr('Specified return redirect ":return" for root_cookie script is invalid, it should be a sub domain of ":domain". Showing 404 instead', array(':domain' => domain(), ':return' => $_GET['redirect'])), 'root_cookie', 'warning');
        page_show(404);
    }
}

redirect($_GET['redirect']);
