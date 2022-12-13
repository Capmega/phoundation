<?php

namespace Phoundation\Web;

use Exception;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Date\Time;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Servers\Server;
use Phoundation\Web\Http\File;
use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Exception\RouteException;
use Throwable;



/**
 * Class Route
 *
 * Core routing class that will route URL request queries to PHP scripts in the PATH_ROOT/www/LANGUAGE_CODE/ path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Route
{
    /**
     * The template to use for these routes
     *
     * @var Template $template
     */
    protected Template $template;

    /**
     * The default server filesystem access restrictions to use while routing
     *
     * @var Server $server_restrictions
     */
    protected Server $server_restrictions;

    /**
     * The temporary server filesystem access restrictions to use while routing ONLY for the next try
     *
     * @var ?Server $temp_server
     */
    protected ?Server $temp_server = null;



    /**
     * Route constructor
     *
     * @param Template $template
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @throws Throwable
     */
    public function __construct(Template $template, Server|Restrictions|array|string|null $server_restrictions = null)
    {
        // Start the Core object, hide $_GET & $_POST
        Core::startup();
        GetValidator::hideData();
        PostValidator::hideData();

        // Set what template and default server restrictions  we'll be using
        $this->template            = $template;
        $this->server_restrictions = Core::ensureServer($server_restrictions, PATH_WWW);
    }



    /**
     * Returns a new routing object
     *
     * @param Template $template
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @return static
     */
    public static function new(Template $template, Server|Restrictions|array|string|null $server_restrictions = null): static
    {
        return new static($template, $server_restrictions);
    }



    /**
     * Try the following access restrictions only for the next try
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @return static
     */
    public function using(Server|Restrictions|array|string|null $server_restrictions = null): static
    {
        $this->temp_server = $server_restrictions;
        return $this;
    }



    /**
     * Route the request uri from the client to the correct php file
     *
     * The Route::add() call requires 3 arguments; $regex, $target, and $flags.
     *
     * The first argument is the PERL compatible regular expression that will match the URL you wish to route to a page. Note that this must be a FULL regular expression with opening and closing tags. / is recommended for these tags, but not required. See https://www.php.net/manual/en/function.preg-match.php for more information about PERL compatible regular expressions. This regular expression may capture variables which then can be used in the target as $1, $2 for the first and second variable respectitively. Regular expression flags like i (case insensitive matches), u (unicode matches), etc. may be added after the trailing / of this variable
     *
     * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular expression captured variables, you may use these variables here. If the page name itself is a variable, then Route::add() will try to find that page, and execute it if it exists
     *
     * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
     * A                Process the target as an attachement (i.e. Send the file so that the browser client can download it)
     * B                Block. Return absolutely nothing
     * C                Use URL cloaking. A cloaked URL is basically a random string that the Route::add() function can look up in the `cloak` table. domain() and its related functions will generate these URL's automatically. See the "url" library, and domain() and related functions for more information
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
     * X$PATHS$         Restrict access to the specified dot-comma separated $PATHS$ list. $PATHS is optional and defaults to PATH_ROOT.'www,'.PATH_ROOT.'data/content/downloads'
     *
     * The $Debug::enabled() and $Debug::enabled() variables here are to set the system in Debug::enabled() or Debug::enabled() mode, but ONLY if the system runs in debug mode. The former will add extra log output in the data/log files, the latter will add LOADS of extra log data in the data/log files, so please use with care and only if you cannot resolve the problem
     *
     * Once all Route::add() calls have passed without result, the system will shut down. The shutdown() call will then automatically execute Route::execute404() which will display the 404 page
     *
     * To use translation mapping, first set the language map using Route::map()
     *
     * @param string $url_regex
     * @param string $target
     * @param string $flags
     * @return bool
     * @throws RouteException|\Throwable
     * @package Web
     * @see Route::execute404()
     * @see Route::execute()
     * @see domain()
     * @see Route::map()
     * @see Route::insertStatic()
     * @see https://www.php.net/manual/en/function.preg-match.php
     * @see https://regularexpressions.info/ NOTE: The site currently has broken SSL, but is one of the best resources out there to learn regular expressions
     * @table: `routes_static`
     * @example
     * code
     * // This will take phoundation.org/ and execute the index page, but not allow queries.
     * Route::add('/\//'                                            , 'index.php'                            , '');
     *
     * // This will take phoundation.org/?test=1 and execute the index page, and allow the query.
     * Route::add('/\//'                                            , 'index.php'                            , 'Q');
     *
     * // This rule will take phoundation.org/en/page/users-1.html and execute en/users.php?page=1
     * Route::add('/^([a-z]{2})\/page\/([a-z-]+)?(?:-(\d+))?.html$/', '$1/$2.php?page=$3'                    , 'Q');
     *
     * // This rule will redirect phoundation.org/ to phoundation.org/en/
     * Route::add(''                                                , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301'); // This will HTTP 301 redirect the user to a page with the same protocol, same domain, but the language that their browser requested. So for example, http://domain.com with HTTP header "accept-language:en" would HTTP 301 redirect to http://domain.com/en/
     *
     * // These are some examples for blocking hacking attempts
     * Route::add('/\/\.well-known\//i'  , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/\/acme-challenge\//i', 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/C=S;O=A/i'           , 'en/system/404.php', 'B,H,L,M,S'); // If you request this query, you will be 404-ing for a good while
     * Route::add('/wp-admin/i'          , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/libs\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/scripts\//i'         , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/config\//i'          , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/init\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/www\//i'             , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/data\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * Route::add('/public\//i'          , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * /code
     *
     * The following example code will set a language route map where the matched word "from" would be translated to "to" and "foor" to "bar" for the language "es"
     *
     * code
     * Route::map(array('language' => 2,
     *                  'es'       => array('servicios'    => 'services',
     *                                      'portafolio'   => 'portfolio'),
     *                  'nl'       => array('diensten'     => 'services',
     *                                      'portefeuille' => 'portfolio')));
     * Route::add('/\//', 'index')
     * /code
     *
     * @example Setup URL translations map. In this example, URL's with /es/ with the word "conferencias" would map to the word "conferences", etc.
     * code
     * Route::map('es', ['conferencias' => 'conferences',
     *                   'portafolio'   => 'portfolio',
     *                   'servicios'    => 'services',
     *                   'nosotros'     => 'about']);
     *
     * Route::map('nl' => ['conferenties' => 'conferences',
     *                    'portefeuille' => 'portfolio',
     *                    'diensten'     => 'services',
     *                    'over-ons'     => 'about']);
     * /code
     *
     */
    public function try(string $url_regex, string $target, string $flags = ''): bool
    {
        static $count = 1;
        static $init  = false;

        try {
            $type = ($_POST ?  'POST' : 'GET');
            $ip   = (empty($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_REAL_IP']);

            // Ensure the post-processing function is registered
            if (!$init) {
                $init = true;
                Log::action(tr('Processing ":domain" routes for ":type" type request ":url" from client ":client"', [
                    ':domain' => Config::get('web.domains.primary.www'),
                    ':type'   => $type,
                    ':url'    => $_SERVER['REQUEST_SCHEME'].'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    ':client' => $_SERVER['REMOTE_ADDR'] . (empty($_SERVER['HTTP_X_REAL_IP']) ? '' : ' (Real IP: ' . $_SERVER['HTTP_X_REAL_IP'].')')
                ]));

                Core::registerShutdown('core_shutdown', ['\Phoundation\Web\Route', 'shutdown']);
                Core::registerShutdown('route_postprocess', [$this, 'postProcess']);
            }

            if (!$url_regex) {
                // Match an empty string
                $url_regex = '/^$/';
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
            $query = Strings::from($_SERVER['REQUEST_URI']         , '?');
            $uri   = Strings::startsNotWith($_SERVER['REQUEST_URI'], '/');
            $uri   = Strings::until($uri                           , '?');

            if (strlen($uri) > 2048) {
                Log::warning(tr('Requested URI ":uri" has ":count" characters, where 2048 is a hardcoded limit (See route() function). 404-ing the request', [
                    ':uri' => $uri,
                    ':count' => strlen($uri)
                ]));

                Route::execute404();
            }

            // Apply pre-matching flags. Depending on individual flags we may do different things
            $flags  = strtoupper($flags);
            $flags  = explode(',', $flags);
            $until  = false; // By default, do not store this rule
            $block  = false; // By default, do not block this request
            $static = true;  // By default, do check for rules, if configured so

            foreach ($flags as $flag) {
                if (!$flag) {
                    continue;
                }

                switch ($flag[0]) {
                    case 'D':
                        // Include domain in match
                        $uri = $_SERVER['HTTP_HOST'] . $uri;
                        Log::notice(tr('Adding complete HTTP_HOST in match for URI ":uri"', [':uri' => $uri]));
                        break;

                    case 'M':
                        $uri .= '?' . $query;
                        Log::notice(tr('Adding query to URI ":uri"', array(':uri' => $uri)));

                        if (!array_search('Q', $flags)) {
                            // Auto imply Q
                            $flags[] = 'Q';
                        }

                        break;

                    case 'N':
                        $static = false;
                }
            }

            if (($count === 1) and Config::get('web.route.static', false)) {
                if ($static) {
                    // Check if remote IP is registered for special routing
                    $exists = sql()->get('SELECT   `id`, `uri`, `regex`, `target`, `flags`
                                                FROM     `routes_static` 
                                                WHERE    `ip` = :ip 
                                                AND      `status` IS NULL 
                                                AND      `expiredon` >= NOW() 
                                                ORDER BY `created_on` DESC 
                                                LIMIT 1', [':ip' => $ip]);

                    if ($exists) {
                        // Apply semi-permanent routing for this IP
                        Log::warning(tr('Found active routing for IP ":ip", continuing routing as if request is URI ":uri" with regex ":regex", target ":target", and flags ":flags" instead', [
                            ':ip' => $ip,
                            ':uri' => $exists['uri'],
                            ':regex' => $exists['regex'],
                            ':target' => $exists['target'],
                            ':flags' => $exists['flags']
                        ]));

                        $uri       = $exists['uri'];
                        $url_regex = $exists['regex'];
                        $target    = $exists['target'];
                        $flags     = explode(',', $exists['flags']);

                        sql()->query('UPDATE `routes_static` SET `applied` = `applied` + 1 WHERE `id` = :id', [':id' => $exists['id']]);

                        unset($exists);
                    }

                } else {
                    Log::warning(tr('Not checking for routes per N flag'));
                }
            }

            // Match the specified regex. If there is no match, there is nothing else to do for us here
            Log::action(tr('Testing rule ":count" ":regex" on ":type" ":url"', [
                ':count' => $count,
                ':regex' => $url_regex,
                ':type' => $type,
                ':url' => $uri
            ]));

            try {
                $match = preg_match_all($url_regex, $uri, $matches);
            } catch (Exception $e) {
                throw new RouteException(tr('Failed to parse route ":route" with ":message"', [
                    ':route'   => $url_regex,
                    ':message' => Strings::until(Strings::from($e->getMessage(), 'preg_match_all(): '), ' in ')
                ]));
            }

            if (!$match) {
                $count++;
                return false;
            }

            if (Debug::enabled()) {
                Log::success(tr('Regex ":count" ":regex" matched with matches ":matches"', [
                    ':count' => $count,
                    ':regex' => $url_regex,
                    ':matches' => $matches
                ]));
            }

            $route      = $target;
            $attachment = false;

            // Regex matched. Do variable substitution on the target.
            if (preg_match_all('/:([A-Z_]+)/', $target, $variables)) {
                array_shift($variables);

                foreach (array_shift($variables) as $variable) {
                    switch ($variable) {
                        case 'PROTOCOL':
                            // The protocol used in the current request
                            $route = str_replace(':PROTOCOL', $_SERVER['REQUEST_SCHEME'].'://', $route);
                            break;

                        case 'DOMAIN':
                            // The domain used in the current request
                            $route = str_replace(':DOMAIN', $_SERVER['HTTP_HOST'], $route);
                            break;

                        case 'LANGUAGE':
                            // The language specified in the current request
                            $route = str_replace(':LANGUAGE', LANGUAGE, $route);
                            break;

                        case 'REQUESTED_LANGUAGE':
                            // The language requested in the current request
                            // TODO This should be coming from Http class
                            // TODO Implement
//                            $requested = Arrays::firstValue(Arrays::force(Core::readRegister('http', 'accepts_languages')));
//                            $route     = str_replace(':REQUESTED_LANGUAGE', $requested['language'], $route);
                            $route     = str_replace(':REQUESTED_LANGUAGE', 'en', $route);
                            break;

                        case 'PORT':
                            // no-break
                        case 'SERVER_PORT':
                            // The port used in the current request
                            $route = str_replace(':PORT', $_SERVER['SERVER_PORT'], $route);
                            break;

                        case 'REMOTE_PORT':
                            // The port used by the client
                            $route = str_replace(':REMOTE_PORT', $_SERVER['REMOTE_PORT'], $route);
                            break;

                        default:
                            throw new OutOfBoundsException(tr('Unknown variable ":variable" found in target ":target"', [
                                ':variable' => ':' . $variable,
                                ':target' => ':' . $target
                            ]));
                    }
                }
            }

            // Apply regex variables replacements
            if (preg_match_all('/\$(\d+)/', $route, $replacements)) {
                if (preg_match('/\$\d+\.php/', $route)) {
                    $dynamic_pagematch = true;
                }

                foreach ($replacements[1] as $replacement) {
                    try {
                        if (!$replacement[0] or empty($matches[$replacement[0]])) {
                            throw new RouteException(tr('Non existing regex replacement ":replacement" specified in route ":route"', [':replacement' => '$' . $replacement[0], ':route' => $route]));
                        }

                        $route = str_replace('$' . $replacement[0], $matches[$replacement[0]][0], $route);

                    } catch (Exception $e) {
                        Log::warning(tr('Ignoring regex ":regex" because route ":route" has error ":e"', [
                            ':regex' => $url_regex,
                            ':route' => $route,
                            ':e' => $e->getMessage()
                        ]));
                    }
                }

                if (str_contains('$', $route)) {
                    // There are regex variables left that were not replaced. Replace them with nothing
                    $route = preg_replace('/\$\d/', '', $route);
                }
            }

            // Apply specified post matching flags. Depending on individual flags we may do different things
            foreach ($flags as $flags_id => $flag) {
                if (!$flag) {
                    // Completely ignore empty flags
                    continue;
                }

                switch ($flag[0]) {
                    case 'A':
                        // Send the file as a downloadable attachment
                        $attachment = true;
                        break;

                    case 'B':
                        // Block this request, send nothing
                        Log::warning(tr('Blocking request as per B flag'));
                        Core::unregisterShutdown('route_postprocess');
                        $block = true;
                        break;

                    case 'C':
                        // URL cloaking was used. See if we have a real URL behind the specified cloak
                        $_SERVER['REQUEST_URI'] = Url::decloak($route);

                        if (!$_SERVER['REQUEST_URI']) {
                            Log::warning(tr('Specified cloaked URL ":cloak" does not exist, cancelling match', [':cloak' => $route]));

                            $count++;
                            return false;
                        }

                        $_SERVER['REQUEST_URI'] = Strings::from($_SERVER['REQUEST_URI'], '://');
                        $_SERVER['REQUEST_URI'] = Strings::from($_SERVER['REQUEST_URI'], '/');

                        Log::notice(tr('Redirecting cloaked URL ":cloak" internally to ":url"', [
                            ':cloak' => $route,
                            ':url' => $_SERVER['REQUEST_URI']
                        ]));

                        $count = 1;
                        unset($flags[$flags_id]);
                        $this->execute(Debug::currentFile(1), $attachment);

                    case 'G':
                        // MUST be a GET reqest, NO POST data allowed!
                        if (!empty($_POST)) {
                            Log::notice(tr('Matched route ":route" allows only GET requests, cancelling match', [':route' => $route]));

                            $count++;
                            return false;
                        }

                        break;

                    case 'H':
                        Log::notice(tr('*POSSIBLE HACK ATTEMPT DETECTED*'));
                        Notification::new()
                            ->setCode('hack')
                            ->setGroups('security')
                            ->setTitle(tr('*Possible hack attempt detected*'))
                            ->setMessage(tr('The IP address ":ip" made the request ":request" which was matched by regex ":regex" with flags ":flags" and caused this notification', [
                                ':ip'      => $ip,
                                ':request' => $uri,
                                ':regex'   => $url_regex,
                                ':flags'   => implode(',', $flags)
                            ]))->send();
                        break;

                    case 'L':
                        // Disable language support
                        $disable_language = true;
                        break;

                    case 'P':
                        // MUST be a POST reqest, NO EMPTY POST data allowed!
                        if (empty($_POST)) {
                            Log::notice(tr('Matched route ":route" allows only POST requests, cancelling match', [':route' => $route]));

                            $count++;
                            return false;
                        }

                        break;

                    case 'Q':
                        // Let GET request queries pass through
                        if (strlen($flag) === 1) {
                            $get = true;
                            break;
                        }

                        $get = explode(';', substr($flag, 1));
                        $get = array_flip($get);
                        break;

                    case 'R':
                        // Validate the HTTP code to use, then redirect to the specified target
                        $http_code = substr($flag, 1);

                        switch ($http_code) {
                            case '':
                                $http_code = 301;
                                break;

                            case '301':
                                // no-break

                            case '302':
                                break;

                            default:
                                throw new RouteException(tr('Invalid R flag HTTP CODE ":code" specified for target ":target"', [
                                    ':code' => ':' . $http_code,
                                    ':target' => ':' . $target
                                ]));
                        }

                        // We are going to redirect so we no longer need to default to 404
                        Log::success(tr('Redirecting to ":route" with HTTP code ":code"', [':route' => $route, ':code' => $http_code]));
                        Core::unregisterShutdown('route_postprocess');
                        WebPage::redirect(Url::build($route)->addQueries($_GET)->www(), $http_code);
                        break;

                    case 'S':
                        $until = substr($flag, 1);

                        if ($until and !is_natural($until)) {
                            Log::warning(tr('Specified S flag value ":value" is invalid, natural number expected. Falling back to default value of 86400', [':value' => $until]));
                            $until = null;
                        }

                        if (!$until) {
                            $until = 86400;
                        }

                        break;

                    case 'X':
                        // Restrict access to the specified path list
                        $restrictions = substr($flag, 1);
                        $restrictions = str_replace(';', ',', $restrictions);
                        break;
                }
            }

            // Do we allow any $_GET queries from the REQUEST_URI?
            if (empty($get)) {
                if (!empty($_GET)) {
                    // Client specified variables on a URL that does not allow queries, cancel the match
                    Log::warning(tr('Matched route ":route" does not allow query variables while client specified them, cancelling match', [':route' => $route]));
//                    Log::vardump($_GET);

                    $count++;
                    return false;
                }

            } elseif ($get !== true) {
                // Only allow specific query keys. First check all allowed query keys if they have actions specified
                foreach ($get as $key => $value) {
                    if (str_contains('=', $key)) {
                        // Regenerate the key as a $key => $value instead of $key=$value => null
                        $get[Strings::until($key, '=')] = Strings::from ($key, '=');
                        unset($get[$key]);
                    }
                }

                // Go over all $_GET variables and ensure they're allowed
                foreach ($_GET as $key => $value) {
                    // This key must be allowed, or we're done
                    if (empty($get[$key])) {
                        Log::warning(tr('Matched route ":route" contains GET key ":key" which is not specifically allowed, cancelling match', [
                            ':route' => $route,
                            ':key'   => $key
                        ]));

                        $count++;
                        return false;
                    }

                    // Okay, the key is allowed, yay! What action are we going to take?
                    switch ($get[$key]) {
                        case null:
                            break;

                        case 301:
                            // Redirect to URL without query
                            $domain = Web::getDomain(true);
                            $domain = Strings::until($domain, '?');

                            Log::warning(tr('Matched route ":route" allows GET key ":key" as redirect to URL without query', [
                                ':route' => $route,
                                ':key'   => $key
                            ]));

                            Core::unregisterShutdown('route_postprocess');
                            WebPage::redirect($domain);
                    }
                }
            }

            // Split the route into the page name and GET requests
            $page = Strings::until($route, '?');
            $get  = Strings::from($route , '?', 0, true);

            // Translate the route?
            if (isset($core->register['Route::map']) and empty($disable_language)) {
                // Found mapping configuration. Find language match. Assume that $matches[1] contains the language,
                // unless specified otherwise
                if (isset($core->register['Route::map']['language'])) {
                    $language = isset_get($matches[$core->register['Route::map']['language']][0]);

                } else {
                    $language = isset_get($matches[1][0]);
                }

                if ($language !== 'en') {
                    // Requested page is in a non-English language. This means that the entire URL MUST be in that
                    // language. Translate the URL to its English counterpart
                    $translated = false;

                    // Check if route map has the requested language
                    if (empty($core->register['Route::map'][$language])) {
                        Log::warning(tr('Requested language ":language" does not have a language map available', [
                            ':language' => $language
                        ]));

                        Core::unregisterShutdown('route_postprocess');
                        Route::execute404();

                    } else {
                        // Found a map for the requested language
                        Log::notice(tr('Attempting to remap for language ":language"', [':language' => $language]));

                        foreach ($core->register['Route::map'][$language] as $unknown => $remap) {
                            if (str_contains($page, $unknown)) {
                                $translated = true;
                                $page       = str_replace($unknown, $remap, $page);
                            }
                        }

                        if (!file_exists($page)) {
                            Log::warning(tr('Language remapped page ":page" does not exist', [':page' => $page]));
                            Core::unregisterShutdown('route_postprocess');
                            Route::execute404();
                        }

                        Log::success(tr('Found remapped page ":page"', [':page' => $page]));
                    }

                    if (!$translated) {
                        // Page was not translated, ie its still the original and no translation was found.
                        Log::warning(tr('Requested language ":language" does not have a translation available in the language map for page ":page"', [
                            ':language' => $language,
                            ':page'     => $page
                        ]));

                        Core::unregisterShutdown('route_postprocess');
                        Route::execute404();
                    }
                }
            }

            // Check if configured page exists
            if ($page === 'index.php') {
                throw new RouteException(tr('Route regex ":url_regex" resolved to main index.php page which would cause an endless loop', [
                    ':url_regex' => $url_regex
                ]));
            }

            $page = PATH_WWW . Strings::startsNotWith($page, '/');

            if (!file_exists($page) and !$block) {
                if (isset($dynamic_pagematch)) {
                    Log::warning(tr('Dynamically matched page ":page" does not exist', [':page' => $page]));
                    $count++;
                    return false;

                } else {
                    // The hardcoded file for the regex does not exist, oops!
                    Log::warning(tr('Matched hard coded page ":page" does not exist', [':page' => $page]));
                    Core::unregisterShutdown('route_postprocess');
                    Route::execute404();
                }
            }

            // If we have GET parameters, add them to the $_GET array
            if ($get) {
                $get = explode('&', $get);

                foreach ($get as $entry) {
                    $_GET[Strings::until($entry, '=')] = Strings::from($entry, '=', 0, true);
                }
            }

            // We are going to show the matched page so we no longer need to default to 404
            Core::unregisterShutdown('route_postprocess');

            /*
             * Execute the page specified in $target (from here, $route)
             * Update the current running script name
             *
             * Flip the routemap keys <=> values foreach language so that its
             * now english keys. This way, the routemap can be easily used to
             * generate foreign language URLs
             */
            Core::writeRegister($page                                  , 'system', 'script_path') ;
            Core::writeRegister(Strings::fromReverse($page, '/'), 'system', 'script') ;

            if ($until) {
                /*
                 * Store the request as a rule until it expires
                 *
                 * Apply semi-permanent routing for this IP
                 *
                 * Remove the "S" flag since we don't want to store the rule again
                 * in subsequent loads
                 *
                 * Remove the "H" flag since subsequent requests may not be a hack
                 * attempt. Since we are going to act as if the rule AND URI
                 * apply, we don't know really, avoid unneeded red flags
                 */
                foreach ($flags as $id => $flag) {
                    switch ($flag[0]) {
                        case 'H':
                            // no-break
                        case 'S':
                            unset($flags[$id]);
                            break;
                    }
                }

                Route::insertStatic([
                    'expiredon' => $until,
                    'target'    => $target,
                    'regex'     => $url_regex,
                    'flags'     => $flags,
                    'uri'       => $uri,
                    'ip'        => $ip
                ]);
            }

            if ($block) {
                // Block the request by dying
                Core::die();
            }

            return $this->execute($page, $attachment);

        } catch (Exception $e) {
            if (str_starts_with($e->getMessage(), 'PHP ERROR [2] "preg_match_all():')) {
                // A user defined regex failed, give pretty error
                throw new RouteException(tr('Failed to process regex :count ":regex" with error ":e"', [
                    ':count' => $count,
                    ':regex' => $url_regex,
                    ':e' => trim(Strings::cut($e->getMessage(), 'preg_match_all():', '" in'))
                ]));
            }

            if (str_starts_with($e->getMessage(), 'PHP ERROR [2] "preg_match():')) {
                // A user defined regex failed, give pretty error
                throw new RouteException(tr('Failed to process regex :count ":regex" with error ":e"', [
                    ':count' => $count,
                    ':regex' => $url_regex,
                    ':e' => trim(Strings::cut($e->getMessage(), 'preg_match():', '" in'))
                ]));
            }

            throw $e;
        }
    }



    /**
     * Specify a language routing map for multi-lingual websites
     *
     * The translation map helps route() to detect URL's where the language is native. For example; http://phoundation.org/about.html and http://phoundation.org/nosotros.html should both route to about.php, and maybe you wish to add multiple languages for this. The routing table basically says what words should be translated to their native language counterparts. The domain() function uses this table as well when generating URL's. See domain() for more information
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
     * @package Web
     * @see route()
     * @version 2.8.4: Added function and documentation
     * @version 2.8.19: Can now use pre-configured language maps
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
     * @param string $language
     * @param array $map
     * @return void
     */
    public function mapUrl(string $language, array $map): void
    {
        // Set specific language map
        Log::notice(tr('Setting specified URL map'));
        Core::register($map, 'route', 'map');
    }



    /**
     * Shutdown the URL routing
     *
     * @see Route::postProcess()
     * @return void
     */
    public function shutdown(?int $exit_code = null): void
    {
        if ($exit_code) {
            Log::warning(tr('Routed script ":script" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [
                ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                ':exitcode' => $exit_code
            ]));

        } else {
            Log::success(tr('Routed script ":script" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [
                ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                ':exitcode' => $exit_code
            ]));
        }
    }



    /**
     * Shutdown the URL routing
     *
     * @note: This function typically is called automatically
     *
     * @see Route::try()
     * @see Route::execute404()
     *
     * @return void
     */
    public function postProcess(): void
    {
        Log::warning(tr('Found no routes for known pages, testing for hacks'));

        // Test the URI for known hacks. If so, apply configured response
        if (Config::get('web.route.known-hacks', false)) {
            Log::warning(tr('Applying known hacking rules'));

            foreach (Config::get('web.route.known-hacks') as $hacks) {
                $this->try($hacks['regex'], isset_get($hacks['url']), isset_get($hacks['flags']));
            }
        }

        // This is not a hack, the page simply cannot be found. Show a 404 instead
        $this->execute404();
    }



    /**
     * Execute the specified target
     *
     * @param string $target
     * @param bool $attachment
     * @return bool
     */
    protected function execute(string $target, bool $attachment): bool
    {
        // Set the server filesystem restrictions and template for this page
        WebPage::setServerRestrictions($this->getServerRestrictions());

        // Find the correct target page
        $target = Filesystem::absolute(Strings::unslash($target), PATH_WWW . LANGUAGE . '/pages/');

        if (str_ends_with($target, 'php')) {
            // Remove the 404 auto execution on shutdown
            Core::unregisterShutdown('route_postprocess');
            $html = WebPage::execute($target, $this->template, $attachment);

            if ($attachment) {
                // Send download headers and send the $html payload
                File::new($this->server_restrictions)
                    ->setAttachment(true)
                    ->setData($html)
                    ->setFilename(basename($target))
                    ->send();
            }

            return true;

        }

        if ($attachment) {
            // TODO Test this! Implement required HTTP headers!
            // Upload the file to the client as an attachment
            Log::action(tr('Sending file ":target" as attachment', [':target' => $target]));

            File::new($this->server_restrictions)
                ->setAttachment(true)
                ->setFile($target)
                ->send();

        } else {
            // TODO Test this! Implement required HTTP headers!
            // Send the file directly
            $mimetype = mime_content_type($target);
            $bytes    = filesize($target);

            Log::action(tr('Sending contents of file ":target" with mime-type ":type" directly to client', [
                ':target' => $target,
                ':type' => $mimetype
            ]));

            header('Content-Type: ' . $mimetype);
            header('Content-length: ' . $bytes);

            include($target);
        }

        return true;
    }



    /**
     * Show the 404 page
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package Web
     * @see Route::add()
     * @see Route::shutdown()
     * @note: This method typically would only need to be called by the route() or Route::shutdown()functions.
     * @note: This method will kill the process
     * @version 2.0.5: Added function and documentation
     *
     * @return void
     */
    protected function execute404(): void
    {
        Log::warning(tr('Found no routes for known pages nor hacks, sending 404'));

        try {
            Core::writeRegister(PATH_WWW . 'system/404', 'system', 'script_path');
            Core::writeRegister('404', 'system', 'script');

            $this->execute('system/404.php', false);

        } catch (Throwable $e) {
            if ($e->getCode() === 'not-exists') {
                Log::warning(tr('The system/404.php page does not exist, showing basic 404 message instead'));

                echo tr('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
                <html><head>
                <title>:title</title>
                </head><body>
                <h1>:h1</h1>
                <p>:p</p>
                <hr>
                :body
                </body></html>', [
                    ':title' => tr('404 Not Found'),
                    ':h1'    => tr('Not Found'),
                    ':p'     => tr('The requested URL /wer was not found on this server'),
                    ':body'  => ((Config::get('security.expose.phoundation-signature', false)) ? '<address>Phoundation ' . Core::FRAMEWORKCODEVERSION . '</address>' : '')
                ]);
                die();
            }

            Log::warning(tr('The 404 page failed to show with an exception, showing basic 404 message instead and logging exception below'));
            Log::setBacktraceDisplay('BACKTRACE_DISPLAY_BOTH');
            Log::error($e);

            echo tr('404 - The requested page does not exist');
            die();
        }
    }



    /**
     * Insert a route
     *
     * @param $route
     * @return void The result
     * @throws Throwable
     * @package Web
     * @see Route::map()
     * @see Date:convert() Used to convert the sitemap entry dates
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
     */
    protected function insertStatic($route): void
    {
        $route = Route::validateStatic($route);

        Log::notice(tr('Storing routing rule ":rule" for IP ":ip"', [':rule' => $route['target'], ':ip' => $route['ip']]));

        sql()->query(
            'INSERT INTO `routes_static` (`expiredon`                                , `meta_id`, `ip`, `uri`, `regex`, `target`, `flags`)
                   VALUES                      (DATE_ADD(NOW(), INTERVAL :expiredon SECOND), :meta_id , :ip , :uri , :regex , :target , :flags )',

            [
                ':expiredon' => $route['expiredon'],
                ':meta_id'   => meta_action(),
                ':ip'        => $route['ip'],
                ':uri'       => $route['uri'],
                ':regex'     => $route['regex'],
                ':target'    => $route['target'],
                ':flags'     => $route['flags']
            ]);
    }



    /**
     * Validate a route
     *
     * This function will validate all relevant fields in the specified $route array
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package categories
     *
     * @param StaticRoute $route
     * @return string HTML for a categories select box within the specified parameters
     */
    protected function validateStatic(StaticRoute $route)
    {
//        Validator::array($route)
//            ->select('uri')->isUrl('uri')
//            ->select('fields')->sanitizeMakeString()->hasMaxCharacters(16)
//            ->select('regex')->sanitizeMakeString(255)
//            ->select('target')->sanitizeMakeString(255)
//            ->select('ip')->isIp()
//            ->validate();
//
//        return $route;
    }



    /**
     * Returns the temp_server if available, default server otherwise
     *
     * @return Server
     */
    protected function getServerRestrictions(): Server
    {
        if ($this->temp_server) {
            $server_restrictions = $this->temp_server;
        } else {
            $server_restrictions = $this->server_restrictions;
        }

        $this->temp_server = null;
        $server_restrictions->setLabel('Route');
        return $server_restrictions;
    }
}
