<?php
/**
 * Class Route
 *
 * Core routing class that will route URL request queries to PHP scripts in the DIRECTORY_WEB path
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\NoProjectException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\CookieValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Date\DateTime;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Exception\RegexException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Exception\RouteException;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Routing\Interfaces\MappingInterface;
use Throwable;

class Route
{
    /**
     * Singleton variable
     *
     * @var Route
     */
    protected static Route $instance;

    /**
     * Keeps track if the routing system has been initialized
     *
     * @var bool $init
     */
    protected static bool $init = false;

    /**
     * The temporary template to use while routing ONLY for the current try
     *
     * @var ?string $temp_template
     */
    protected static ?string $temp_template = null;

    /**
     * The request method
     *
     * @var string $method
     */
    protected static string $method;

    /**
     * The remote IP address that made this request
     *
     * @var string|mixed $ip
     */
    protected static string $ip;

    /**
     * The request URI
     *
     * @var string $uri
     */
    protected static string $uri;

    /**
     * The request query
     *
     * @var string $query
     */
    protected static string $query;

    /**
     * Routing parameters list to use for the try() requests
     *
     * @var RoutingParametersList $parameters
     */
    protected static RoutingParametersList $parameters;

    /**
     * If true, then the found page was matched dynamically (with a regex)
     *
     * @var bool $dynamic_pagematch
     */
    protected static bool $dynamic_pagematch = false;

    /**
     * URL mappings object
     *
     * @var MappingInterface $mapping
     */
    protected static MappingInterface $mapping;


    /**
     * Route constructor
     */
    protected function __construct()
    {
        // Cleanup the request URI by removing all GET requests and the leading slash, URIs cannot be longer than 255
        // characters
        //
        // Deny URI's larger than 255 characters. If these are specified, automatically 404 because this is a hard coded        // limit. The reason for this is that the routes_static table columns currently only hold 255 characters and at
        // the moment I see no reason why anyone would want more than 255 characters in their URL.
        static::$method = ($_POST ? 'POST' : 'GET');
        static::$ip     = (empty($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_REAL_IP']);
        static::$query  = Strings::from($_SERVER['REQUEST_URI'], '?');
        static::$uri    = Strings::ensureStartsNotWith($_SERVER['REQUEST_URI'], '/');
        static::$uri    = Strings::until(static::$uri, '?');

        if (str_ends_with($_SERVER['REQUEST_URI'], 'favicon.ico')) {
            // By default, increase logger threshold on all favicon.ico requests to avoid log clutter
            Log::setThreshold(Config::getInteger('log.levels.web.favicon', 10));
        }

        // Start the Core object, hide $_GET & $_POST
        try {
            if (Core::isState(null)) {
                Core::startup();
                GetValidator::hideData();
                PostValidator::hideData();
            }

        } catch (SqlException | NoProjectException) {
            // Either we have no project or no system database
            GetValidator::hideData();
            PostValidator::hideData();
            static::execute('setup.php', false);
        }

        // Ensure the post-processing function is registered
        Log::information(tr('[:method] ":url" from client ":client"', [
            ':method' => static::$method,
            ':url'    => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            ':client' => $_SERVER['REMOTE_ADDR'] . (empty($_SERVER['HTTP_X_REAL_IP']) ? '' : ' (Real IP: ' . $_SERVER['HTTP_X_REAL_IP'] . ')'),
        ]), 9);
    }


    /**
     * Execute the specified target
     *
     * @param string $target
     * @param bool   $attachment
     * @param bool   $system
     *
     * @return never
     */
    #[NoReturn] protected static function execute(string $target, bool $attachment, bool $system = false): never
    {
        Core::removeShutdownCallback(404);

        // Get routing parameters and find the correct target page
        $parameters = static::getParameters()->select(static::$uri);
        $target     = new File($target);

        Request::setRoutingParameters($parameters);
        Request::setAttachment($attachment);
        Request::setSystem($system);

        // Target may NEVER be web/index.php because that will run the router into endless loops!
        if ($target->isPath('index.php')) {
            throw new RouteException(tr('Route resolved to main "index.php" routing page which would cause an endless loop'));
        }

        if ($target->hasExtension('php')) {
            // The target is a PHP file, so execute it. The Page object will take care of everything, even if it's an
            // attachment that the client will download instead of view in the browser.
            Request::execute($target);
        }

        // The file is NOT a PHP executable, send the resolved file contents
        throw new UnderConstructionException(tr('Implement routing to files!'));
        //FileResponse::new()->$request)->send();
    }


    /**
     * Returns the routing parameters list
     *
     * @return RoutingParametersList
     */
    public static function getParameters(): RoutingParametersList
    {
        static::getInstance();

        if (!isset(static::$parameters)) {
            static::$parameters = new RoutingParametersList();
        }

        return static::$parameters;
    }


    /**
     * Singleton, ensure to always return the same Route object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (empty(static::$instance)) {
            static::$instance = new static();
        }
        // We should execute the initialization only once
        if (!static::$init) {
            // Only initialize when a parameter list has been set, since init may cause this list to be needed
            if (isset(static::$parameters)) {
                static::$init = true;
                static::init();
            }
        }

        return static::$instance;
    }


    /**
     * Will execute a few initial checks and apply URL mappings
     *
     * @return void
     */
    protected static function init(): void
    {
        Request::setRestrictions(Restrictions::readonly(DIRECTORY_WEB));
        Response::initialize();

        if (Core::getMaintenanceMode()) {
            // We're running in maintenance mode, show the maintenance page
            Log::warning('WARNING: Not processing routes, system is in maintenance mode');
            Request::executeSystem(503);
        }

        // URI may not be more than 2048 bytes
        if (strlen(static::$uri) > 2048) {
            Log::warning(tr('Requested URI ":uri" has ":count" characters, where 2048 is a hardcoded limit for compatibility (See Phoundation\Web\Route class). 400-ing the request', [
                ':uri'   => static::$uri,
                ':count' => strlen(static::$uri),
            ]));
            Request::executeSystem(400);
        }

        // Check for double // anywhere in the URL, this is automatically rejected with a 404, not found
        // NOTE: This is checked on $_SERVER['REQUEST_URI'] and not static::$uri because static::$uri already has the
        // first slash(es) stripped during the __construct() phase
        if (str_contains($_SERVER['REQUEST_URI'], '//')) {
            Log::warning(tr('Requested URI ":uri" contains one or multiple double slashes, automatically rejecting this with a 404 page', [
                ':uri' => $_SERVER['REQUEST_URI'],
            ]));
            Request::executeSystem(404);
        }

        if (str_ends_with($_SERVER['HTTP_HOST'], '.')) {
            // The specified domain ends with a "." like "phoundation.org." instead of "phoundation.org" so redirect
            Response::redirect($_SERVER['REQUEST_SCHEME'] . '://' . Strings::ensureEndsNotWith($_SERVER['HTTP_HOST'], '.') . $_SERVER['REQUEST_URI']);
        }

        // Apply mappings
        static::$uri = static::applyMappings(static::$uri);

        // Ensure a 404 is shown if route cannot execute anything
        Core::addShutdownCallback(404, function () {
            Request::setRoutingParameters(static::getParameters()->select('system/404', true), true);
            Request::executeSystem(404);
        });
    }


    /**
     * Applies any configured URL mappings
     *
     * @param string $url
     *
     * @return string
     */
    protected static function applyMappings(string $url): string
    {
        return static::getMapping()
                     ->apply($url);
    }


    /**
     * Returns the mapping object
     *
     * @return MappingInterface
     */
    public static function getMapping(): MappingInterface
    {
        if (empty(static::$mapping)) {
            static::$mapping = new Mapping();
        }

        return static::$mapping;
    }


    /**
     * Returns the original resource request
     *
     * @return string
     */
    public static function getRequest(): string
    {
        return (string) UrlBuilder::getWww();
    }


    /**
     * Returns the original request method
     *
     * @return string
     */
    public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }


    /**
     * Returns the request headers
     *
     * @return array
     */
    public static function getHeaders(): array
    {
        return getallheaders();
    }


    /**
     * Returns the cookies from the request
     *
     * @return array
     */
    public static function getCookies(): array
    {
        return CookieValidator::new()
                              ->getSource();
    }


    /**
     * Returns the POST data from the request
     *
     * @return array
     */
    public static function getPostData(): array
    {
        return PostValidator::new()
                            ->getSource();
    }


    /**
     * Returns the real remote IP address
     *
     * @return string
     */
    public static function getRemoteIp(): string
    {
        return static::$ip;
    }


    /**
     * Modify the incoming request with the specified regex, (optionally) only if the secondary regex matches
     *
     * @param string      $replace_regex
     * @param string      $replace_value
     * @param string|null $match_regex
     *
     * @return void
     */
    public static function modify(string $replace_regex, string $replace_value, ?string $match_regex = null): void
    {
        try {
            if ($match_regex) {
                try {
                    if (!preg_match($match_regex, static::$uri)) {
                        return;
                    }

                } catch (PhpException $e) {
                    if ($e->messageMatches('preg_replace():')) {
                        throw new RegexException(tr('The Route::modify() match regular expression ":regex" failed with ":e"', [
                            ':e'     => trim(Strings::from($e->getMessage(), 'preg_replace():')),
                            ':regex' => $match_regex,
                        ]));
                    }
                    throw $e;
                }
            }
            static::$uri = preg_replace($replace_regex, $replace_value, static::$uri);

        } catch (PhpException $e) {
            if ($e->messageMatches('preg_replace():')) {
                throw new RegexException(tr('The Route::modify() replace regular expression ":regex" failed with ":e"', [
                    ':e'     => trim(Strings::from($e->getMessage(), 'preg_replace():')),
                    ':regex' => $replace_regex,
                ]));
            }
            throw $e;
        }
    }


    /**
     * Route the request uri from the client to the correct php file
     *
     * The Route::add() call requires 3 arguments; $regex, $target, and $flags.
     *
     * The first argument is the PERL compatible regular expression that will match the URL you wish to route to a
     * page.
     * Note that this must be a FULL regular expression with opening and closing tags. / is recommended for these tags,
     * but not required. See https://www.php.net/manual/en/function.preg-match.php for more information about PERL
     * compatible regular expressions. This regular expression may capture variables which then can be used in the
     * target as $1, $2 for the first and second variable respectitively. Regular expression flags like i (case
     * insensitive matches), u (unicode matches), etc. may be added after the trailing / of this variable
     *
     * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular
     * expression captured variables, you may use these variables here. If the page name itself is a variable, then
     * Route::add() will try to find that page, and execute it if it exists
     *
     * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
     * A                Process the target as an attachement (i.e. Send the file so that the browser client can
     * download
     *                  it)
     * B                Block. Return absolutely nothing
     * C                Use URL cloaking. A cloaked URL is basically a random string that the Route::add() function can
     *                  look up in the `cloak` table. domain() and its related functions will generate these URL's
     *                  automatically. See the "url" library, and domain() and related functions for more information
     * D                Add HTTP_HOST to the REQUEST_URI before applying the match
     * G                The request must be GET to match
     * H                If the routing rule matches, the router will add a *POSSIBLE HACK ATTEMPT DETECTED* log entry
     *                  for later processing
     * L                Disable language map requirements for this specific URL (Use this with non language URLs on a
     *                  multi lingual site)
     * P                The request must be POST to match
     * M                Add queries into the REQUEST_URI before applying the match, autmatically implies Q
     * N                Do not check for permanent routing rules
     * Q                Allow queries to pass through. If NOT specified, and the URL contains queries, the URL will NOT
     *                  match!
     * QKEY;KEY=ACTION  Is a ; separated string containing query keys that are allowed, and if specified, what action
     *                  must be taken when encountered
     * R301             Redirect to the specified page argument using HTTP 301
     * R302             Redirect to the specified page argument using HTTP 302
     * S$SECONDS$       Store the specified rule for this IP and apply it for $SECONDS$ number of seconds. $SECONDS$ is
     *                  optional, and defaults to 86400 seconds (1 day). This works well to auto 404 IP's that are
     *                  doing
     *                  naughty things for at least a day
     * T$TEMPLATE$      Use the specified template instead of the current template for this try
     * X$PATHS$         Restrict access to the specified dot-comma separated $PATHS$ list. $PATHS is optional and
     *                  defaults to DIRECTORY_WEB, DIRECTORY_DATA .'content/downloads'
     * Z$RIGHT$[$PAGE$] Requires that the current session user has the specified right, or $PAGE$ will be shown, with
     *                  $PAGE$ defaulting to system/403. Multiple Z flags may be specified
     *
     * The $Debug::enabled() and $Debug::enabled() variables here are to set the system in Debug::enabled() or
     * Debug::enabled() mode, but ONLY if the system runs in debug mode. The former will add extra log output in the
     * data/log files, the latter will add LOADS of extra log data in the data/log files, so please use with care and
     * only if you cannot resolve the problem
     *
     * Once all Route::add() calls have passed without result, the system will shut down. The shutdown() call will then
     * automatically execute Request::executeSystem() which will display the 404 page
     *
     * To use translation mapping, first set the language map using Route::map()
     *
     * @param string $url_regex
     * @param string $target
     * @param string $flags
     *
     * @return bool
     * @throws RouteException|\Throwable
     * @package Web
     * @see     Request::executeSystem()
     * @see     Route::execute()
     * @see     domain()
     * @see     Route::map()
     * @see     Route::makeStatic()
     * @see     https://www.php.net/manual/en/function.preg-match.php
     * @see     https://regularexpressions.info/ NOTE: The site currently has broken SSL, but is one of the best
     *          resources out there to learn regular expressions
     * @table   : `routes_static`
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
     * Route::add(''                                                , ':PROTOCOL:DOMAIN/:REQUESTED_LANGUAGE/', 'R301');
     * // This will HTTP 301 redirect the user to a page with the same protocol, same domain, but the language that
     * their browser requested. So for example, http://domain.com with HTTP header "accept-language:en" would HTTP 301
     * redirect to http://domain.com/en/
     *
     * // These are some examples for blocking hacking attempts
     * Route::add('/\/\.well-known\//i'  , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be
     * 404-ing for a good while Route::add('/\/acme-challenge\//i', 'en/system/404.php', 'B,H,L,S');   // If you
     * request this, you will be 404-ing for a good while Route::add('/C=S;O=A/i'           , 'en/system/404.php',
     * 'B,H,L,M,S'); // If you request this query, you will be 404-ing for a good while Route::add('/wp-admin/i'
     *   , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     *   Route::add('/libs\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be
     *   404-ing for a good while Route::add('/scripts\//i'         , 'en/system/404.php', 'B,H,L,S');   // If you
     *   request this, you will be 404-ing for a good while Route::add('/config\//i'          , 'en/system/404.php',
     *   'B,H,L,S');   // If you request this, you will be 404-ing for a good while Route::add('/init\//i'            ,
     *   'en/system/404.php', 'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     *   Route::add('/www\//i'             , 'en/system/404.php', 'B,H,L,S');   // If you request this, you will be
     *   404-ing for a good while Route::add('/data\//i'            , 'en/system/404.php', 'B,H,L,S');   // If you
     *   request this, you will be 404-ing for a good while Route::add('/public\//i'          , 'en/system/404.php',
     *   'B,H,L,S');   // If you request this, you will be 404-ing for a good while
     * /code
     *
     * The following example code will set a language route map where the matched word "from" would be translated to
     * "to" and "for" to "bar" for the language "es"
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
     * @example Setup URL translations map. In this example, URL's with /es/ with the word "conferencias" would map to
     *          the word "conferences", etc.
     * code
     * Route::map('es', [
     *     'conferencias' => 'conferences',
     *     'portafolio'   => 'portfolio',
     *     'servicios'    => 'services',
     *     'nosotros'     => 'about'
     * ]);
     *
     * Route::map('nl', [
     *     'conferenties' => 'conferences',
     *     'portefeuille' => 'portfolio',
     *     'diensten'     => 'services',
     *     'over-ons'     => 'about'
     *  ]);
     * /code
     */
    public static function try(string $url_regex, string $target, string $flags = ''): void
    {
        static $count = 1;
        static::getInstance();
        static::validateHost();
        try {
            if (!$url_regex) {
                // Match an empty string
                $url_regex = '/^$/';
            }
            // Apply pre-matching flags. Depending on individual flags we may do different things
            $uri    = static::$uri;
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
                        $uri .= '?' . static::$query;
                        Log::notice(tr('Adding query to URI ":uri"', [':uri' => $uri]));

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
                                                LIMIT 1', [':ip' => static::$ip]);

                    if ($exists) {
                        // Apply semi-permanent routing for this IP
                        Log::warning(tr('Found active routing for IP ":ip", continuing routing as if request is URI ":uri" with regex ":regex", target ":target", and flags ":flags" instead', [
                            ':ip'     => static::$ip,
                            ':uri'    => $exists['uri'],
                            ':regex'  => $exists['regex'],
                            ':target' => $exists['target'],
                            ':flags'  => $exists['flags'],
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
                ':type'  => static::$method,
                ':url'   => $uri,
            ]), 4);

            try {
                $match = preg_match_all($url_regex, $uri, $matches);

            } catch (Exception $e) {
                throw new RouteException(tr('Failed to parse route ":route" with ":message"', [
                    ':route'   => $url_regex,
                    ':message' => Strings::until(Strings::from($e->getMessage(), 'preg_match_all(): '), ' in '),
                ]));
            }

            if (!$match) {
                // No match, stop this try
                $count++;

                return;
            }

            if (Debug::getEnabled()) {
                Log::success(tr('Regex ":count" ":regex" matched with matches ":matches" and flags ":flags"', [
                    ':count'   => $count,
                    ':regex'   => $url_regex,
                    ':matches' => Strings::force($matches, ', '),
                    ':flags'   => Strings::force($flags, ', '),
                ]), 5);
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
                            $route = str_replace(':PROTOCOL', $_SERVER['REQUEST_SCHEME'] . '://', $route);
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
                            $route = str_replace(':REQUESTED_LANGUAGE', 'en', $route);
                            break;

                        case 'PORT':
                            // no break

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
                                ':target'   => ':' . $target,
                            ]));
                    }
                }
            }

            // Apply regex variables replacements
            if (preg_match_all('/\$(\d+)/', $route, $replacements)) {
                if (preg_match('/\$\d+\.php/', $route)) {
                    static::$dynamic_pagematch = true;
                }

                foreach ($replacements[1] as $replacement) {
                    try {
                        if (!$replacement[0] or empty($matches[$replacement[0]])) {
                            throw new RouteException(tr('Non existing regex replacement ":replacement" specified in route ":route"', [
                                ':replacement' => '$' . $replacement[0],
                                ':route'       => $route,
                            ]));
                        }

                        $route = str_replace('$' . $replacement[0], $matches[$replacement[0]][0], $route);

                    } catch (Exception $e) {
                        Log::warning(tr('Ignoring route ":route" because regex ":regex" has the error ":e"', [
                            ':regex' => $url_regex,
                            ':route' => $route,
                            ':e'     => $e->getMessage(),
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
                        $block = true;
                        break;

                    case 'C':
                        // URL cloaking was used. See if we have a real URL behind the specified cloak
                        $_SERVER['REQUEST_URI'] = Url::decloak($route);

                        if (!$_SERVER['REQUEST_URI']) {
                            Log::warning(tr('Specified cloaked URL ":cloak" does not exist, cancelling match', [':cloak' => $route]));
                            $count++;

                            return;
                        }

                        $_SERVER['REQUEST_URI'] = Strings::from($_SERVER['REQUEST_URI'], '://');
                        $_SERVER['REQUEST_URI'] = Strings::from($_SERVER['REQUEST_URI'], '/');

                        Log::notice(tr('Redirecting cloaked URL ":cloak" internally to ":url"', [
                            ':cloak' => $route,
                            ':url'   => $_SERVER['REQUEST_URI'],
                        ]));

                        $count = 1;
                        unset($flags[$flags_id]);
                        static::execute(Debug::currentFile(1), $attachment);

                    case 'G':
                        // MUST be a GET reqest, NO POST data allowed!
                        if (!empty($_POST)) {
                            Log::notice(tr('Matched route ":route" allows only GET requests, cancelling match', [':route' => $route]));
                            $count++;

                            return;
                        }

                        break;

                    case 'H':
                        Log::notice(tr('*POSSIBLE HACK ATTEMPT DETECTED*'));
                        Notification::new()
                                    ->setUrl('security/incidents.html')
                                    ->setMode(EnumDisplayMode::exception)
                                    ->setCode('hack')
                                    ->setRoles('security')
                                    ->setTitle(tr('*Possible hack attempt detected*'))
                                    ->setMessage(tr('The IP address ":ip" made the request ":request" which was matched by regex ":regex" with flags ":flags" and caused this notification', [
                                        ':ip'      => static::$ip,
                                        ':request' => $uri,
                                        ':regex'   => $url_regex,
                                        ':flags'   => implode(',', $flags),
                                    ]))
                                    ->send();
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

                            return;
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
                                // no break

                            case '302':
                                break;

                            default:
                                throw new RouteException(tr('Invalid R flag HTTP CODE ":code" specified for target ":target"', [
                                    ':code'   => ':' . $http_code,
                                    ':target' => ':' . $target,
                                ]));
                        }

                        Request::setRoutingParameters(static::getParameters()->select(static::$uri));
                        Response::redirect(UrlBuilder::getWww($route)->addQueries($_GET), (int) $http_code);
                    case 'S':
                        $until = substr($flag, 1);
                        if ($until and !is_natural($until)) {
                            $until = null;
                            Log::warning(tr('Specified S flag value ":value" is invalid, natural number expected. Falling back to default value of 86400', [
                                ':value' => $until,
                            ]));
                        }

                        if (!$until) {
                            $until = 86400;
                        }

                        break;

                    case 'T':
                        static::$temp_template = substr($flag, 1);
                        break;

                    case 'X':
                        // Restrict access to the specified path list
                        $restrictions = substr($flag, 1);
                        $restrictions = str_replace(';', ',', $restrictions);
                        break;

                    case 'Z':
                        // Restrict access to users with the specified right, or execute the specified page instead
                        // (defaults to 403). Format is Z$RIGHT$[$PAGE$] and multiple Z rules may be specified
                        if (!preg_match_all('/^Z(.+?)(?:\[(.+?)])?$/iu', $flag, $matches)) {
                            Log::warning(tr('Invalid "Z" (requires right) rule ":flag" encountered, denying access by default for security', [
                                ':flag' => $flag,
                            ]));
                            Request::executeSystem(403);
                        }

                        $right = get_null(isset_get($matches[1][0]));
                        $page  = get_null(isset_get($matches[2][0]));

                        if (Session::getUser()->isGuest()) {
                            Log::warning(tr('Denied guest user access to resource because signed in user is required'));
                            Request::executeSystem(401);
                        }

                        if (!Session::getUser()->hasAllRights($right)) {
                            Log::warning(tr('Denied user ":user" access to resource because of missing right ":right"', [
                                ':user'  => Session::getUser()
                                                   ->getLogId(),
                                ':right' => $right,
                            ]));
                            Request::executeSystem(403);
                        }
                }
            }

            // Do we allow any $_GET queries from the REQUEST_URI?
            if (empty($get)) {
                if (!empty($_GET)) {
                    // Client specified variables on a URL that does not allow queries, cancel the match
                    Log::warning(tr('Matched route ":route" does not allow query variables while client specified them, cancelling match', [
                        ':route' => $route,
                    ]));
//                    Log::vardump($_GET);
                    $count++;

                    return;
                }

            } elseif ($get !== true) {
                // Only allow specific query keys. First check all allowed query keys if they have actions specified
                foreach ($get as $key => $value) {
                    if (str_contains('=', $key)) {
                        // Regenerate the key as a $key => $value instead of $key=$value => null
                        $get[Strings::until($key, '=')] = Strings::from($key, '=');
                        unset($get[$key]);
                    }
                }

                // Go over all $_GET variables and ensure they're allowed
                foreach ($_GET as $key => $value) {
                    // This key must be allowed, or we're done
                    if (empty($get[$key])) {
                        Log::warning(tr('Matched route ":route" contains GET key ":key" which is not specifically allowed, cancelling match', [
                            ':route' => $route,
                            ':key'   => $key,
                        ]));
                        $count++;

                        return;
                    }

                    // Okay, the key is allowed, yay! What action are we going to take?
                    switch ($get[$key]) {
                        case null:
                            // Just allow this query variable
                            break;

                        case 301:
                            // TODO What is going on here? Redirects to URL, but only domain is used? Wut?
                            throw new UnderConstructionException();
                            // Redirect to URL without query
                            $domain = Url::getDomain()->getThis();
                            $domain = Strings::until($domain, '?');
                            Log::warning(tr('Matched route ":route" allows GET key ":key" as redirect to URL without query', [
                                ':route' => $route,
                                ':key'   => $key,
                            ]));
                            Request::setRoutingParameters(static::getParameters()
                                                                ->select(static::$uri));
                            Response::redirect($domain);
                    }
                }
            }

            // Split the route into the page name and GET requests
            $page = Strings::until($route, '?');
            $get  = Strings::from($route, '?', needle_required: true);

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
                    // The requested page is in a non-English language. This means that the entire URL MUST be in that
                    // language. Translate the URL to its English counterpart
                    $translated = false;

                    // Check if route map has the requested language
                    if (empty($core->register['Route::map'][$language])) {
                        Log::warning(tr('Requested language ":language" does not have a language map available', [
                            ':language' => $language,
                        ]));

                        // TODO Check if this should be 404 or maybe some other HTTP code?
                        Request::executeSystem(404);

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
                            Request::executeSystem(404);
                        }

                        Log::success(tr('Found remapped page ":page"', [':page' => $page]));
                    }
                    if (!$translated) {
                        // Page was not translated, ie it's still the original, and no translation was found.
                        Log::warning(tr('Requested language ":language" does not have a translation available in the language map for page ":page"', [
                            ':language' => $language,
                            ':page'     => $page,
                        ]));
                        Request::executeSystem(404);
                    }
                }
            }
            if (!$block) {
                // If we have GET parameters, add them to the $_GET array
                if ($get) {
                    $get = explode('&', $get);
                    foreach ($get as $entry) {
                        GetValidator::addData(Strings::until($entry, '='), Strings::from($entry, '=', needle_required: true));
                    }
                }
                // We are going to show the matched page so we no longer need to default to 404
                // Execute the page specified in $target (from here, $route)
                // Update the current running script name
                // Flip the routemap keys <=> values foreach language so that its
                // now english keys. This way, the routemap can be easily used to
                // generate foreign language URLs
            }
            if ($until) {
                // Store the request as a rule until it expires. Apply semi-permanent routing for this IP
                // Remove the "S" flag since we don't want to store the rule again in subsequent loads
                // Remove the "H" flag since subsequent requests may not be a hack attempt. Since we are going to act as
                // if the rule AND URI apply, we don't know really, avoid unneeded red flags
                foreach ($flags as $id => $flag) {
                    switch ($flag[0]) {
                        case 'H':
                            // no break
                        case 'S':
                            unset($flags[$id]);
                            break;
                    }
                }

                Route::makeStatic([
                    'expiredon' => $until,
                    'target'    => $target,
                    'regex'     => $url_regex,
                    'flags'     => $flags,
                    'uri'       => $uri,
                    'ip'        => static::$ip,
                ]);
            }

            if ($block) {
                // Block the request by dying
                exit();
            }

        } catch (Exception $e) {
            if (str_starts_with($e->getMessage(), 'PHP ERROR [2] "preg_match_all():')) {
                // A user defined regex failed, give pretty error
                throw new RouteException(tr('Failed to process route pattern ":count" ":regex" with error ":e"', [
                    ':count' => $count,
                    ':regex' => $url_regex,
                    ':e'     => trim(Strings::cut($e->getMessage(), 'preg_match_all():', '" in')),
                ]));
            }

            if (str_starts_with($e->getMessage(), 'PHP ERROR [2] "preg_match():')) {
                // A user defined regex failed, give pretty error
                throw new RouteException(tr('Failed to process route pattern ":count" ":regex" with error ":e"', [
                    ':count' => $count,
                    ':regex' => $url_regex,
                    ':e'     => trim(Strings::cut($e->getMessage(), 'preg_match():', '" in')),
                ]));
            }

            throw $e;
        }

        static::execute($page, $attachment);
    }


    /**
     * Ensure that the requested host name is valid
     *
     * @return void
     * @todo implement
     */
    protected static function validateHost(): void
    {
        // Check only once
        static $validated = false;

        return;
        if (!$validated) {
            $validated = true;
            // Check that the domain doesn't start or end with a dot (.) if it does, redirect to the domain without the .
            // In principle, this should already cause a shitload of other issues, like SSL certs not working, etc. but
            // still, just to be sure
            if (empty($_SERVER['HTTP_HOST'])) {
                // No host name WTF? Redirect to the main site
                Request::setRoutingParameters(static::getParameters()
                                                    ->select(UrlBuilder::getRootDomainRootUrl()));
                Response::redirect(UrlBuilder::getRootDomainRootUrl());
            }
            if (str_starts_with($_SERVER['HTTP_HOST'], '.') or str_ends_with($_SERVER['HTTP_HOST'], '.')) {
                Log::warning(tr('Encountered invalid HTTP HOST ":host", it starts or ends with a dot. Redirecting to clean hostname', [
                    ':host' => $_SERVER['HTTP_HOST'],
                ]));
                // Remove dots, whitespaces, etc.
                $domain = trim(trim($_SERVER['HTTP_HOST'], '.'));
                if (Domains::isConfigured($domain)) {
                    Log::warning(tr('HTTP HOST ":host" is not configured, redirecting to main site main page', [
                        ':host' => $_SERVER['HTTP_HOST'],
                    ]));
                    Request::setRoutingParameters(static::getParameters()
                                                        ->select(UrlBuilder::getRootDomainRootUrl()));
                    Response::redirect(UrlBuilder::getRootDomainRootUrl());
                }
                // Redirect to correct page
                Request::setRoutingParameters(static::getParameters()
                                                    ->select(UrlBuilder::getRootDomainUrl()));
                Response::redirect(UrlBuilder::getRootDomainUrl());
            }
        }
    }


    /**
     * Create a static route for the specified IP
     *
     * @param string $target
     * @param string $ip
     *
     * @return StaticRoute
     *
     * @throws Throwable
     * @package Web
     * @see     Route::map()
     * @see     Date:convert() Used to convert the sitemap entry dates
     * @table   : `template`
     * @note    : This is a note
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
    protected static function makeStatic(string $target, string $ip): StaticRoute
    {
        Log::notice(tr('Creating static route ":route" for IP ":ip"', [
            ':route' => $target,
            ':ip'    => $ip,
        ]));
        $route = StaticRoute::new();

        // TODO Implement
        return $route;
    }


    /**
     * Specify a language routing map for multi-lingual websites
     *
     * The translation map helps route() to detect URL's where the language is native. For example;
     * http://phoundation.org/about.html and http://phoundation.org/nosotros.html should both route to about.php, and
     * maybe you wish to add multiple languages for this. The routing table basically says what words should be
     * translated to their native language counterparts. The domain() function uses this table as well when generating
     * URL's. See domain() for more information
     *
     * The translation mapping table should have the following format:
     *
     * [
     *   FIRST_LANGUAGE_CODE => [
     *     ENGLISH_WORD => [
     *       FIRST_LANGUAGE_CODE_WORD,
     *       ENGLISH_WORD => FIRST_LANGUAGE_CODE_WORD,
     *       ENGLISH_WORD => FIRST_LANGUAGE_CODE_WORD,
     *       ENGLISH_WORD => ...
     *     ]
     *   ],
     *   SECOND_LANGUAGE_CODE => [
     *     ENGLISH_WORD => [
     *       SECOND_LANGUAGE_CODE_WORD,
     *       ENGLISH_WORD => SECOND_LANGUAGE_CODE_WORD,
     *       ENGLISH_WORD => SECOND_LANGUAGE_CODE_WORD,
     *       ENGLISH_WORD => ...
     *     ]
     *   ]
     * ]
     *
     * @param string $language
     * @param array  $map
     *
     * @return void
     * @version                         2.8.19: Can now use pre-configured language maps
     * @example                         This example configures a language map for spanish (es) and dutch (nl)
     *                                  code
     *                                  route('map', array('es' => array('portafolio'   => 'portfolio',
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
     * @package                         Web
     * @see                             route()
     * @version                         2.8.4: Added function and documentation
     */
    public static function mapUrl(string $language, array $map): void
    {
        // Set specific language map
        Log::notice(tr('Setting specified URL map'));
        Core::register($map, 'route', 'map');
    }


    /**
     * Block the specified IP
     *
     * @param string $ip
     * @param int    $until
     * @param string $reason
     *
     * @return void
     */
    protected static function block(string $ip, int $until, string $reason): void
    {
        if (!$until) {

        }

        Log::notice(tr('Blocking IP ":ip" until ":until" because ":reason"', [
            ':until' => DateTime::new($until),
            ':ip'    => $ip,
        ]));
//        $route FirewallEntry = Firewall::block($ip);
//        // TODO Implement
//
//        return FirewallEntry;
//        return $route;
    }
}
