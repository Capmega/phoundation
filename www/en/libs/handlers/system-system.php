<?php
/*
 * This is the startup sequence for system web pages, like 404, 500, etc
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>, Johan Geuze
 */



try{
    /*
     * Set timeout
     * Define basic platform constants
     *
     * NOTE: System pages may be executed by the uncaught exception handler
     * which defines these constants by itself. Because of this, first check if
     * ADMIN is defined. If so, all variables are already defined.
     */
    if(!defined('ADMIN')){
        set_timeout();

        define('ADMIN'   , '');
        define('PWD'     , slash(isset_get($_SERVER['PWD'])));
        define('STARTDIR', slash(getcwd()));
        define('FORCE'   , (getenv('FORCE')   ? 'FORCE'   : null));
        define('TEST'    , (getenv('TEST')    ? 'TEST'    : null));
        define('QUIET'   , (getenv('QUIET')   ? 'QUIET'   : null));
        define('LIMIT'   , (getenv('LIMIT')   ? 'LIMIT'   : $_CONFIG['paging']['limit']));
        define('ORDERBY' , (getenv('ORDERBY') ? 'ORDERBY' : null));
        define('ALL'     , (getenv('ALL')     ? 'ALL'     : null));
        define('DELETED' , (getenv('DELETED') ? 'DELETED' : null));
        define('STATUS'  , (getenv('STATUS')  ? 'STATUS'  : null));
    }



    /*
     * Load basic libraries
     */
    load_libs('route,http,html,inet,cache'.(empty($_CONFIG['cdn']['enabled']) ? '' : ',cdn'));



    /*
     * Check OPTIONS request.
     * If options was requested, just return basic HTTP headers
     */
    // :TODO: Should pages themselves not check for this and perhaps send other headers?
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        http_headers(200, 0);
        die();
    }



    /*
     * Set security umask
     */
    umask($_CONFIG['security']['umask']);



    /*
     * Setup locale and character encoding
     */
    ini_set('default_charset', $_CONFIG['encoding']['charset']);

    foreach($_CONFIG['locale'] as $key => $value){
        if($value){
            setlocale($key, $value);
        }
    }



    /*
     * Prepare for unicode usage
     */
    if($_CONFIG['encoding']['charset'] = 'UTF-8'){
        mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

        if(function_exists('mb_internal_encoding')){
            mb_internal_encoding('UTF-8');
        }
    }



    /*
     * Check for configured maintenance mode
     */
    if($_CONFIG['maintenance']){
        /*
         * We are in maintenance mode, have to show mainenance page.
         */
        page_show(503);
    }



    /*
     * Set cookie, start session where needed, etc.
     */
    include(ROOT.'libs/handlers/system-manage-session.php');



    /*
     * Set timezone
     * See http://www.php.net/manual/en/timezones.php for more info
     */
    try{
        date_default_timezone_set($_CONFIG['timezone']['system']);

    }catch(Exception $e){
        /*
         * Users timezone failed, use the configured one
         */
        notify($e);
    }

    define('TIMEZONE', isset_get($_SESSION['user']['timezone'], $_CONFIG['timezone']['display']));



    /*
     * Set language data
     *
     * This is normally done by checking the current dirname of the startup file,
     * this will be LANGUAGECODE/libs/handlers/system-webpage.php
     */
    try{
        /*
         * Language is defined by the www/LANGUAGE dir that is used.
         */
        $language = substr(__DIR__, -16, 2);

        define('LANGUAGE', $language);
        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));

        /*
         * Ensure $_SESSION['language'] available
         */
        if(empty($_SESSION['language'])){
            $_SESSION['language'] = LANGUAGE;
        }

    }catch(Exception $e){
        /*
         * Language selection failed
         */
        if(!defined('LANGUAGE')){
            define('LANGUAGE', 'en');
        }

        $e = new BException('core::startup(): Language selection failed', $e);
    }



    /*
     * System pages cannot do $_POST requests
     */
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $_POST = array();
        throw new BException(tr('core::startup(): system pages cannot do POST requests'), '400');
    }



    /*
     * Load custom library, if available
     * Set the CDN url for javascript
     * Validate HTTP GET
     */
    load_libs('custom');
    html_set_js_cdn_url();
    http_validate_get();



    /*
     * This is a 400, 403, 404, 500, 503, etc page.
     * Ensure at least that we're not returning HTML if a different type of file was requested.
     */
    switch($this->register['accepts']){
        case 'text/html':
            /*
             * Show the standard HTML system page
             */
            break;

        default:
            /*
             * Something else was requested, only send headers with the requested accept header as Content-Type
             */
            http_headers(null, 0);
            die();

    }

}catch(Exception $e){
    throw new BException(tr('core::system(): Failed'), $e);
}
