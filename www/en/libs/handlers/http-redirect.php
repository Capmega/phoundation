<?php
try{
    global $_CONFIG;

    if(PLATFORM != 'http'){
        throw new BException(tr('redirect(): This function can only be called on webservers'));
    }

    /*
     * Special targets?
     */
    if(($target === true) or ($target === 'self')){
        /*
         * Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid "confirm post submissions"
         */
        $target = $_SERVER['REQUEST_URI'];

    }elseif($target === 'prev'){
        /*
         * Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid "confirm post submissions"
         */
        $target = isset_get($_SERVER['HTTP_REFERER']);

        if(!$target or ($target == $_SERVER['REQUEST_URI'])){
            /*
             * Don't redirect to the same page! If the referrer was this page, then drop back to the index page
             */
            $target = $_CONFIG['redirects']['index'];
        }

    }elseif($target === false){
        /*
         * Special redirect. Redirect to this very page, but without query
         */
        $target = str_until($_SERVER['REQUEST_URI'], '?');

    }elseif(!$target){
        /*
         * No target specified, redirect to index page
         */
        $target = $_CONFIG['redirects']['index'];
    }

    if(empty($http_code)){
        if(is_numeric($clear_session_redirect)){
            $http_code              = $clear_session_redirect;
            $clear_session_redirect = true;

        }else{
            $http_code              = 301;
        }

    }else{
        if(is_numeric($clear_session_redirect)){
            $clear_session_redirect = true;
        }
    }

    /*
     * Validate the specified http_code, must be one of
     *
     * 301 Moved Permanently
     * 302 Found
     * 303 See Other
     * 307 Temporary Redirect
     */
    switch($http_code){
        case 301:
            // FALLTHROUGH
        case 302:
            // FALLTHROUGH
        case 303:
            // FALLTHROUGH
        case 307:
            /*
             * All valid
             */
            break;

        default:
            throw new BException(tr('redirect(): Invalid HTTP code ":code" specified', array(':code' => $http_code)), 'invalid-http-code');
    }

    if($clear_session_redirect){
        if(!empty($_SESSION)){
            unset($_GET['redirect']);
            unset($_SESSION['sso_referrer']);
        }
    }

    if((substr($target, 0, 1) != '/') and (substr($target, 0, 7) != 'http://') and (substr($target, 0, 8) != 'https://')){
        $target = $_CONFIG['url_prefix'].$target;
    }

    $target = redirect_url($target);

    if($time_delay){
        log_file(tr('Redirecting with ":time" seconds delay to url ":url"', array(':time' => $time_delay, ':url' => $target)), null, 'cyan');
        header('Refresh: '.$time_delay.';'.$target, true, $http_code);
        die();
    }

    log_file(tr('Redirecting to url ":url"', array(':url' => $target)), null, 'cyan');
    header('Location:'.redirect_url($target), true, $http_code);
    die();

}catch(Exception $e){
    throw new BException(tr('redirect(): Failed'), $e);
}
?>
