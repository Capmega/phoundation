<?php
/*
 * This is the startup sequence for normal web pages
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



try{
    /*
     * Set timeout
     * Define basic platform constants
     */
    set_timeout();



    /*
     * Define basic platform constants
     */
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



    /*
     * Load basic libraries
     */
    load_libs('html,inet,cache'.(empty($_CONFIG['cdn']['enabled']) ? '' : ',cdn'));



    /*
     * Check HEAD and OPTIONS requests.
     * If HEAD was requested, just return basic HTTP headers
     */
// :TODO: Should pages themselves not check for this and perhaps send other headers?
    switch($_SERVER['REQUEST_METHOD'] ) {
        case 'OPTIONS':
under_construction();
    }



    /*
     * Set security umask
     */
    umask($_CONFIG['security']['umask']);



    /*
     * Set language data
     *
     * This is normally done by checking the current dirname of the startup file,
     * this will be LANGUAGECODE/libs/handlers/system-webpage.php
     */
    try{
        if($_CONFIG['language']['supported']) {
            /*
             * Language is defined by the www/LANGUAGE dir that is used.
             */
            if(empty($this->register['route_exec'])) {
                $url      = $_SERVER['REQUEST_URI'];
                $url      = Strings::startsNotWith($url, '/');
                $language = Strings::until($url, '/');

                if(!array_key_exists($language, $_CONFIG['language']['supported'])) {
                    log_console(tr('Detected language ":language" is not supported, falling back to default. See $_CONFIG[language][supported]', array(':language' => $language)), 'VERBOSE/warning');
                    $language = $_CONFIG['language']['default'];
                }

            } else {
                $language = substr($this->register['route_exec'], 0, 2);

                if(!array_key_exists($language, $_CONFIG['language']['supported'])) {
                    log_console(tr('Detected language ":language" is not supported, falling back to default. See $_CONFIG[language][supported]', array(':language' => $language)), 'VERBOSE/warning');
                    $language = $_CONFIG['language']['default'];
                }
            }

        } else {
            $language = $_CONFIG['language']['default'];
        }

        define('LANGUAGE', $language);
        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));

        /*
         * Ensure $_SESSION['language'] available
         */
        if(empty($_SESSION['language'])) {
            $_SESSION['language'] = LANGUAGE;
        }

    }catch(Exception $e) {
        /*
         * Language selection failed
         */
        if(!defined('LANGUAGE')) {
            define('LANGUAGE', 'en');
        }

        $e = new OutOfBoundsException('core::startup(): Language selection failed', $e);
    }

    define('LIBS', ROOT.'www/'.LANGUAGE.'/libs/');



    /*
     * Setup locale and character encoding
     */
    ini_set('default_charset', $_CONFIG['encoding']['charset']);
    $this->register('locale', set_locale());



    /*
     * Prepare for unicode usage
     */
    if($_CONFIG['encoding']['charset'] = 'UTF-8') {
        mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

        if(function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
    }



    /*
     * Check for configured maintenance mode
     */
    if($_CONFIG['maintenance']) {
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

    }catch(Exception $e) {
        /*
         * Users timezone failed, use the configured one
         */
        notify($e);
    }

    define('TIMEZONE', isset_get($_SESSION['user']['timezone'], $_CONFIG['timezone']['display']));



    /*
     * If POST request, automatically untranslate translated POST entries
     */
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        html_untranslate();
        html_fix_checkbox_values();

        if($_CONFIG['security']['csrf']['enabled'] === 'force') {
            /*
             * Force CSRF checks on every submit!
             */
            check_csrf();
        }
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
     * Did the startup sequence encounter reasons for us to actually show another
     * page?
     */
    if(isset($core->register['page_show'])) {
        page_show($core->register['page_show']);
    }

}catch(Exception $e) {
    throw new OutOfBoundsException(tr('core::http(): Failed'), $e);
}
