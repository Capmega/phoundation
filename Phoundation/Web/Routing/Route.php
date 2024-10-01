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
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\GetVariables;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Iterator;
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
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Exception\RouteException;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Url;
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
     * @var string $url
     */
    protected static string $url;

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
     * Counts the routing rule number that has been processed
     *
     * @var int $rule_count
     */
    protected static int $rule_count = 1;

    /**
     * Tracks if GET variables should be passed to the web page
     *
     * @var array|bool $pass_get_variables
     */
    protected static array|bool $pass_get_variables = false;

    /**
     * The page detected by Route::try()
     *
     * @var string|null $page
     */
    protected static ?string $page = null;

    /**
     * Tracks if the current page contents should be sent back as an attachment
     *
     * @var bool $attachment
     */
    protected static bool $attachment = false;

    /**
     * Tracks if this request is blocked
     *
     * @var bool $block_request
     */
    protected static bool $block_request = false;

    /**
     * Tracks the flags for this request
     *
     * @var array $flags
     */
    protected static array $flags;

    /**
     * Tracks if static routing rules should be applied
     *
     * @var bool $apply_static_routes
     */
    protected static bool $apply_static_routes = true;

    /**
     * Tracks until when a static route should be applicable
     *
     * @var false|int $until
     */
    protected static false|int $until = false;

    /**
     * Tracks the route being tried
     *
     * @var string $route
     */
    protected static string $route;

    /**
     * Tracks the URL regex for the current route being tried
     *
     * @var string $url_regex
     */
    protected static string $url_regex;

    /**
     * Tracks if multilingual support has been disabled for this route
     *
     * @var bool $disable_language
     */
    protected static bool $disable_language = false;

    /**
     * Tracks the matches from the regex applied to the URL
     *
     * @var array|null $regex_matches
     */
    protected static ?array $regex_matches = null;


    /**
     * Route constructor
     */
    protected function __construct()
    {
        // Start the Core object
        try {
            if (Core::isState(null)) {
                Core::startup();
            }

        } catch (SqlException) {
            // Either we have no project or no system database
            static::$page = 'setup.php';
            static::execute();

        } catch (Throwable $e) {
            throw new RouteException('Failed to start Core library', $e);
        }


        // Cleanup the request URI by removing all GET requests and the leading slash, URIs cannot be longer than 255
        // characters
        //
        // Deny URI's larger than 255 characters. If these are specified, automatically 404 because this is a hard coded
        // limit. The reason for this is that the routes_static table columns currently only hold 255 characters and at
        // the moment I see no reason why anyone would want more than 255 characters in their URL.
        static::$method = $_SERVER['REQUEST_METHOD'];
        static::$ip     = Session::getIpAddress();
        static::$query  = Strings::from($_SERVER['REQUEST_URI'], '?');
        static::$url    = Strings::ensureStartsNotWith($_SERVER['REQUEST_URI'], '/');
        static::$url    = Strings::until(static::$url, '?');

        // Ensure the post-processing function is registered
        Log::information(tr('[:method] ":url" from client ":client"', [
            ':method' => static::$method,
            ':url'    => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            ':client' => Session::getIpAddress() . (empty($_SERVER['HTTP_X_REAL_IP']) ? '' : ' (Real IP: ' . $_SERVER['HTTP_X_REAL_IP'] . ')'),
        ]), 9);

        // Hide all request data, $_GET & $_POST, but NOT YET $_FILES!
        // Hiding $_FILES data requires the users_id and is done in Request::executeWebTarget()
        GetValidator::hideData();
        PostValidator::hideData();
    }


    /**
     * Execute the specified route
     *
     * @param bool   $system
     *
     * @return never
     */
    #[NoReturn] protected static function execute(bool $system = false): never
    {
        Core::removeShutdownCallback(404);

        // Get routing parameters and find the correct web page file for this route
        $parameters = static::getParametersObject()->select(static::$url);
        $route      = new FsFile(static::$page, FsRestrictions::newReadonly(DIRECTORY_WEB));

        // Setup the request object, send parameters, attachment configuration and if this is a system request
        Request::setRoutingParameters($parameters);
        Request::setAttachment(static::$attachment);
        Request::setSystem($system);

        // Target may NEVER be web/index.php because that will run the router into endless loops!
        if ($route->isPath('index.php')) {
            throw new RouteException(tr('Will not route to resolved file "index.php" as this would cause an endless loop'));
        }

        if ($route->hasExtension('php')) {
            // The route is a PHP file, so execute it. The Page object will take care of everything, even if it's an
            // attachment that the client will download instead of view in the browser.
            Request::execute($route);
        }

        // The file is NOT a PHP executable, send the resolved file contents to the client directly
        throw new UnderConstructionException(tr('Implement routing to files!'));
        //FileResponse::new()->$request)->send();
    }


    /**
     * Execute the specified system page
     *
     * @param int            $target An integer and valid HTTP code which will display the system page for that HTTP code
     * @param Throwable|null $e
     * @param string|null    $message
     *
     * @return never
     */
    #[NoReturn] protected static function executeSystem(int $target, ?Throwable $e = null, ?string $message = null): never
    {
        Core::removeShutdownCallback(404);

        // Get routing parameters and find the correct target page
        $parameters = static::getParametersObject()->select((String) $target, true);

        Request::setRoutingParameters($parameters, true);
        Request::executeSystem($target, $e, $message);
    }


    /**
     * Returns the routing parameters list
     *
     * @return RoutingParametersList
     */
    public static function getParametersObject(): RoutingParametersList
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
        Request::setRestrictions(FsRestrictions::newReadonly(DIRECTORY_WEB));
        Response::initialize();

        if (Core::getMaintenanceMode()) {
            // We're running in maintenance mode, show the maintenance page
            Log::warning('WARNING: Not processing routes, system is in maintenance mode');
            static::executeSystem(503);
        }

        // Domain should NOT end with a .
        if (str_ends_with($_SERVER['HTTP_HOST'], '.')) {
            // Redirect to the same URL, but the host without .
            Response::redirect($_SERVER['REQUEST_SCHEME'] . '://' . substr($_SERVER['HTTP_HOST'], 0, -1) . $_SERVER['REQUEST_URI']);
        }

        // URI may not be more than 2048 bytes
        if (strlen(static::$url) > 2048) {
            Log::warning(tr('Requested URI ":uri" has ":count" characters, where 2048 is a hardcoded limit for compatibility (See Phoundation\Web\Route class). 400-ing the request', [
                ':uri'   => static::$url,
                ':count' => strlen(static::$url),
            ]));
            static::executeSystem(400);
        }

        // Check for double // anywhere in the URL, this is automatically rejected with a 404, not found
        // NOTE: This is checked on $_SERVER['REQUEST_URI'] and not static::$url because static::$url already has the
        // first slash(es) stripped during the __construct() phase
        if (str_contains($_SERVER['REQUEST_URI'], '//')) {
            Log::warning(tr('Requested URI ":uri" contains one or multiple double slashes, automatically rejecting this with a 404 page', [
                ':uri' => $_SERVER['REQUEST_URI'],
            ]));
            static::executeSystem(404);
        }

        if (str_ends_with($_SERVER['HTTP_HOST'], '.')) {
            // The specified domain ends with a "." like "phoundation.org." instead of "phoundation.org" so redirect
            Response::redirect($_SERVER['REQUEST_SCHEME'] . '://' . Strings::ensureEndsNotWith($_SERVER['HTTP_HOST'], '.') . $_SERVER['REQUEST_URI']);
        }

        // Apply mappings
        static::$url = static::applyMappings(static::$url);

        // Ensure a 404 is shown if route cannot execute anything
        Core::addShutdownCallback(404, function () {
            Request::setRoutingParameters(static::getParametersObject()->select('system/404', true), true);
            static::executeSystem(404);
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
        return static::getMapping()->apply($url);
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
     * Returns the rule number that is being processed
     *
     * @return int
     */
    public static function getRuleCount(): int
    {
        return static::$rule_count;
    }


    /**
     * Returns true if ALL GET variables will be passed, FALSE if none will be passed, or a comma delimited string if
     * some will be passed
     *
     * @return array|bool
     */
    public static function getPassGetVariables(): array|bool
    {
        return static::$pass_get_variables;
    }


    /**
     * Returns the page detected by Route::try()
     *
     * @return string|null
     */
    public static function getPage(): ?string
    {
        return static::$page;
    }


    /**
     * Returns if the contents of the current page should be sent to the client as an attachment or not
     *
     * @return bool
     */
    public static function getAttachment(): bool
    {
        return static::$attachment;
    }


    /**
     * Returns if the current request will be blocked or not
     *
     * @return bool
     */
    public static function getBlockRequest(): bool
    {
        return static::$block_request;
    }


    /**
     * Returns the flags for the matched routing rule
     *
     * @return array
     */
    public static function getFlags(): array
    {
        return static::$flags;
    }


    /**
     * Returns if static routing rules should be applied
     *
     * @return bool
     */
    public static function getApplyStaticRoutes(): bool
    {
        return static::$apply_static_routes;
    }


    /**
     * Returns until when a static route should be applicable
     *
     * @return false|int
     */
    public static function getUntil(): false|int
    {
        return static::$until;
    }


    /**
     * Returns the route being tried
     *
     * @return string
     */
    public static function getRoute(): string
    {
        return static::$route;
    }


    /**
     * Returns the URL regex for the current route being tried
     *
     * @return string
     */
    public static function getUrlRegex(): string
    {
        return static::$url_regex;
    }


    /**
     * Returns if multilingual support has been disabled or not
     *
     * @return bool
     */
    public static function getDisableLanguage(): bool
    {
        return static::$disable_language;
    }


    /**
     * Tracks the matches from the regex applied to the URL
     *
     * @return array
     */
    public static function getRegexMatches(): array
    {
        return static::$regex_matches;
    }


    /**
     * Returns the original resource request
     *
     * @return string
     */
    public static function getRequest(): string
    {
        return (string) Url::getWww();
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
        return CookieValidator::new()->getSource();
    }


    /**
     * Returns the POST data from the request
     *
     * @return array
     */
    public static function getPostData(): array
    {
        return PostValidator::new()->getSource();
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
                    if (!preg_match($match_regex, static::$url)) {
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
            static::$url = preg_replace($replace_regex, $replace_value, static::$url);

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
     * The Route::try() call requires 3 arguments; $regex, $route, and $flags.
     *
     * The first argument is the PERL compatible regular expression that will match the URL you wish to route to a
     * page.
     * Note that this must be a FULL regular expression with opening and closing tags. / is recommended for these tags,
     * but not required. See https://www.php.net/manual/en/function.preg-match.php for more information about PERL
     * compatible regular expressions. This regular expression may capture variables which then can be used in the
     * route as $1, $2 for the first and second variable respectitively. Regular expression flags like i (case
     * insensitive matches), u (unicode matches), etc. may be added after the trailing / of this variable
     *
     * The second argument is the page you wish to execute and the variables that should be sent to it. If your regular
     * expression captured variables, you may use these variables here. If the page name itself is a variable, then
     * Route::add() will try to find that page, and execute it if it exists
     *
     * The third argument is a list (CSV string or array) with flags. Current allowed flags are:
     * A                Process the route as an attachement (i.e. Send the file so that the browser client can
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
     * @param string $route
     * @param string $flags
     *
     * @return bool
     * @throws RouteException|\Throwable
     * @package Web
     * @see     Request::executeSystem()
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
    public static function try(string $url_regex, string $route, string $flags = ''): bool
    {
        static::getInstance();
        static::validateHost();

        try {
            if (!static::tryRegex($url_regex, $route, $flags)) {
                return false;
            }

            // The supplied regex with flags matched, execute it!
            static::execute();

        } catch (Exception $e) {
            if (str_starts_with($e->getMessage(), 'PHP ERROR [2] "preg_match_all():')) {
                // A user defined regex failed, give pretty error
                throw new RouteException(tr('Failed to process route pattern ":count" ":regex" with error ":e"', [
                    ':count' => static::$rule_count,
                    ':regex' => $url_regex,
                    ':e'     => trim(Strings::cut($e->getMessage(), 'preg_match_all():', '" in')),
                ]));
            }

            if (str_starts_with($e->getMessage(), 'PHP ERROR [2] "preg_match():')) {
                // A user defined regex failed, give pretty error
                throw new RouteException(tr('Failed to process route pattern ":count" ":regex" with error ":e"', [
                    ':count' => static::$rule_count,
                    ':regex' => $url_regex,
                    ':e'     => trim(Strings::cut($e->getMessage(), 'preg_match():', '" in')),
                ]));
            }

            throw $e;
        }
    }


    /**
     * Tries to apply the specified regex on the current request. If it applies, will execute the page
     *
     * @param string $url_regex
     * @param string $route
     * @param string $flags
     *
     * @return bool
     * @throws Throwable
     */
    protected static function tryRegex(string $url_regex, string $route, string $flags = ''): bool
    {
        if (!$url_regex) {
            // Match an empty string
            $url_regex = '/^$/';
        }

        // Set route and regex
        // Apply pre-matching flags. Depending on individual flags we may do different things
        static::$route     = $route;
        static::$url_regex = $url_regex;
        static::$flags     = explode(',', $flags);

        // Pre-apply flags and static routes
        static::preApplyFlags();
        static::applyStaticRoutes();

        // Check if this route regex matches
        if (!static::match()) {
            // The route regex did not match, match cancelled this try
            static::$rule_count++;
            return false;
        }

        // Apply constants replacements, route variables replacements, get variables replacements
        static::applyConstantsReplacements();
        static::applyRouteVariablesReplacements();
        static::applyGetVariableReplacements();

        // Apply flags
        if (!static::applyFlags()) {
            // Flags cancelled this try
            return false;
        }

        if (!static::processGetQueries()) {
            // Query processing cancelled this try
            return false;
        }

        static::translateRoute();
        static::processRouteGetVariables();
        static::processStaticRequests();

        if (static::$block_request) {
            // Block the request by dying
            exit();
        }

        // This is the one!
        return true;
    }


    /**
     * Processes static request requirements
     *
     * @return void
     * @throws Throwable
     */
    protected static function processStaticRequests(): void
    {
        if (static::$until) {
            // Store the request as a rule until it expires. Apply semi-permanent routing for this IP
            // Remove the "S" flag since we don't want to store the rule again in subsequent loads
            // Remove the "H" flag since subsequent requests may not be a hack attempt. Since we are going to act as
            // if the rule AND URI apply, we don't know really, avoid unneeded red flags
            foreach (static::$flags as $id => $flag) {
                switch ($flag[0]) {
                    case 'H':
                        // no break

                    case 'S':
                        unset(static::$flags[$id]);
                        break;
                }
            }

            Route::makeStatic([
                'expiredon' => static::$until,
                'route'     => $route,
                'regex'     => static::$url_regex,
                'flags'     => static::$flags,
                'url'       => static::$url,
                'ip'        => static::$ip,
            ]);
        }
    }


    /**
     * Tries to pass URL GET variables to the GetValidator
     *
     * @return void
     */
    protected static function processRouteGetVariables(): void
    {
        // Split the route into the page name and GET requests
        static::$page  = Strings::until(static::$route, '?');
        $get_variables = Strings::from(static::$route , '?', needle_required: true);

        if (!static::$block_request and $get_variables) {
            // If we have GET parameters in the route, add them to the $_GET array
            $get_variables = explode('&', $get_variables);

            foreach ($get_variables as $entry) {
                GetValidator::new()->add(Strings::from($entry, '=', needle_required: true), Strings::until($entry, '='));
            }
        }
    }


    /**
     * Will translate the current route for the current language
     *
     * @return void
     */
    protected static function translateRoute(): void
    {
        // Translate the route?
        if (isset($core->register['Route::map']) and empty(static::$disable_language)) {
            // Found mapping configuration.
            // Find language match.
            // Assume that "static::$regex_matches[1]" contains the language,
            // unless specified otherwise
            if (isset($core->register['Route::map']['language'])) {
                $language = isset_get(static::$regex_matches[$core->register['Route::map']['language']][0]);

            } else {
                $language = isset_get(static::$regex_matches[1][0]);
            }

            if ($language !== 'en') {
                // The requested page is in a non-English language. This means that the entire URL MUST be in that
                // language. Translate the URL to its English counterpart
                $translated = false;

                // Check if the route map has the requested language
                if (empty($core->register['Route::map'][$language])) {
                    Log::warning(tr('Requested language ":language" does not have a language map available', [
                        ':language' => $language,
                    ]));

                    // TODO Check if this should be 404 or maybe some other HTTP code?
                    static::executeSystem(404);

                } else {
                    // Found a map for the requested language
                    Log::notice(tr('Attempting to remap for language ":language"', [':language' => $language]));

                    foreach ($core->register['Route::map'][$language] as $unknown => $remap) {
                        if (str_contains(static::$route, $unknown)) {
                            $translated    = true;
                            static::$route = str_replace($unknown, $remap, static::$route);
                        }
                    }

                    if (!file_exists(static::$route)) {
                        Log::warning(tr('Language remapped route ":route" does not exist', [
                            ':route' => static::$route
                        ]));

                        static::executeSystem(404);
                    }

                    Log::success(tr('Found remapped route ":route"', [':route' => static::$route]));
                }

                if (!$translated) {
                    // Page was not translated, ie it's still the original, and no translation was found.
                    Log::warning(tr('Requested language ":language" does not have a translation available in the language map for route ":route"', [
                        ':language' => $language,
                        ':route'    => static::$route,
                    ]));

                    static::executeSystem(404);
                }
            }
        }
    }


    /**
     * Processes GET request queries, adding, removing, or modifying variables depending on static::$pass_get_variables
     *
     * @return bool
     */
    protected static function processGetQueries(): bool
    {
        // Do we allow any $_GET queries from the REQUEST_URI?
        if (!static::$pass_get_variables) {
            if (!GetValidator::new()->isEmpty()) {
                // Client specified variables on a URL that does not allow queries, cancel the match
                Log::warning(tr('Matched route ":route" does not allow query variables while client specified them, cancelling match', [
                    ':route' => static::$route,
                ]));

                static::$rule_count++;
                return false;
            }

            return true;
        }

        if (static::$pass_get_variables !== true) {
            // The variable $pass_get_variables contains a list of keys to pass and information on how to pass them.
            foreach (static::$pass_get_variables as $key => $value) {
                if (str_contains('=', $key)) {
                    // Regenerate the key as a $key => $value instead of $key=$value => null
                    static::$pass_get_variables[Strings::until($key, '=')] = Strings::from($key, '=');
                    unset(static::$pass_get_variables[$key]);

                } else {
                    static::$pass_get_variables[$key] = true;
                }
            }

            // Go over all $_GET variables and ensure they're allowed
            foreach (GetValidator::new() as $key => $action) {
                // This key must be allowed, or we're done
                if (empty(static::$pass_get_variables[$key])) {
                    Log::warning(tr('Matched route ":route" contains GET key ":key" which is not specifically allowed by the pass_get_variables list, cancelling match', [
                        ':route' => static::$route,
                        ':key'   => $key,
                    ]));

                    static::$rule_count++;
                    return false;
                }

                // Okay, the key is allowed, yay! What action are we going to take?
                switch ($action) {
                    case null:
                        // Allow this query variable
                        break;

                    case 301:
                        // TODO What is going on here? Redirects to URL, but only domain is used? Wut?
                        throw new UnderConstructionException(tr('GET Query redirect rules are under construction'));
                        // Redirect to URL without query
                        $domain = Url::getDomain()->getThis();
                        $domain = Strings::until($domain, '?');

                        Log::warning(tr('Matched route ":route" allows GET key ":key" as redirect to URL without query', [
                            ':route' => static::$route,
                            ':key'   => $key,
                        ]));

                        Request::setRoutingParameters(static::getParametersObject()->select(static::$url));
                        Response::redirect($domain);
                }
            }
        }

        return true;
    }


    /**
     * Replaces variables in the route with GET variables
     */
    protected static function applyGetVariableReplacements(): void
    {
        // Apply regex GET variables replacements
        if (preg_match_all('/\$(\w+)\$/', static::$route, $replacements)) {
            foreach ($replacements[1] as $replacement) {
                try {
                    if (!$replacement) {
                        throw new RouteException(tr('Invalid empty regex replacement specified in route ":route"', [
                            ':route' => static::$route,
                        ]));
                    }

                    if (!GetValidator::new()->sourceKeyExists($replacement)) {
                        throw new RouteException(tr('Regex replacement ":replacement" specified in route ":route" does not exist in the $_GET array', [
                            ':replacement' => '$' . $replacement . '$',
                            ':route'       => static::$route,
                        ]));
                    }

                    static::$route = str_replace('$' . $replacement . '$', GetValidator::new()->get($replacement), static::$route);

                } catch (Throwable $e) {
                    Log::warning(tr('Ignoring route ":route" because regex ":regex" has the error ":e"', [
                        ':regex' => static::$url_regex,
                        ':route' => static::$route,
                        ':e'     => $e->getMessage(),
                    ]));
                }
            }

            if (str_contains('$', static::$route)) {
                // There are regex variables left that were not replaced. Replace them with nothing
                static::$route = preg_replace('/\$\w+\$/', '', static::$route);
            }
        }
    }


    /**
     * Substitutes variables specified in the route
     *
     * @return void
     */
    protected static function applyConstantsReplacements(): void
    {
        // Regex matched. Do variable substitution on the route.
        if (preg_match_all('/:([A-Z_]+)/', static::$route, $variables)) {
            array_shift($variables);

            foreach (array_shift($variables) as $variable) {
                switch ($variable) {
                    case 'PROTOCOL':
                        // The protocol used in the current request
                        static::$route = str_replace(':PROTOCOL', $_SERVER['REQUEST_SCHEME'] . '://', static::$route);
                        break;

                    case 'DOMAIN':
                        // The domain used in the current request
                        static::$route = str_replace(':DOMAIN', $_SERVER['HTTP_HOST'], static::$route);
                        break;

                    case 'LANGUAGE':
                        // The language specified in the current request
                        static::$route = str_replace(':LANGUAGE', LANGUAGE, static::$route);
                        break;

                    case 'REQUESTED_LANGUAGE':
                        // The language requested in the current request
                        // TODO This should be coming from Http class
                        // TODO Implement
//                            $requested = Arrays::firstValue(Arrays::force(Core::readRegister('http', 'accepts_languages')));
//                            static::$route     = str_replace(':REQUESTED_LANGUAGE', $requested['language'], static::$route);
                        static::$route = str_replace(':REQUESTED_LANGUAGE', 'en', static::$route);
                        break;

                    case 'PORT':
                        // no break

                    case 'SERVER_PORT':
                        // The port used in the current request
                        static::$route = str_replace(':PORT', $_SERVER['SERVER_PORT'], static::$route);
                        break;

                    case 'REMOTE_PORT':
                        // The port used by the client
                        static::$route = str_replace(':REMOTE_PORT', $_SERVER['REMOTE_PORT'], static::$route);
                        break;

                    default:
                        throw new OutOfBoundsException(tr('Unknown variable ":variable" found in route ":route"', [
                            ':variable' => ':' . $variable,
                            ':route'    => ':' . static::$route,
                        ]));
                }
            }
        }
    }


    /**
     * Tries to find and apply static routes
     *
     * @return void
     */
    protected static function applyStaticRoutes(): void
    {
        if ((static::$rule_count === 1) and Config::get('web.route.static', false)) {
            if (static::$apply_static_routes) {
                // Check if remote IP is registered for special routing
                $exists = sql()->get('SELECT   `id`, `url`, `regex`, `route`, `flags`
                                      FROM     `routes_static` 
                                      WHERE    `ip` = :ip 
                                        AND    `status` IS NULL 
                                        AND    `expiredon` >= NOW() 
                                      ORDER BY `created_on` DESC 
                                      LIMIT 1', [':ip' => static::$ip]);

                if ($exists) {
                    // Apply semi-permanent routing for this IP
                    Log::warning(tr('Found active routing for IP ":ip", continuing routing as if request is URI ":url" with regex ":regex", route ":route", and flags ":flags" instead', [
                        ':ip'     => static::$ip,
                        ':url'    => $exists['url'],
                        ':regex'  => $exists['regex'],
                        ':route'  => $exists['route'],
                        ':flags'  => $exists['flags'],
                    ]));

                    static::$url       = $exists['url'];
                    static::$url_regex = $exists['regex'];
                    static::$route     = $exists['route'];
                    static::$flags     = explode(',', $exists['flags']);

                    sql()->query('UPDATE `routes_static` SET `applied` = `applied` + 1 WHERE `id` = :id', [':id' => $exists['id']]);
                    unset($exists);
                }

            } else {
                Log::warning(tr('Not checking for routes per N flag'));
            }
        }
    }


    /**
     * Tries to match the URL regex on the specified URL and returns true if successful
     *
     * @return bool
     */
    protected static function match(): bool
    {
        // Match the specified regex. If there is no match, there is nothing else to do for us here
        Log::action(tr('Testing rule ":count" ":regex" on ":type" ":url"', [
            ':count' => static::$rule_count,
            ':regex' => static::$url_regex,
            ':type'  => static::$method,
            ':url'   => static::$url,
        ]), 4);

        try {
            $match = preg_match_all(static::$url_regex, static::$url, static::$regex_matches);

        } catch (Exception $e) {
            throw new RouteException(tr('Failed to parse route ":route" with ":message"', [
                ':route'   => static::$url_regex,
                ':message' => Strings::until(Strings::from($e->getMessage(), 'preg_match_all(): '), ' in '),
            ]));
        }

        if (!$match) {
            // No match, stop this try
            return false;
        }

        if (Debug::isEnabled()) {
            Log::success(tr('Regex ":count" ":regex" matched with matches ":matches" and flags ":flags"', [
                ':count'   => static::$rule_count,
                ':regex'   => static::$url_regex,
                ':matches' => Strings::force(static::$regex_matches, ', '),
                ':flags'   => Strings::force(static::$flags        , ', '),
            ]), 5);
        }

        return true;
    }


    /**
     * Applies a specific set of flags early on in the route matching process
     *
     * @return void
     */
    protected static function preApplyFlags(): void
    {
        foreach (static::$flags as $flag) {
            if (!$flag) {
                continue;
            }

            switch ($flag[0]) {
                case 'D':
                    // Include domain in match
                    static::$url = $_SERVER['HTTP_HOST'] . static::$url;
                    Log::notice(tr('Adding complete HTTP_HOST in match for URI ":url"', [':url' => static::$url]));
                    break;

                case 'M':
                    static::$url .= '?' . static::$query;
                    Log::notice(tr('Adding query to URI ":url"', [':url' => static::$url]));

                    if (!array_search('Q', static::$flags)) {
                        // Auto imply Q
                        static::$flags[] = 'Q';
                    }

                    break;

                case 'N':
                    static::$apply_static_routes = false;
            }
        }
    }


    /**
     * Applies routing flags
     *
     * @return bool
     */
    protected static function applyFlags(): bool
    {
        // Apply specified post matching flags. Depending on individual flags we may do different things
        foreach (static::$flags as $flags_id => $flag) {
            if (!$flag) {
                // Completely ignore empty flags
                continue;
            }

            switch ($flag[0]) {
                case 'A':
                    // Send the file as a downloadable attachment
                    static::$attachment = true;
                    break;

                case 'B':
                    // Block this request, send nothing
                    Log::warning(tr('Blocking request as per B flag'));
                    static::$block_request = true;
                    break;

                case 'C':
                    // URL cloaking will execute the resolved URL directly, so if it returns here at all it didn't match
                    static::applyFlagCloak($flags_id);
                    return false;

                case 'G':
                    if (!static::applyFlagGet()) {
                        return false;
                    }

                    break;

                case 'H':
                    static::applyFlagHack();
                    break;

                case 'L':
                    // Disable language support
                    static::$disable_language = true;
                    break;

                case 'P':
                    if (!static::applyFlagPost()) {
                        return false;
                    }

                    break;

                case 'Q':
                    static::applyFlagPassQueries($flag);
                    break;

                case 'R':
                    static::applyFlagRedirect($flag);

                case 'S':
                    static::applyFlagStoreRule($flag);
                    break;

                case 'T':
                    static::$temp_template = substr($flag, 1);
                    break;

                case 'X':
                    // TODO Where do these restrictions apply? This seems to not be used anywhere!
                    // Restrict access to the specified path list
                    $restrictions = substr($flag, 1);
                    $restrictions = str_replace(';', ',', $restrictions);
                    break;

                case 'Y':
                    static::applyFlagExecuteSystem();

                case 'Z':
                    static::applyFlagRestrictAccess($flag);
            }
        }

        return true;
    }


    /**
     * Processes the restrict access flag
     *
     * @param string $flag
     *
     * @return void
     */
    protected static function applyFlagRestrictAccess(string $flag): void
    {
        // Restrict access to users with the specified right, or execute the specified page instead
        // (defaults to 403). Format is Z$RIGHT$[$PAGE$] and multiple Z rules may be specified
        if (!preg_match_all('/^Z(.+?)(?:\[(.+?)])?$/iu', $flag, static::$regex_matches)) {
            Log::warning(tr('Invalid "Z" (requires right) rule ":flag" encountered, denying access by default for security', [
                ':flag' => $flag,
            ]));

            static::executeSystem(403);
        }

        $right        = get_null(isset_get(static::$regex_matches[1][0]));
        static::$page = get_null(isset_get(static::$regex_matches[2][0]));

        if (Session::getUserObject()->isGuest()) {
            Log::warning(tr('Denied guest user access to resource because signed in user is required'));
            static::executeSystem(401);
        }

        if (!Session::getUserObject()->hasAllRights($right)) {
            Log::warning(tr('Denied user ":user" access to resource because of missing right ":right"', [
                ':user'  => Session::getUserObject()->getLogId(),
                ':right' => $right,
            ]));

            static::executeSystem(403);
        }
    }


    /**
     * Processes the execute as system request flag
     *
     * @return never
     */
    #[NoReturn] protected static function applyFlagExecuteSystem(): never
    {
        // Execute the resolved page as a system page
        if (!is_numeric_integer(static::$route)) {
            throw new OutOfBoundsException(tr('Cannot execute ":route" from route ":url_regex" as a system page, it should be an integer HTTP compatible number', [
                ':url_regex' => static::$url_regex,
                ':route'     => static::$route
            ]));
        }

        static::executeSystem((int) static::$route);
    }


    /**
     * Processes the store rule flag
     *
     * @param string $flag
     *
     * @return never
     */
    protected static function applyFlagStoreRule(string $flag): void
    {
        $until = substr($flag, 1);

        if ($until and !is_natural($until)) {
            $until = null;

            Log::warning(tr('Specified S flag value ":value" is invalid, natural number expected. Falling back to default value of 86400', [
                ':value' => $until,
            ]));
        }

        if (!$until) {
            static::$until = 86400;
        }

    }


    /**
     * Processes the redirect flag
     *
     * @param string $flag
     *
     * @return never
     */
    #[NoReturn] protected static function applyFlagRedirect(string $flag): never
    {
        // Validate the HTTP code to use, then redirect to the specified route
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
                throw new RouteException(tr('Invalid R flag HTTP CODE ":code" specified for route ":route"', [
                    ':code'  => ':' . $http_code,
                    ':route' => ':' . static::$route,
                ]));
        }

        Core::removeShutdownCallback(404);

        Request::setRoutingParameters(static::getParametersObject()->select(static::$url));
        Response::redirect(Url::getWww(static::$route)->addQueries($_GET), (int) $http_code);
    }


    /**
     * Processes the pass queries flag
     *
     * @param string $flag
     *
     * @return void
     */
    protected static function applyFlagPassQueries(string $flag): void
    {
        // Let GET request queries pass through
        if (strlen($flag) === 1) {
            static::$pass_get_variables = true;
            return;
        }

        static::$pass_get_variables = explode('|', substr($flag, 1));
        static::$pass_get_variables = array_flip(static::$pass_get_variables);
    }


    /**
     * Processes the URL get flag, requiring the request to be a GET request
     *
     * @return bool
     */
    protected static function applyFlagGet(): bool
    {
        // MUST be a GET request, NO POST data allowed!
        if (!empty($_POST)) {
            Log::notice(tr('Matched route ":route" allows only GET requests, cancelling match', [
                ':route' => static::$route
            ]));

            static::$rule_count++;
            return false;
        }

        return true;
    }


    /**
     * Processes the URL post flag, requiring the request to be a POST request
     *
     * @return bool
     */
    protected static function applyFlagPost(): bool
    {
        // MUST be a POST request, NO EMPTY POST data allowed!
        if (empty($_POST)) {
            Log::notice(tr('Matched route ":route" allows only POST requests, cancelling match', [
                ':route' => static::$route
            ]));

            static::$rule_count++;
            return false;
        }

        return true;
    }


    /**
     * Processes the URL hacked flag
     *
     * @return void
     */
    protected static function applyFlagHack(): void
    {
        Log::notice(tr('*POSSIBLE HACK ATTEMPT DETECTED*'));
        Notification::new()
            ->setUrl('security/incidents.html')
            ->setMode(EnumDisplayMode::exception)
            ->setCode('hack')
            ->setRoles('security')
            ->setTitle(tr('*Possible hack attempt detected*'))
            ->setMessage(tr('The IP address ":ip" made the request ":request" which was matched by regex ":regex" with flags ":flags" and caused this notification', [
                ':ip'      => static::$ip,
                ':request' => static::$url,
                ':regex'   => static::$url_regex,
                ':flags'   => implode(',', static::$flags),
            ]))
            ->send();
    }


    /**
     * Processes the URL cloaking flag
     *
     * @param int $flags_id
     *
     * @return bool
     */
    protected static function applyFlagCloak(int $flags_id): bool
    {
        // URL cloaking was used. See if we have a real URL behind the specified cloak
        $url = Url::getWww(static::$route)->decloak();

        if (!$url) {
            Log::warning(tr('Specified cloaked URL ":cloak" does not exist, cancelling match', [
                ':cloak' => static::$route
            ]));

            static::$rule_count++;
            return false;
        }

        $url = Strings::from($url, '://');
        $url = Strings::from($url, '/');

        Log::notice(tr('Redirecting cloaked URL ":cloak" internally to ":url"', [
            ':cloak' => static::$route,
            ':url'   => $_SERVER['REQUEST_URI'],
        ]));

        throw new UnderConstructionException(tr('URL cloacking support in Route is broken and under construction'));
        $_SERVER['REQUEST_URI'] = $url;
        static::$rule_count = 1;
        unset(static::$flags[$flags_id]);
        static::execute();
    }


    /**
     * Replaces route variables with their replacements
     *
     * @return void
     */
    protected static function applyRouteVariablesReplacements(): void
    {
        if (preg_match_all('/\$(\d+)/', static::$route, $replacements)) {
            if (preg_match('/\$\d+\.php/', static::$route)) {
                static::$dynamic_pagematch = true;
            }

            // Split the route into URL and query
            $url     = Strings::until(static::$route, '?');
            $queries = Strings::from(static::$route , '?', needle_required: true);
            $queries = explode('&', $queries);

            // Replace parts, remove queries with empty values
            foreach ($replacements[1] as $replacement) {
                try {
                    static::applyVariableReplacement($url, $queries, $replacement);

                } catch (Exception $e) {
                    Log::warning(tr('Ignoring route ":route" because regex ":regex" has the error ":e"', [
                        ':regex' => static::$url_regex,
                        ':route' => static::$route,
                        ':e'     => $e->getMessage(),
                    ]));
                }
            }

            static::rebuildRouteFromParts($url, $queries);
        }
    }


    /**
     * Replaces a single route variable with its replacement
     *
     * @param string $url
     * @param array  $queries
     * @param string $replacement
     *
     * @return void
     */
    protected static function applyVariableReplacement(string &$url, array &$queries, string $replacement): void
    {
        if (!$replacement or empty(static::$regex_matches[$replacement])) {
            throw new RouteException(tr('Non existing regex replacement ":replacement" specified in route ":route"', [
                ':replacement' => '$' . $replacement,
                ':route'       => static::$route,
            ]));
        }

        // Replace URL variables
        $url = str_replace('$' . $replacement, static::$regex_matches[$replacement][0], $url);

        // Replace query variables, removing queries that have empty variables
        foreach ($queries as $location => $query) {
            if (str_contains($query, '$' . $replacement)) {
                if (empty(static::$regex_matches[$replacement][0])) {
                    // This query has an empty variable, remove it
                    unset($queries[$location]);

                } else {
                    // This query has a variable with data, replace it
                    $queries[$location] = str_replace('$' . $replacement, static::$regex_matches[$replacement][0], $query);
                }
            }
        }
    }


    /**
     * Rebuilds and returns the route variable from the specified url and queries parts
     *
     * @param string $url
     * @param array  $queries
     *
     * @return void
     */
    protected static function rebuildRouteFromParts(string $url, array $queries): void
    {
        if (str_contains('$', $url)) {
            // There are regex variables left that were not replaced. Replace them with nothing
            $url = preg_replace('/\$\d/', '', $url);
        }

        foreach ($queries as $location => $query) {
            if (str_contains('$', $query)) {
                // There are regex variables left that were not replaced. Remove these queries
                unset($queries[$location]);
            }
        }

        // Put the route back together from URL and query
        static::$route = $url;

        if ($queries) {
            static::$route .= '?' . implode('&', $queries);
        }
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
                Request::setRoutingParameters(static::getParametersObject()
                                                    ->select(Url::getRootDomainRootUrl()));
                Response::redirect(Url::getRootDomainRootUrl());
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
                    Request::setRoutingParameters(static::getParametersObject()
                                                        ->select(Url::getRootDomainRootUrl()));
                    Response::redirect(Url::getRootDomainRootUrl());
                }
                // Redirect to correct page
                Request::setRoutingParameters(static::getParametersObject()
                                                    ->select(Url::getRootDomainUrl()));
                Response::redirect(Url::getRootDomainUrl());
            }
        }
    }


    /**
     * Create a static route for the specified IP
     *
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
    protected static function makeStatic(string $ip): StaticRoute
    {
        Log::notice(tr('Creating static route ":route" for IP ":ip"', [
            ':route' => static::$route,
            ':ip'    => $ip,
        ]));

        // TODO Implement
        return StaticRoute::new();
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
     * @param string $reason
     *
     * @return void
     */
    protected static function block(string $ip, string $reason): void
    {
        if (!static::$until) {

        }

        Log::notice(tr('Blocking IP ":ip" until ":until" because ":reason"', [
            ':until' => DateTime::new(static::$until),
            ':ip'    => $ip,
        ]));
//        $route FirewallEntry = Firewall::block($ip);
//        // TODO Implement
//
//        return FirewallEntry;
//        return $route;
    }
}
