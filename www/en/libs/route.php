<?php
/*
 * Route library
 *
 * This is the URL to PHP page routing library. This library will take the client request URL and depending on specified rules, send it to a specific PHP page with $_GET variables.
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package route
 */



/*
 * Route the request uri from the client to the correct php file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `route`
 * @note: This is a note
 * @version 1.27.0: Added function and documentation
 * @example
 * code
 * route('/\//', 'index')
 * route('/\//', 'index')
 * /code
 *
 * @param string $regex
 * @params string $target
 * @params null string $flags
 * @return void
 */
function route($regex, $target, $flags = null){
    global $_CONFIG, $core;
    static $count = 1;

    try{
        /*
         * Ensure the 404 shutdown function is registered
         */
        register_shutdown('page_show', 404);

        if(!$regex){
            /*
             * Match an empty string
             */
            $regex = '/^$/';
        }

        $_SERVER['REQUEST_URI'] = str_starts_not($_SERVER['REQUEST_URI'], '/');

        /*
         * Match the specified regex. If there is no match, there is nothing
         * else to do for us here
         */
        log_file(tr('Testing rule ":count" ":regex" on ":url"', array(':count' => $count++, ':regex' => $regex, ':url' => $_SERVER['REQUEST_URI'])), 'route', 'VERYVERBOSE/cyan');

        $match  = preg_match_all($regex, $_SERVER['REQUEST_URI'], $matches);

        if(!$match){
            return false;
        }

        $route = $target;

        /*
         * Regex matched. Do variable substitution on the target. We no longer
         * need to default to 404
         */
        unregister_shutdown('page_show');

        if(preg_match_all('/:([A-Z_]+)/', $target, $variables)){
            array_shift($variables);

            foreach(array_shift($variables) as $variable){
                switch($variable){
                    case 'PROTOCOL':
                        $route = str_replace(':PROTOCOL', $_CONFIG['protocol'], $route);
                        break;

                    case 'DOMAIN':
                        $route = str_replace(':DOMAIN', $_CONFIG['domain'], $route);
                        break;

                    case 'LANGUAGE':
                        $route = str_replace(':LANGUAGE', LANGUAGE, $route);
                        break;

                    case 'REQUESTED_LANGUAGE':
                        $requested = array_first($core->register['accepts_languages']);
                        $route     = str_replace(':REQUESTED_LANGUAGE', $requested['language'], $route);
                        break;

                    case 'PORT':
                        // FALLTHROUGH

                    case 'SERVER_PORT':
                        $route = str_replace(':PORT', $_SERVER['SERVER_PORT'], $route);
                        break;

                    case 'REMOTE_PORT':
                        $route = str_replace(':REMOTE_PORT', $_SERVER['REMOTE_PORT'], $route);
                        break;

                    default:
                        throw new bException(tr('route(): Unknown variable ":variable" found in target ":target"', array(':variable' => ':'.$variable, ':target' => ':'.$target)), 'unknown');
                }
            }
        }

        /*
         * Apply regex variables replacements
         */
        if(preg_match_all('/\$(\d+)/', $route, $replacements)){
            unset($replacements[0]);

            if($route[0] == '$'){
                /*
                 * The target page itself is a regex replacement! We can only
                 * match if the file exist
                 */
                $replace_page = true;
            }

            foreach($replacements as $replacement){
                $route = str_replace('$'.$replacement[0], $matches[$replacement[0] + 1][0], $route);
            }

            if(str_exists($route, '$')){
                /*
                 * There are regex variables left that were not replaced.
                 * Replace them with nothing
                 */
                $route = preg_replace('/\$\d/', '', $route);
            }
        }

        /*
         * Apply specified flags. Depending on individual flags we may do
         * different things
         */
        foreach(array_force($flags) as $flag){
            switch($flag[0]){
                case 'R':
                    /*
                     * Validate the HTTP code to use, then redirect to the
                     * specified target
                     */
                    $http_code = substr($flag, 1);

                    switch($http_code){
                        case '':
                            $http_code = 301;
                            break;

                        case '301':
                            // FALLTHROUGH

                        case '302':
                            break;

                        default:
                            throw new bException(tr('route(): Invalid R flag HTTP CODE ":code" specified for target ":target"', array(':code' => ':'.$http_code, ':target' => ':'.$target)), 'invalid');
                    }

                    log_file(tr('Redirecting to ":route" with HTTP code ":code"', array(':route' => $route, ':code' => $http_code)), 'route', 'VERYVERBOSE/cyan');
                    redirect($route, $http_code);
            }
        }

        /*
         * Split the route into the page name and GET requests
         */
        $page = str_until($route, '?');
        $get  = str_from($route , '?', 0, true);

        if(isset($replace_page)){
            /*
             * Ensure the target page exists, else we did not match
             */
            if(!page_show($page, array('exists' => true))){
                return false;
            }
        }

        /*
         * If we have GET parameters, add them to the $_GET array
         */
        if($get){
            $get = explode('&', $get);

            foreach($get as $entry){
                $_GET[str_until($entry, '=')] = str_from($entry, '=', 0, true);
            }
        }

        /*
         * Create $_GET variables
         * Execute the page specified in $target (from here, $route)
         */
        $core->register('script', $page);
        return page_show($page);

    }catch(Exception $e){
        if(substr($e->getMessage(), 0, 28) == 'PHP ERROR [2] "preg_match():'){
            throw new bException(tr('route(): Failed to process regex ":regex" with error ":e"', array(':regex' => $regex, ':e' => trim(str_cut($e->getMessage(), 'preg_match():', '"')))), 'syntax');
        }

        throw new bException('route(): Failed', $e);
    }
}
?>
