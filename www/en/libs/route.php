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
 * The first argument is the PERL compatible regular expression that will match the URL you wish to route to a page. Note that this must be a FULL regular expression with opening and closing tags. / is recommended for these tags, but not required. See https://www.php.net/manual/en/function.preg-match.php for more information about PERL compatible regular expressions. This regular expression may capture variables which then can be used in the target as $1, $2 for the first and second variable respectitively. Regular expression flags like i (case insensitive matches), u (unicode matches), etc. may be added after the trailing / of this variable
 *
 * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular expression captured variables, you may use these variables here. If the page name itself is a variable, then route() will try to find that page, and execute it if it exists
 *
 * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
 * A                Process the target as an attachement (i.e. Send the file so that the browser client can download it)
 * B                Block. Return absolutely nothing
 * C                Use URL cloaking. A cloaked URL is basically a random string that the route() function can look up in the `cloak` table. domain() and its related functions will generate these URL's automatically. See the "url" library, and domain() and related functions for more information
 * D                Add HTTP_HOST to the REQUEST_URI before applying the match
 * G                The request must be GET to match
 * H                If the routing rule matches, the router will add a *POSSIBLE HACK ATTEMPT DETECTED* log entry for later processing
 * L                Disable language map requirements for this specific URL (Use this with non language URLs on a multi lingual site)
 * P                The request must be POST to match
 * M                Add queries into the REQUEST_URI before applying the match, autmatically implies Q
 * N                Do not check for permanent routing rules
 * Q                Allow queries to pass through. If NOT specified, and the URL contains queries, the URL will NOT match!
 * QKEY;KEY=ACTION  Is a ; separated string containing query keys that are allowed, and if specified, what action must be taken when encountered
 * R301             Redirect to the specified page argument using HTTP 301
 * R302             Redirect to the specified page argument using HTTP 302
 * S$SECONDS$       Store the specified rule for this IP and apply it for $SECONDS$ amount of seconds. $SECONDS$ is optional, and defaults to 86400 seconds (1 day). This works well to auto 404 IP's that are doing naughty things for at least a day
 * X$PATHS$         Restrict access to the specified dot-comma separated $PATHS$ list. $PATHS is optional and defaults to ROOT.'www,'.ROOT.'data/content/downloads'
 *
 * The $verbose and $veryverbose variables here are to set the system in VERBOSE or VERYVERBOSE mode, but ONLY if the system runs in debug mode. The former will add extra log output in the data/log files, the latter will add LOADS of extra log data in the data/log files, so please use with care and only if you cannot resolve the problem
 *
 * Once all route() calls have passed without result, the system will shut down. The shutdown() call will then automatically execute route_404() which will display the 404 page
 *
 * To use translation mapping, first set the language map using route_map()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route_404()
 * @see route_exec()
 * @see domain()
 * @see route_map()
 * @see route_insert_static()
 * @see https://www.php.net/manual/en/function.preg-match.php
 * @see https://regularexpressions.info/ NOTE: The site currently has broken SSL, but is one of the best resources out there to learn regular expressions
 * @table: `routes_static`
 * @version 1.27.0: Added function and documentation
 * @version 2.0.7: Now uses route_404() to display 404 pages
 * @version 2.5.63: Improved documentation
 * @example
 * code
 * // This will take phoundation.org/ and execute the index page, but not allow queries.
 * route('/\//'                                            , 'index.php'                            , '');
 *
 * // This will take phoundation.org/?test=1 and execute the index page, and allow the query.
 * route('/\//'                                            , 'index.php'                            , 'Q');
 *
 * // This rule will take phoundation.org/en/page/users-1.html and execute en/users.php?page=1
 * route('/^([a-z]{2})\/page\/([a-z-]+)?(?:-(\d+))?.html$/', '$1/$2.php?page=$3'                    , 'Q');
 *
 * // This rule will redirect phoundation.org/ to phoundation.org/en/
 * route(''                                                , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301'); // This will HTTP 301 redirect the user to a page with the same protocol, same domain, but the language that their browser requested. So for example, http://domain.com with HTTP header "accept-language:en" would HTTP 301 redirect to http://domain.com/en/
 *
 * // These are some examples for blocking hacking attempts
 * route('/\/\.well-known\//i'  , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/\/acme-challenge\//i', 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/C=S;O=A/i'           , 'en/system/404.php', 'B,H,L,M,S'); // If you request this query, you will be 404-ing for a good while
 * route('/wp-admin/i'          , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/libs\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/scripts\//i'         , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/config\//i'          , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/init\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/www\//i'             , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/data\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
 * route('/public\//i'          , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
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
// :LEGACY: 2.9 and up will have this functionality removed and only route_map() will function
        if($regex === 'map'){
            return route_map($target);
        }

        $type = ($_POST ?  'POST' : 'GET');
        $ip   = (empty($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_REAL_IP']);

        /*
         * Ensure the 404 shutdown function is registered
         */
        if(!$init){
            $init = true;
            log_file(tr('Processing ":domain" routes for ":type" type request ":url" from client ":client"', array(':domain' => $_CONFIG['domain'], ':type' => $type, ':url' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], ':client' => $_SERVER['REMOTE_ADDR'].(empty($_SERVER['HTTP_X_REAL_IP']) ? '' : ' (Real IP: '.$_SERVER['HTTP_X_REAL_IP'].')'))), 'route', 'white');
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
         * slash, URIs cannot be longer than 255 characters
         *
         * Deny URI's larger than 255 characters. If these are specified,
         * automatically 404 because this is a hard coded limit. The reason for
         * this is that the routes_static table columns currently only hold 255
         * characters and at the moment I see no reason why anyone would want
         * more than 255 characters in their URL.
         */
        $query = str_from($_SERVER['REQUEST_URI']      , '?');
        $uri   = str_starts_not($_SERVER['REQUEST_URI'], '/');
        $uri   = str_until($uri                        , '?');

        if(strlen($uri) > 255){
            log_file(tr('Requested URI ":uri" has ":count" characters, where 255 is a hardcoded limit (See route() function). 404-ing the request', array(':uri' => $uri, ':count' => strlen($uri))), 'route', 'yellow');
            route_404();
        }

        /*
         * Apply pre-matching flags. Depending on individual flags we may do
         * different things
         */
        $flags  = strtoupper($flags);
        $flags  = array_force($flags);
        $until  = false;    // By default, do not store this rule
        $block  = false;    // By default, do not block this request
        $static = true;     // By default, do check for static rules, if configured so

        foreach($flags as $flags_id => $flag){
            switch($flag[0]){
                case 'D':
                    /*
                     * Include domain in match
                     */
                    $uri = $_SERVER['HTTP_HOST'].$uri;
                    log_file(tr('Adding complete HTTP_HOST in match for URI ":uri"', array(':uri' => $uri)), 'route', 'VERYVERBOSE/green');
                    break;

                case 'M':
                    $uri .= '?'.$query;
                    log_file(tr('Adding query to URI ":uri"', array(':uri' => $uri)), 'route', 'VERYVERBOSE/green');

                    if(!str_exists(str_force($flags), 'Q')){
                        /*
                         * Auto imply Q
                         */
                        $flags[] = 'Q';
                    }

                    break;

                case 'N':
                    $static = false;
            }
        }

        if(($count === 1) and $_CONFIG['route']['static']){
            if($static){
                /*
                 * Check if remote IP is registered for special routing
                 */
                $exists = sql_get('SELECT `uri`, `regex`, `target`, `flags` FROM `routes_static` WHERE `ip` = :ip AND `status` IS NULL AND `expiredon` >= NOW() ORDER BY `createdon` DESC LIMIT 1', array(':ip' => $ip));

                if($exists){
                    /*
                     * Apply semi-permanent routing for this IP
                     */
                    log_file(tr('Found active static routing for IP ":ip", continuing routing as if request is URI ":uri" with regex ":regex", target ":target", and flags ":flags" instead', array(':ip' => $ip, ':uri' => $exists['uri'], ':regex' => $exists['regex'], ':target' => $exists['target'], ':flags' => $exists['flags'])), 'route', 'yellow');

                    $uri    = $exists['uri'];
                    $regex  = $exists['regex'];
                    $target = $exists['target'];
                    $flags  = array_force($exists['flags']);

                    unset($exists);
                }

            }else{
                log_file(tr('Not checking for static routes per N flag'), 'route', 'VERBOSE/yellow');
            }
        }

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
                        /*
                         * The protocol used in the current request
                         */
                        $route = str_replace(':PROTOCOL', $_SERVER['REQUEST_SCHEME'].'://', $route);
                        break;

                    case 'DOMAIN':
                        /*
                         * The domain used in the current request
                         */
                        $route = str_replace(':DOMAIN', $_SERVER['HTTP_HOST'], $route);
                        break;

                    case 'LANGUAGE':
                        /*
                         * The language specified in the current request
                         */
                        $route = str_replace(':LANGUAGE', LANGUAGE, $route);
                        break;

                    case 'REQUESTED_LANGUAGE':
                        /*
                         * The language requested in the current request
                         */
                        $requested = array_first($core->register['accepts_languages']);
                        $route     = str_replace(':REQUESTED_LANGUAGE', $requested['language'], $route);
                        break;

                    case 'PORT':
                        // FALLTHROUGH
                    case 'SERVER_PORT':
                        /*
                         * The port used in the current request
                         */
                        $route = str_replace(':PORT', $_SERVER['SERVER_PORT'], $route);
                        break;

                    case 'REMOTE_PORT':
                        /*
                         * The port used by the client
                         */
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
         * Apply specified post matching flags. Depending on individual flags we
         * may do different things
         */
        foreach($flags as $flags_id => $flag){
            if(!$flag){
                /*
                 * Completely ignore empty flags
                 */
                continue;
            }

            switch($flag[0]){
                case 'A':
                    /*
                     * Send the file as a downloadable attachment
                     */
                    $attachment = true;
                    break;

                case 'B':
                    /*
                     * Block this request, send nothing
                     */
                    log_file(tr('Blocking request as per B flag'), 'route', 'warning');
                    unregister_shutdown('route_404');
                    $block = true;
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
                    route_exec(current_file(1), $attachment, $restrictions);

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

                case 'H':
                    log_file(tr('*POSSIBLE HACK ATTEMPT DETECTED*'), 'route', 'yellow');
                    notify(array('code'    => 'hack',
                                 'class'   => 'hack',
                                 'title'   => tr('*Possible hack attempt detected*'),
                                 'message' => tr('The IP address ":ip" made the request ":request" which was matched by regex ":regex" with flags ":flags" and caused this notification', array(':ip'      => $ip,
                                                                                                                                                                                                ':request' => $uri,
                                                                                                                                                                                                ':regex'   => $regex,
                                                                                                                                                                                                ':flags'   => $flags))));
                    break;

                case 'L':
                    /*
                     * Disable language support
                     */
                    $disable_language = true;
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

                case 'S':
                    $until = substr($flag, 1);

                    if($until and !is_natural($until)){
                        notify(new BException(tr('route(): Specified S flag value ":value" is invalid, natural number expected. Falling back to default value of 86400', array(':value' => $until)), 'warning/invalid'));
                        $until = null;
                    }

                    if(!$until){
                        $until = 86400;
                    }

                    break;

                case 'X':
                    /*
                     * Restrict access to the specified path list
                     */
                    $restrictions = substr($flag, 1);
                    $restrictions = str_replace(';', ',', $restrictions);
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
         * Translate the route?
         */
        if(isset($core->register['route_map']) and empty($disable_language)){
            /*
             * Found mapping configuration. Find language match. Assume
             * that $matches[1] contains the language, unless specified
             * otherwise
             */
            if(isset($core->register['route_map']['language'])){
                $language = isset_get($matches[$core->register['route_map']['language']][0]);

            }else{
                $language = isset_get($matches[1][0]);
            }

            if($language !== 'en'){
                /*
                 * Requested page is in a non-English language. This means that
                 * the entire URL MUST be in that language. Translate the URL to
                 * its English counterpart
                 */
                $translated = false;

                /*
                 * Check if route map has the requested language
                 */
                if(empty($core->register['route_map'][$language])){
                    log_file(tr('Requested language ":language" does not have a language map available', array(':language' => $language)), 'route', 'yellow');
                    unregister_shutdown('route_404');
                    route_404();

                }else{
                    /*
                     * Found a map for the requested language
                     */
                    log_file(tr('Attempting to remap for language ":language"', array(':language' => $language)), 'route', 'VERBOSE/cyan');

                    foreach($core->register['route_map'][$language] as $unknown => $remap){
                        if(strpos($page, $unknown) !== false){
                            $translated = true;
                            $page       = str_replace($unknown, $remap, $page);
                        }
                    }

                    if(!file_exists($page)){
                        log_file(tr('Language remapped page ":page" does not exist', array(':page' => $page)), 'route', 'VERBOSE/yellow');
                        unregister_shutdown('route_404');
                        route_404();
                    }

                    log_file(tr('Found remapped page ":page"', array(':page' => $page)), 'route', 'VERBOSE/green');
                }

                if(!$translated){
                    /*
                     * Page was not translated, ie its still the original and
                     * no translation was found.
                     */
                    log_file(tr('Requested language ":language" does not have a translation available in the language map for page ":page"', array(':language' => $language, ':page' => $page)), 'route', 'yellow');
                    unregister_shutdown('route_404');
                    route_404();
                }
            }
        }

        /*
         * Check if configured page exists
         */
        if(!file_exists($page)){
            if(isset($dynamic_pagematch)){
                log_file(tr('Dynamically matched page ":page" does not exist', array(':page' => $page)), 'route', 'VERBOSE/yellow');
                $count++;
                return false;

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

        if(isset($core->register['route_map'])){
            foreach($core->register['route_map'] as $code => &$map){
                $map = array_flip($map);
            }
        }

        unset($map);

        if($until){
            /*
             * Store the request as a static rule until it expires
             *
             * Apply semi-permanent routing for this IP
             *
             * Remove the "S" flag since we don't want to store the rule again
             * in subsequent loads
             *
             * Remove the "H" flag since subsequent requests may not be a hack
             * attempt. Since we are going to act as if the static rule AND URI
             * apply, we don't know really, avoid unneeded red flags
             */
            $flags = array_force($flags);

            foreach($flags as $id => $flag){
                switch($flag[0]){
                    case 'H':
                        // FALLTHROUGH
                    case 'S':
                        unset($flags[$id]);
                        break;
                }
            }

            route_insert_static(array('expiredon' => $until,
                                      'target'    => $target,
                                      'regex'     => $regex,
                                      'flags'     => $flags,
                                      'uri'       => $uri,
                                      'ip'        => $ip));
        }

        if($block){
            /*
             * Block the request by dying
             */
            die();
        }

        route_exec($page, $attachment, $restrictions);

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
 * @param list $restrictions If specified, apply the specified file system restrictions, which may block the request if the requested file is outside of these restrictions
 * @return void
 */
function route_exec($target, $attachment, $restrictions){
    global $_CONFIG, $core;

    try{
        $core->register['route_exec'] = $target;

        if(substr($target, -3, 3) === 'php'){
            if($attachment){
                throw new BException(tr('route_exec(): Found "A" flag for executable target ":target", but this flag can only be used for non PHP files', array(':target' => $target)), 'access-denied');
            }

            log_file(tr('Executing page ":target"', array(':target' => $target)), 'route', 'VERYVERBOSE/cyan');

            /*
             * Auto start the phoundation core
             */
            if(empty($core->register['startup'])){
                $core->startup();
            }

            include($target);

        }else{
            if($attachment){
                /*
                 * Upload the file to the client as an attachment
                 */
                $target = file_absolute(unslash($target), ROOT.'www/');

                log_file(tr('Sending file ":target" as attachment', array(':target' => $target)), 'route', 'VERYVERBOSE/cyan');
                file_http_download(array('restrictions' => $restrictions,
                                         'attachment'   => $attachment,
                                         'file'         => $target,
                                         'filename'     => basename($target)));

            }else{
                $mimetype = mime_content_type($target);
                $bytes    = filesize($target);

                log_file(tr('Sending contents of file ":target" with mime-type ":type" directly to client', array(':target' => $target, ':type' => $mimetype)), 'route', 'VERYVERBOSE/cyan');

                header('Content-Type: '.$mimetype);
                header('Content-length: '.$bytes);

                include($target);
            }
        }

        die();

    }catch(Exception $e){
        throw new BException(tr(tr('route_exec(): Failed to execute page ":target"', array(':target' => $target))), $e);
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
        $core->register['route_exec']  = 'en/404.php';
        $core->register['script_path'] = 'system/404';
        $core->register['script']      = 404;

        /*
         * Auto start the phoundation core if configured to do so
         */
        if(!empty($GLOBALS['route_start'])){
            $core->startup();
        }

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



/*
 * Specify a language routing map for multi lingual websites
 *
 * The translation map helps route() to detect URL's where the language is native. For example; http://phoundation.org/about.html and http://phoundation.org/nosotros.html should both route to about.php, and maybe you wish to add multiple languages for this. The routing table basically says what static words should be translated to their native language counterparts. The domain() function uses this table as well when generating URL's. See domain() for more information
 *
 * The translation mapping table should have the following format:
 *
 * array(FIRST_LANGUAGE_CODE => array(ENGLISH_WORD => FIRST_LANGUAGE_CODE_WORD,
 *                                    ENGLISH_WORD => FIRST_LANGUAGE_CODE_WORD,
 *                                    ENGLISH_WORD => FIRST_LANGUAGE_CODE_WORD,
 *                                    ENGLISH_WORD => ...),
 *
 *       SECOND_LANGUAGE_CODE => array(ENGLISH_WORD => SECOND_LANGUAGE_CODE_WORD,
 *                                     ENGLISH_WORD => SECOND_LANGUAGE_CODE_WORD,
 *                                     ENGLISH_WORD => SECOND_LANGUAGE_CODE_WORD,
 *                                     ENGLISH_WORD => ...),
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route()
 * @version 2.8.4: Added function and documentation
 * @example This example configures a language map for spanish (es) and dutch (nl)
 * code
 * route('map', array('es' => array('portafolio'   => 'portfolio',
 *                                  'servicios'    => 'services',
 *                                  'contacto'     => 'contact',
 *                                  'nosotros'     => 'about',
 *                                  'index'        => 'index'),
 *
 *                    'nl' => array('portefeuille' => 'portfolio',
 *                                  'diensten'     => 'services',
 *                                  'contact'      => 'contact',
 *                                  'over-ons'     => 'about',
 *                                  'index'        => 'index')));
 *
 * /code
 *
 * @param array $map The language mapping array
 * @return void
 */
function route_map($map){
    global $core;

    try{
        log_file(tr('Setting URL map'), 'route', 'VERYVERBOSE/cyan');
        $core->register['route_map'] = $map;

    }catch(Exception $e){
        throw new BException(tr('route_map(): Failed'), $e);
    }
}



/*
 * Insert a static route
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `template`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example This example configures a language map for spanish (es) and dutch (nl)
 * code
 * route('map', array('es' => array('portafolio'   => 'portfolio',
 *                                  'servicios'    => 'services',
 *                                  'contacto'     => 'contact',
 *                                  'nosotros'     => 'about',
 *                                  'index'        => 'index'),
 *
 *                    'nl' => array('portefeuille' => 'portfolio',
 *                                  'diensten'     => 'services',
 *                                  'contact'      => 'contact',
 *                                  'over-ons'     => 'about',
 *                                  'index'        => 'index')));
 *
 * /code
 *
 * @param params $params A parameters array
 * @param string $params[foo]
 * @param string $params[bar]
 * @return string The result
 */
function route_insert_static($route){
    try{
        $route = route_validate_static($route);

        log_file(tr('Storing static routing rule ":rule" for IP ":ip"', array(':rule' => $route['target'], ':ip' => $route['ip'])), 'route', 'VERYVERBOSE/cyan');

        sql_query('INSERT INTO `routes_static` (`expiredon`                                , `meta_id`, `ip`, `uri`, `regex`, `target`, `flags`)
                   VALUES                      (DATE_ADD(NOW(), INTERVAL :expiredon SECOND), :meta_id , :ip , :uri , :regex , :target , :flags )',

                   array(':expiredon' => $route['expiredon'],
                         ':meta_id'   => meta_action(),
                         ':ip'        => $route['ip'],
                         ':uri'       => $route['uri'],
                         ':regex'     => $route['regex'],
                         ':target'    => $route['target'],
                         ':flags'     => $route['flags']));

    }catch(Exception $e){
        throw new BException(tr('route_insert_static(): Failed'), $e);
    }
}



/*
 * Validate a static route
 *
 * This function will validate all relevant fields in the specified $route array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param array $route
 * @return string HTML for a categories select box within the specified parameters
 */
function route_validate_static($route){
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($route, 'uri,regex,target,until,ip');

        $route['flags'] = str_force($route['flags']);

        $v->hasMaxChars($route['uri'], 255, tr('Please ensure uri is less than 255 characters'));
        $v->isFilter($route['ip'], FILTER_VALIDATE_IP, tr('Please specify a valid IP address'));
        $v->hasMaxChars($route['flags'], 16, tr('Please ensure the flags is less than 16 characters'));

        if($route['regex']){
            $v->hasMaxChars($route['regex'], 255, tr('Please ensure regex is less than 255 characters'));

        }else{
            $route['regex'] = '';
        }

        if($route['target']){
            $v->hasMaxChars($route['target'], 255, tr('Please ensure target is less than 255 characters'));

        }else{
            $route['target'] = '';
        }

        $v->isValid();

        return $route;

    }catch(Exception $e){
        throw new BException(tr('route_validate_static(): Failed'), $e);
    }
}