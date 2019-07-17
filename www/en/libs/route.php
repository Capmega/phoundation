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
 * Load the system library so that system functions are available
 * Create core object for minimal functionality
 */
require_once(__DIR__.'/system.php');



/*
 * Route the request uri from the client to the correct php file
 *
 * The route() call requires 3 arguments; $regex, $target, and $flags.
 *
 * The first argument is the regular expression that will match the URL you wish to route to a page. This regular expression may capture variables
 *
 * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular expression captured variables, you may use these variables here. If the page name itself is a variable, then route() will try to find that page, and execute it if it exists
 *
 * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
 * Q Allow queries to pass through. If NOT specified, and the URL contains queries, the URL will NOT match!
 * QKEY;KEY=ACTION Is a ; separated string containing query keys that are allowed, and if specified, what action must be taken when encountered
 * R301 Redirect to the specified page argument using HTTP 301
 * R302 Redirect to the specified page argument using HTTP 302
 * P The request must be POST to match
 * G The request must be GET to match
 * C Use URL cloaking. A cloaked URL is basically a random string that the route() function can look up in the `cloak` table. domain() and its related functions will generate these URL's automatically. See the "url" library, and domain() and related functions for more information
 *
 * The $verbose and $veryverbose variables here are to set the system in VERBOSE or VERYVERBOSE mode, but ONLY if the system runs in debug mode. The former will add extra log output in the data/log files, the latter will add LOADS of extra log data in the data/log files, so please use with care and only if you cannot resolve the problem
 *
 * The translation map helps route() to detect URL's where the language is native. For example; http://phoundation.org/about.html and http://phoundation.org/nosotros.html should both route to about.php, and maybe you wish to add multiple languages for this. The routing table basically says what static words should be translated to their native language counterparts. The mapped_domain() function use this table as well when generating URL's. See mapped_domain() for more information
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route_404()
 * @see route_send()
 * @see mapped_domain()
 * @table: `route`
 * @version 1.27.0: Added function and documentation
 * @version 2.0.7: Now uses route_404() to display 404 pages
 * @version 2.5.63: Improved documentation
 * @example
 * code
 * route('/\//'                                            , 'index'                                , '');     // This would NOT allow queries, and the match would fail
 * route('/\//'                                            , 'index'                                , 'Q');    // This would allow queries
 * route('/^([a-z]{2})\/page\/([a-z-]+)?(?:-(\d+))?.html$/', '$1/$2.php?page=$3'                    , 'Q');    // This would map a URL like en/page/users-4.html to ROOT/en/users.php?page=4 while also allowing queries to be passed as well.
 * route(''                                                , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301'); // This will HTTP 301 redirect the user to a page with the same protocol, same domain, but the language that their browser requested. So for example, http://domain.com with HTTP header "accept-language:en" would HTTP 301 redirect to http://domain.com/en/
 * /code
 *
 * The following example code will set a language route map where the matched word "from" would be translated to "to" and "foor" to "bar" for the language "es"
 *
 * code
 * route('map', array('language' => 2,
 *                    'es'       => array('servicios'    => 'services',
 *                                        'portafolio'   => 'portfolio'),
 *                    'nl'       => array('diensten'     => 'services',
 *                                        'portefeuille' => 'portfolio')));
 * route('/\//', 'index')
 * /code
 *
 * @example Setup URL translations map. In this example, URL's with /es/ with the word "conferencias" would map to the word "conferences", etc.
 * code
 * route('map', array('es' => array('conferencias' => 'conferences',
 *                                  'portafolio'   => 'portfolio',
 *                                  'servicios'    => 'services',
 *                                  'nosotros'     => 'about'),
 *
 *                   'nl' => array('conferenties' => 'conferences',
 *                                  'portefeuille' => 'portfolio',
 *                                  'diensten'     => 'services',
 *                                  'over-ons'     => 'about')));
 * /code
 *
 * @param string $regex
 * @params string $target
 * @params null string $flags
 * @return void
 */
function route($regex, $target, $flags = null){
    global $_CONFIG, $core;
    static $count = 1,
           $init  = false;

    try{
        if($regex === 'map'){
            log_file(tr('Setting URL map'), 'route', 'VERYVERBOSE/cyan');
            $core->register['routemap'] = $target;
            return true;
        }

        $type = ($_POST ?  'POST' : 'GET');

        /*
         * Ensure the 404 shutdown function is registered
         */
        if(!$init){
            $init = true;
            log_file(tr('Processing ":domain" routes for ":type" type request ":url" from client ":client"', array(':domain' => $_CONFIG['domain'], ':type' => $type, ':url' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], ':client' => $_SERVER['REMOTE_ADDR'])), 'route', 'white');
            register_shutdown('route_404', null);
        }

        if(!$regex){
            /*
             * Match an empty string
             */
            $regex = '/^$/';
        }

        /*
         * Cleanup the request URI by removing all GET requests and the leading
         * slash
         */
        $uri = str_starts_not($_SERVER['REQUEST_URI'], '/');
        $uri = str_until($uri                        , '?');

        /*
         * Match the specified regex. If there is no match, there is nothing
         * else to do for us here
         */
        log_file(tr('Testing rule ":count" ":regex" on ":type" ":url"', array(':count' => $count, ':regex' => $regex, ':type' => $type, ':url' => $uri)), 'route', 'VERYVERBOSE/cyan');

        $match = preg_match_all($regex, $uri, $matches);

        if(!$match){
            $count++;
            return false;
        }

        if(VERBOSE){
            log_file(tr('Regex ":count" ":regex" matched with matches ":matches"', array(':count' => $count, ':regex' => $regex, ':matches' => $matches)), 'route', 'green');
        }

        $route        = $target;
        $attachment   = false;
        $restrictions = ROOT.'www,'.ROOT.'data/content/downloads';

        /*
         * Regex matched. Do variable substitution on the target.
         */
        if(preg_match_all('/:([A-Z_]+)/', $target, $variables)){
            array_shift($variables);

            foreach(array_shift($variables) as $variable){
                switch($variable){
                    case 'PROTOCOL':
                        $route = str_replace(':PROTOCOL', $_SERVER['REQUEST_SCHEME'].'://', $route);
                        break;

                    case 'DOMAIN':
                        $route = str_replace(':DOMAIN', $_SERVER['HTTP_HOST'], $route);
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
                        throw new BException(tr('route(): Unknown variable ":variable" found in target ":target"', array(':variable' => ':'.$variable, ':target' => ':'.$target)), 'unknown');
                }
            }
        }

        /*
         * Apply regex variables replacements
         */
        if(preg_match_all('/\$(\d+)/', $route, $replacements)){
            if(preg_match('/\$\d+\.php/', $route)){
                $dynamic_pagematch = true;
            }

            foreach($replacements[1] as $replacement){
                try{
                    if(!$replacement[0] or empty($matches[$replacement[0]])){
                        throw new BException(tr('route(): Non existing regex replacement ":replacement" specified in route ":route"', array(':replacement' => '$'.$replacement[0], ':route' => $route)), 'invalid');
                    }

                    $route = str_replace('$'.$replacement[0], $matches[$replacement[0]][0], $route);

                }catch(Exception $e){
                    log_file(tr('Ignoring regex ":regex" because route ":route" has error ":e"', array(':regex' => $regex, ':route' => $route, ':e' => $e->getMessage())), 'route', 'yellow');
                }
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
        $flags = array_force($flags);

        foreach($flags as $flags_id => $flag){
            switch($flag[0]){
                case 'A':
                    /*
                     * Send the file as a downloadable attachment
                     */
                    $attachment = true;
                    break;

                case 'C':
                    /*
                     * URL cloaking was used. See if we have a real URL behind
                     * the specified cloak
                     */
                    load_libs('url');

                    $_SERVER['REQUEST_URI'] = url_decloak($route);

                    if(!$_SERVER['REQUEST_URI']){
                        log_file(tr('Specified cloaked URL ":cloak" does not exist, cancelling match', array(':cloak' => $route)), 'route', 'VERYVERBOSE');

                        $count++;
                        return false;
                    }

                    $_SERVER['REQUEST_URI'] = str_from($_SERVER['REQUEST_URI'], '://');
                    $_SERVER['REQUEST_URI'] = str_from($_SERVER['REQUEST_URI'], '/');

                    log_file(tr('Redirecting cloaked URL ":cloak" internally to ":url"', array(':cloak' => $route, ':url' => $_SERVER['REQUEST_URI'])), 'route', 'VERYVERBOSE');

                    $count = 1;
                    unset($flags[$flags_id]);
                    route_send(current_file(1), $attachment, $restrictions);

                case 'G':
                    /*
                     * MUST be a GET reqest, NO POST data allowed!
                     */
                    if(!empty($_POST)){
                        log_file(tr('Matched route ":route" allows only GET requests, cancelling match', array(':route' => $route)), 'route', 'VERYVERBOSE');

                        $count++;
                        return false;
                    }

                    break;

                case 'P':
                    /*
                     * MUST be a POST reqest, NO EMPTY POST data allowed!
                     */
                    if(empty($_POST)){
                        log_file(tr('Matched route ":route" allows only POST requests, cancelling match', array(':route' => $route)), 'route', 'VERYVERBOSE');

                        $count++;
                        return false;
                    }

                    break;

                case 'Q':
                    /*
                     * Let GET request queries pass through
                     */
                    if(strlen($flag) === 1){
                        $get = true;
                        break;
                    }

                    $get = explode(';', substr($flag, 1));
                    $get = array_flip($get);
                    break;

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
                            throw new BException(tr('route(): Invalid R flag HTTP CODE ":code" specified for target ":target"', array(':code' => ':'.$http_code, ':target' => ':'.$target)), 'invalid');
                    }

                    /*
                     * We are going to redirect so we no longer need to default
                     * to 404
                     */
                    load_libs('inet');
                    log_file(tr('Redirecting to ":route" with HTTP code ":code"', array(':route' => $route, ':code' => $http_code)), 'route', 'VERYVERBOSE/cyan');
                    unregister_shutdown('route_404');
                    redirect(url_add_query($route, $_GET), $http_code);

                case 'X':
                    /*
                     * Restrict access to the specified path list
                     */
                    $restrictions = substr($flag, 1);
                    break;
            }
        }

        /*
         * Do we allow any $_GET queries from the REQUEST_URI?
         */
        if(empty($get)){
            if(!empty($_GET)){
                /*
                 * Client specified variables on a URL that does not allow
                 * queries, cancel the match
                 */
                log_file(tr('Matched route ":route" does not allow query variables while client specified them, cancelling match', array(':route' => $route)), 'route', 'VERYVERBOSE/yellow');

                $count++;
                return false;
            }

        }elseif($get !== true){
            /*
             * Only allow specific query keys. First check all allowed query
             * keys if they have actions specified
             */
            foreach($get as $key => $value){
                if(str_exists($key, '=')){
                    /*
                     * Regenerate the key as a $key => $value instead of $key=$value => null
                     */
                    $get[str_until($key, '=')] = str_from ($key, '=');
                    unset($get[$key]);
                }
            }

            /*
             * Go over all $_GET variables and ensure they're allowed
             */
            foreach($_GET as $key => $value){
                /*
                 * This key must be allowed, or we're done
                 */
                if(empty($get[$key])){
                    log_file(tr('Matched route ":route" contains GET key ":key" which is not specifically allowed, cancelling match', array(':route' => $route, ':key' => $key)), 'route', 'VERYVERBOSE/yellow');

                    $count++;
                    return false;
                }

                /*
                 * Okay, the key is allowed, yay! What action are we going to
                 * take?
                 */
                switch($get[$key]){
                    case null:
                        break;

                    case 301:
                        /*
                         * Redirect to URL without query
                         */
                        $domain = domain(true);
                        $domain = str_until($domain, '?');

                        log_file(tr('Matched route ":route" allows GET key ":key" as redirect to URL without query', array(':route' => $route, ':key' => $key)), 'route', 'VERYVERBOSE/yellow');
                        unregister_shutdown('route_404');
                        redirect($domain);
                }
            }
        }

        /*
         * Split the route into the page name and GET requests
         */
        $page = str_until($route, '?');
        $get  = str_from($route , '?', 0, true);

        /*
         * Check if configured page exists
         */
        if(!file_exists($page)){
            if(isset($dynamic_pagematch)){
                log_file(tr('Dynamically matched page ":page" does not exist', array(':page' => $page)), 'route', 'VERBOSE/yellow');
                $cancel = true;

                /*
                 * Page doesn't exist. Maybe a URL section is mapped?
                 */
                if(isset($core->register['routemap'])){
                    /*
                     * Found mapping configuration. Find language match. Assume
                     * that $matches[1] contains the language, unless specified
                     * otherwise
                     */
                    if(isset($core->register['routemap']['language'])){
                        $match = isset_get($matches[$core->register['routemap']['language']][0]);

                    }else{
                        $match = isset_get($matches[1][0]);
                    }

                    if(isset($core->register['routemap'][$match])){
                        /*
                         * Found a map for the requested language
                         */
                        log_file(tr('Attempting to remap for language ":language"', array(':language' => $match)), 'route', 'VERBOSE/cyan');

                        foreach($core->register['routemap'][$match] as $unknown => $remap){
                            $page = str_replace($unknown, $remap, $page);
                        }

                        if(file_exists($page)){
                            log_file(tr('Found remapped page ":page"', array(':page' => $page)), 'route', 'VERBOSE/green');
                            $cancel = false;

                        }else{
                            log_file(tr('Remapped page ":page" does not exist either', array(':page' => $page)), 'route', 'VERBOSE/yellow');
                        }
                    }
                }

                if($cancel){
                    /*
                     * Could not find any file, even with potential remapping.
                     * Cancel match
                     */
                    log_file(tr('No pages found, cancelling match'), 'route', 'VERYVERBOSE');

                    $count++;
                    return false;
                }

            }else{
                /*
                 * The hardcoded file for the regex does not exist, oops!
                 */
                log_file(tr('Matched hard coded page ":page" does not exist', array(':page' => $page)), 'route', 'yellow');
                unregister_shutdown('route_404');
                route_404();
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
         * We are going to show the matched page so we no longer need to default
         * to 404
         */
        unregister_shutdown('route_404');

        /*
         * Execute the page specified in $target (from here, $route)
         * Update the current running script name
         *
         * Flip the routemap keys <=> values foreach language so that its
         * now english keys. This way, the routemap can be easily used to
         * generate foreign language URLs
         */
        $core->register['script_path'] = $page;
        $core->register['script']      = str_rfrom($page, '/');
        $core->register['real_script'] = $core->register['script'];

        if(isset($core->register['routemap'])){
            foreach($core->register['routemap'] as $code => &$map){
                $map = array_flip($map);
            }
        }

        unset($map);
        route_send($page, $attachment, $restrictions);

    }catch(Exception $e){
        if(substr($e->getMessage(), 0, 32) == 'PHP ERROR [2] "preg_match_all():'){
            /*
             * A "user" regex failed, give pretty error
             */
            throw new BException(tr('route(): Failed to process regex :count ":regex" with error ":e"', array(':count' => $count, ':regex' => $regex, ':e' => trim(str_cut($e->getMessage(), 'preg_match_all():', '" in')))), 'syntax');
        }

        if(substr($e->getMessage(), 0, 28) == 'PHP ERROR [2] "preg_match():'){
            /*
             * A "user" regex failed, give pretty error
             */
            throw new BException(tr('route(): Failed to process regex :count ":regex" with error ":e"', array(':count' => $count, ':regex' => $regex, ':e' => trim(str_cut($e->getMessage(), 'preg_match():', '" in')))), 'syntax');
        }

        throw new BException('route(): Failed', $e);
    }
}



/*
 * Process the routed target
 *
 * We have a target for the requested route. If the resource is a PHP page, then
 * execute it. Anything else, send it directly to the client
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route()
 * @note: This function will kill the process once it has finished executing / sending the target file to the client
 * @version 2.5.88: Added function and documentation
 *
 * @param string $target The target file that should be executed or sent to the client
 * @param boolean $attachment If specified as true, will send the file as an downloadable attachement, to be written to disk instead of displayed on the browser. If set to false, the file will be sent as a file to be displayed in the browser itself.
 * @return void
 */
function route_send($target, $attachment, $restrictions){
    global $_CONFIG, $core;

    try{
        if(substr($target, -3, 3) === 'php'){
            if($attachment){
                throw new BException(tr('route_send(): Found "A" flag for executable target ":target", but this flag can only be used for non PHP files', array(':target' => $target)), 'access-denied');
            }

            log_file(tr('Executing page ":target"', array(':target' => $target)), 'route', 'VERYVERBOSE/cyan');
            include($target);

        }else{
            $target = file_absolute(unslash($target), ROOT.'www/');

            log_file(tr('Sending file ":target"', array(':target' => $target)), 'route', 'VERYVERBOSE/cyan');
            file_http_download(array('restrictions' => $restrictions,
                                     'attachment'   => $attachment,
                                     'file'         => $target,
                                     'filename'     => basename($target)));
        }

        die();

    }catch(Exception $e){
        throw new BException(tr(tr('route_send(): Failed to execute page ":target"', array(':target' => $target))), $e);
    }
}



/*
 * Show the 404 page
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route()
 * @note: This function typically would only need to be called by the route() function.
 * @note: This function dies
 * @version 2.0.5: Added function and documentation
 *
 * @return void
 */
function route_404(){
    global $core;

    try{
        $core->register['script_path'] = 'system/404';
        $core->register['script']      = 404;

        page_show(404);

    }catch(Exception $e){
        if($e->getCode() === 'not-exists'){
            log_file(tr('The system/404.php page does not exist, showing basic 404 message instead'), 'route_404', 'yellow');

            echo tr('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
                <html><head>
                <title>'.tr('404 Not Found').'</title>
                </head><body>
                <h1>'.tr('Not Found').'</h1>
                <p>'.tr('The requested URL /wer was not found on this server').'.</p>
                <hr>
                '.(!empty($_CONFIG['security']['signature']) ? '<address>Phoundation '.FRAMEWORKCODEVERSION.'</address>' : '').'
                </body></html>');
            die();
        }

        log_file(tr('The 404 page failed to show with ":e", showing basic 404 message instead', array(':e' => $e->getMessages())), 'route_404', 'yellow');

        echo tr('404 - The requested page does not exist');
        die();
    }
}
?>
