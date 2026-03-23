<?php

/**
 * Class Session
 *
 * This class manages the session of a single user (The current user)
 *
 * This Session class is static
 *
 * @see       https://www.tonymarston.net/php-mysql/session-handler.html
 * @see       https://shiflett.org/articles/storing-sessions-in-a-database
 * @see       https://culttt.com/2013/02/04/how-to-save-php-sessions-to-a-database/
 * @see       https://konrness.com/php5/how-to-prevent-blocking-php-requests/
 * @see       https://www.php.net/manual/en/memcached.sessions.php
 * @see       https://www.php.net/manual/en/function.session-set-save-handler.php
 * @see       https://jennifersoft.com/en/blog/tech/2019-04-08/
 * @see       https://stackoverflow.com/questions/3512507/proper-way-to-logout-from-a-session-in-php
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions;

use DateTimeZone;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Users\Authentication;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Interfaces\SignInKeyInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Locale\Language\Interfaces\PhoLocaleInterface;
use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Accounts\Users\Sessions\Exception\SessionNotInitializedException;
use Phoundation\Accounts\Users\Sessions\Exception\SessionPostAndSignoutException;
use Phoundation\Accounts\Users\Sessions\Exception\SessionStartFailedException;
use Phoundation\Accounts\Users\Sessions\Interfaces\SessionInterface;
use Phoundation\Accounts\Users\Sessions\Interfaces\SessionStateInterface;
use Phoundation\Accounts\Users\Sessions\Interfaces\UserSessionInterface;
use Phoundation\Accounts\Users\SignInKey;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryStatusException;
use Phoundation\Data\Traits\TraitDataStaticFlashMessages;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Memcached\Memcached;
use Phoundation\Date\PhoDateTime;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\EndlessLoopException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Client;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Plugins\Phoundation\MultiFactorAuthentication\Interfaces\MultiFactorAuthenticationInterface;
use Throwable;


class Session implements SessionInterface
{
    use TraitDataStaticFlashMessages;


    /**
     * Tracks if the current session is open or not
     *
     * @var bool $open
     */
    protected static bool $open = false;

    /**
     * The IP address for this session
     *
     * @var string|null $ip_address
     */
    protected static ?string $ip_address = null;

    /**
     * Singleton
     *
     * @var SessionInterface
     */
    protected static SessionInterface $instance;

    /**
     * The current user for this session
     *
     * @var UserInterface|null $user
     */
    protected static ?UserInterface $user = null;

    /**
     * The current impersonated user for this session
     *
     * @var UserInterface|null $impersonated_user
     */
    protected static ?UserInterface $impersonated_user = null;

    /**
     * Tracks if the session has startup or not
     *
     * @var bool $has_started_up
     */
    protected static bool $has_started_up = false;

    /**
     * Language for this session
     *
     * @var string|null $language
     */
    protected static ?string $language = null;

    /**
     * Domain for this session
     *
     * @var string|null $domain
     */
    protected static ?string $domain = null;

    /**
     * Stores the sign-in key, if available
     *
     * @var SignInKeyInterface|null $key
     */
    protected static ?SignInKeyInterface $key = null;

    /**
     * Tracks if during this page load the user changed
     *
     * @var bool $user_changed
     */
    protected static bool $user_changed = false;

    /**
     * Cache object for the UserSession object for this session
     *
     * @var UserSessionInterface|null
     */
    protected static ?userSessionInterface $_user_session = null;

    /**
     * Tracks if the session should sign out when Session::exit() is called
     *
     * @var int|null $sign_out_on_exit
     */
    protected static ?int $sign_out_on_exit = null;

    /**
     * Tracks if any request for this session should be redirected to a different page
     *
     * @var UrlInterface|null $_redirect
     */
    protected static ?UrlInterface $_redirect = null;


    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(Session::$instance)) {
            Session::$instance = new static();
        }

        return Session::$instance;
    }


    /**
     * Returns the IP address for this session
     *
     * Though usually in REMOTE_ADDR, IP address may sometimes be a local address and the correct one may have been set
     * in any of the following list
     *
     * HTTP_X_REAL_IP
     * X_REAL_IP
     * X-Real-IP
     * x-real-ip
     * REMOTE_ADDR
     * HTTP_X_FORWARDED_FOR (can be comma delimited list of IPs)
     * HTTP_CLIENT_IP
     * HTTP_X_FORWARDED
     * HTTP_X_CLUSTER_CLIENT_IP
     * HTTP_FORWARDED_FOR
     * HTTP_FORWARDED
     * HTTP_X_FORWARDED_FOR
     * X-Forwarded-For
     * HTTP_FORWARDED
     * X-Forwarded-For
     * HTTP_FORWARDED
     * X-Forwarded-For
     * X-Forwarded-Host
     *
     * .... Much m
     *
     * @todo Improve remote IP detection using the list of variables above
     * @return string|null
     */
    public static function getIpAddress(): ?string
    {
        if (PLATFORM_CLI) {
            // We are on command line interface, there is no IP!
            return null;
        }

        if (empty(Session::$ip_address)) {
            // Correctly detect the remote IP

            // Set fields to check for IP
            $fields = [
                'x_real_ip'      => true,
                'http_client_ip' => true,
                'remote_addr'    => true,Session::$ip_address
            ];

            foreach ($_SERVER as $key => $value) {
                $key = strtolower($key);

                if (array_key_exists($key, $fields)) {
                    Session::$ip_address = $value;
                    break;
                }
            }
        }

        return Session::$ip_address;
    }


    /**
     * Sets if any request for this session should be redirected to a different page
     *
     * @param string|null $_redirect The redirect for this user session which will redirect (almost) every page for the user to this redirect only
     *
     * @return void
     */
    public static function setRedirect(string|null $_redirect = null): void
    {
        $_redirect = get_null($_redirect);
        $_redirect = Url::newOrNull($_redirect);

        $_SESSION['redirect'] = $_redirect?->makeWww()->getSource();
    }


    /**
     * Sets if any request for this session should be redirected to a different page
     *
     * @param UrlInterface|null $_redirect The redirect for this user session which will redirect (almost) every page for the user to this redirect only
     *
     * @return void
     */
    public static function setRedirectObject(UrlInterface|null $_redirect): void
    {
        Session::setRedirect($_redirect->getSource());
    }


    /**
     * Returns if any request for this session should be redirected to a different page
     *
     * @return string|null
     */
    public static function getRedirect(): ?string
    {
        return get_null((string) array_get_safe($_SESSION, 'redirect'));
    }


    /**
     * Returns if any request for this session should be redirected to a different page
     *
     * @return UrlInterface|null
     */
    public static function getRedirectObject(): ?UrlInterface
    {
        return Url::newOrNull(Session::getRedirect());
    }


    /**
     * Returns the unique session identifier
     *
     * @return string|null
     */
    public static function getId(): ?string
    {
        return get_null(session_id());
    }


    /**
     * Returns the IP address for this session when it was started
     *
     * @return string|null
     */
    public static function getOriginalIpAddress(): ?string
    {
        return array_get_safe($_SESSION, 'first_ip');
    }


    /**
     * Start this session
     *
     * @return void
     */
    public static function start(): void
    {
        if (Session::$has_started_up) {
            Log::warning(ts('Session has already started, not starting again'));
            return;
        }

        Log::action(ts('Starting session object'), 1);

        try {
            Session::checkDomains();
            Session::configureCookies();

        } catch (PhpException $e) {
            if (!Session::getConfigIgnoreSessionFail()) {
                throw $e;
            }
        }

        Session::resume();
        Session::$has_started_up = true;

        Http::setSslDefaultContext();
    }


    /**
     * Returns true if session failures should be ignored (only possible with debug mode enabled)
     *
     * @return bool
     */
    public static function getConfigIgnoreSessionFail(): bool
    {
        return Debug::isEnabled() and config()->getBoolean('platforms.web.sessions.ignore-fail', false);
    }


    /**
     * @return bool
     */
    public static function hasStartedUp(): bool
    {
        return Session::$has_started_up;
    }


    /**
     * Returns true if the user changed during this page load
     *
     * @return bool
     */
    public static function userChanged(): bool
    {
        return Session::$user_changed;
    }


    /**
     * Check the requested domain, if its a valid main domain, sub domain or whitelabel domain
     *
     * @return void
     * @todo See if this needs to move to the Domains class
     */
    protected static function checkDomains(): void
    {
        // :TODO: The next section may be included in the whitelabel domain check
        // Check if the requested domain is allowed
        Session::$domain = $_SERVER['HTTP_HOST'];

        if (!Session::$domain) {
            // No domain was requested at all, so probably instead of a domain name, an IP was requested. Redirect to
            // the domain name
            Response::redirect();
        }

        // Check the detected domain against the configured domain. If it does not match then check if it is a registered
        // whitelabel domain
        if (Session::$domain === Request::getDomain()) {
            // This is the primary domain

        } else {
            // This is not the registered domain!
            switch (config()->getBoolean('platforms.web.domains.whitelabels', false)) {
                case '':
                    // White label domains are disabled, so the requested domain MUST match the configured domain
                    Log::warning(ts('White labels are disabled, redirecting domain ":source" to ":target"', [
                        ':source' => $_SERVER['HTTP_HOST'],
                        ':target' => Request::getDomain(),
                    ]));

                    Response::redirect(PROTOCOL . Request::getDomain());

                case 'all':
                    // All domains are allowed
                    break;

                case 'sub':
                    // White label domains are disabled, but subdomains from the primary domain are allowed
                    if (Strings::from(Session::$domain, '.') !== Request::getDomain()) {
                        Log::warning(ts('Whitelabels are set to subdomains only, redirecting domain ":source" to ":target"', [
                            ':source' => $_SERVER['HTTP_HOST'],
                            ':target' => Request::getDomain(),
                        ]));

                        Response::redirect(PROTOCOL . Request::getDomain());
                    }

                    break;

                case 'list':
                    // This domain must be registered in the whitelabels list
                    Session::$domain = sql()->getColumn('SELECT `domain` 
                                                         FROM   `whitelabels` 
                                                         WHERE  `domain` = :domain 
                                                         AND   (`status` IS NULL OR `status` NOT LIKE "deleted%")', [
                                                             ':domain' => $_SERVER['HTTP_HOST']
                    ]);

                    if (empty(Session::$domain)) {
                        Log::warning(ts('Whitelabel check failed because domain was not found in database, redirecting domain ":source" to ":target"', [
                            ':source' => $_SERVER['HTTP_HOST'],
                            ':target' => Request::getDomain(),
                        ]));

                        Response::redirect(PROTOCOL . Request::getDomain());
                    }

                    break;

                default:
                    if (config()->getArrayBoolean('platforms.web.domains.whitelabels', false)) {
                        // Domain must be specified in one of the array entries
                        if (!in_array(Session::$domain, config()->getArray('platforms.web.domains.whitelabels'), true)) {
                            Log::warning(ts('Whitelabel check failed because domain was not found in configured array, redirecting domain ":source" to ":target"', [
                                ':source' => $_SERVER['HTTP_HOST'],
                                ':target' => Request::getDomain(),
                            ]));

                            Response::redirect(PROTOCOL . Request::getDomain());
                        }

                    } else {
                        // The domain must match either domain configuration or the domain specified in configuration
                        // "whitelabels.enabled"
                        if (Session::$domain !== config()->getArrayBoolean('platforms.web.domains.whitelabels', false)) {
                            Log::warning(ts('Whitelabel check failed because domain did not match only configured alternative, redirecting domain ":source" to ":target"', [
                                ':source' => $_SERVER['HTTP_HOST'],
                                ':target' => Request::getDomain(),
                            ]));

                            Response::redirect(PROTOCOL . Request::getDomain());
                        }
                    }
            }
        }
    }


    /**
     * Returns the domain for this session
     *
     * @return string
     */
    public static function getDomain(): string
    {
        return Session::$domain;
    }


    /**
     * Sets the domain for this session
     *
     * @return string
     */
    protected static function setDomain(): string
    {
        // Check what domains are accepted by the client (in order of importance) and see if we support any of those
        $supported_domains = config()->get('platforms.web.domains');

        if (array_key_exists($_SERVER['HTTP_HOST'], $supported_domains)) {
            Session::$domain = $_SERVER['HTTP_HOST'];

            return Session::$domain;
        }

        // No supported domain found, redirect to the primary domain
        Response::redirect(true);
    }


    /**
     * Returns the value for the given key / sub_key
     *
     * @param string|float|int      $key
     * @param string|float|int|null $sub_key
     *
     * @return mixed
     */
    public static function get(string|float|int $key, string|float|int|null $sub_key = null): mixed
    {
        if ($sub_key) {
            $section = array_get_safe($_SESSION, $key);

            if (is_array($section)) {
                // Key exists and is an array, yay!
                return array_get_safe($section, $sub_key);
            }

            if ($section === null) {
                // Key does not exist or was null, either way, nothing to return!
                return null;
            }

            // Sub must either not exist or be an array. Here its neither
            throw new OutOfBoundsException(tr('Cannot read session key ":key" sub key ":sub-key" because the session key value is not an array', [
                ':key'     => $key,
                ':sub-key' => $sub_key,
            ]));
        }

        return array_get_safe($_SESSION, $key);
    }


    /**
     * Returns the value for the given key
     *
     * @param string                $value
     * @param string|float|int      $key
     * @param string|float|int|null $sub_key
     *
     * @return void
     */
    public static function set(mixed $value, string|float|int $key, string|float|int|null $sub_key = null): void
    {
        if ($sub_key) {
            if (!array_key_exists($key, $_SESSION)) {
                $_SESSION[$key] = [];
            }

            if (!is_array($_SESSION[$key])) {
                throw new OutOfBoundsException(tr('Cannot write session key ":key" sub key ":sub-key" because session key is not an array', [
                    ':key'     => $key,
                    ':sub-key' => $sub_key,
                ]));
            }

            $_SESSION[$key][$sub_key] = $value;

            return;
        }

        $_SESSION[$key] = $value;
    }


    /**
     * Configure cookies
     *
     * @return void
     */
    protected static function configureCookies(): void
    {
        if (Request::isRequestType(EnumRequestTypes::api)) {
            // API calls do not handle cookies, sessions are done manually
            return;
        }

        if (Response::getHttpHeadersSent()) {
            // Cannot configure cookies, headers have already been sent!
            throw new SessionException(tr('Cannot startup session, HTTP headers have already been sent'));
        }

        // Check the cookie domain configuration to see if its valid.
        // NOTE: In case whitelabel domains are used, $_CONFIG[cookie][domain] must be one of "auto" or ".auto"
        switch (config()->getStringBoolean('platforms.web.sessions.cookies.domain', '.auto')) {
            case false:
                // This domain has no cookies
                break;

            case 'auto':
                config()->set('sessions.cookies.domain', Session::$domain);
                ini_set('session.cookie_domain', Session::$domain);
                break;

            case '.auto':
                config()->get('platforms.web.sessions.cookies.domain', '.' . Session::$domain);
                ini_set('session.cookie_domain', '.' . Session::$domain);
                break;

            default:
                // Test cookie domain limitation
                //
                // If the configured cookie domain is different from the current domain then all cookie will
                // inexplicably fail without warning, so this must be detected to avoid lots of hair pulling and
                // throwing arturo off the balcony incidents :)
                if (config()->getStringBoolean('platforms.web.sessions.cookies.domain')[0] == '.') {
                    $test = substr(config()->get('platforms.web.sessions.cookies.domain'), 1);

                } else {
                    $test = config()->getStringBoolean('platforms.web.sessions.cookies.domain');
                }

                if (!str_contains(Session::$domain, $test)) {
                    Notification::new()
                                ->setUrl(Url::new('reports/security/incidents.html')->makeWww())
                                ->setMode(EnumDisplayMode::warning)
                                ->setCode('configuration')
                                ->setRoles('developer')
                                ->setTitle(tr('Invalid cookie domain'))
                                ->setMessage(tr('Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain". Please fix $_CONFIG[cookie][domain]! Redirecting to ":domain"', [
                                    ':domain'         => Strings::ensureBeginsNotWith(config()->getStringBoolean('platforms.web.sessions.cookies.domain'), '.'),
                                    ':cookie_domain'  => config()->getStringBoolean('platforms.web.sessions.cookies.domain'),
                                    ':current_domain' => Session::$domain,
                                ]))
                                ->send();
                    Response::redirect(PROTOCOL . Strings::ensureBeginsNotWith(config()->getStringBoolean('platforms.web.sessions.cookies.domain'), '.'));
                }

                ini_set('session.cookie_domain', config()->getStringBoolean('platforms.web.sessions.cookies.domain'));

                unset($test);
                unset($length);
        }

        // Set session and cookie parameters
        try {
            if (config()->getBoolean('platforms.web.sessions.enabled', true)) {
                Session::initializePhpIni();
            }

        } catch (Exception $e) {
            if ($e->getCode() == 403) {
                // TODO Check if any of this is still required? we are no longer using page_show...
                Core::writeRegister(403, 'page_show');

            } else {
                if (str_starts_with(session_save_path(), '/')) {
                    if (!is_writable(session_save_path())) {
                        throw new SessionException(tr('Session startup failed because the session directory ":directory" is not writable for platform ":platform"', [
                            ':directory' => session_save_path(),
                            ':platform'  => PLATFORM,
                        ]), $e);
                    }
                }

                throw new SessionException(tr('Session startup failed, see previous exception why'), $e);
            }
        }
    }


    /**
     * Make all necessary INI settings
     *
     * @return void
     */
    public static function initializePhpIni(): void
    {
        $handler = UserSessions::getHandler();

        // Force session cookie configuration
        ini_set('session.serialize_handler', 'php_serialize');
        ini_set('session.gc_maxlifetime'   , config()->getInteger('platforms.web.sessions.timeout'            , 86400));
        ini_set('session.cookie_lifetime'  , config()->getInteger('platforms.web.sessions.cookies.lifetime'   , 0));
        ini_set('session.use_strict_mode'  , config()->getBoolean('platforms.web.sessions.cookies.strict_mode', true));
        ini_set('session.name'             , config()->getString('platforms.web.sessions.cookies.name'        , 'phoundation'));
        ini_set('session.cookie_httponly'  , config()->getBoolean('platforms.web.sessions.cookies.http-only'  , true));
        ini_set('session.cookie_secure'    , config()->getBoolean('platforms.web.sessions.cookies.secure'     , true));
        ini_set('session.cookie_samesite'  , config()->getBoolean('platforms.web.sessions.cookies.same-site'  , true));
        ini_set('session.save_handler'     , $handler);
        ini_set('session.save_path'        , Strings::force(config()->getArrayString('platforms.web.sessions.save-path', DIRECTORY_SYSTEM . 'sessions/'), ';'));

        // Sanity check
        if (ini_get('session.cookie_secure')) {
            // TODO Add checks here. IF cookie_secure is required, then we need to also be sure that all links are HTTPS and vice versa
            // TODO Add log incidents on http-only being disabled (ONCE PER new cookie, I guess?)
        }

        // Are we using memcached?
        if ($handler === 'memcached') {
            // Do we have the memcached driver loaded?
            Memcached::checkDriver();

            // Remove the memcached session prefix, we do not want or need people to know we use memcached
            ini_set('memcached.sess_prefix'       , '');
            ini_set('memcached.sess_lock_retries' , config()->getPositiveInteger('platforms.web.sessions.memcached.lock-retries' , 10));
            ini_set('memcached.sess_lock_wait_min', config()->getPositiveInteger('platforms.web.sessions.memcached.lock-wait-min', 1000));
            ini_set('memcached.sess_lock_wait_max', config()->getPositiveInteger('platforms.web.sessions.memcached.lock-wait-max', 2000));

            // Is memcached enabled?
            if (!Memcached::getEnabled()) {
                throw new SessionException(tr('Cannot use memcached session handler (Configured in configuration path "web.sessions.handler") because memcached is not enabled'));
            }
        }

        if (Debug::isEnabled() or !config()->getBoolean('cache.http.enabled', true)) {
            ini_set('session.cache_limiter', 'nocache');

        } else {
            if (config()->get('cache.http.enabled', true) === 'auto') {
                ini_set('session.cache_limiter', config()->getBoolean('cache.http.php-cache-limiter', true));
                ini_set('session.cache_expire' , config()->getBoolean('cache.http.php-cache-php-cache-expire', true));
            }
        }
    }


    /**
     * Resume an existing session
     *
     * @return bool
     */
    protected static function resume(): bool
    {
        if (PLATFORM_CLI) {
            return false;
        }

        if (!config()->get('platforms.web.sessions.enabled', true)) {
            return false;
        }

        if (Session::detectCrawler()) {
            return false;
        }

        Session::processHandler();
        Session::startForPhp();
        Session::initialize();
        Session::processCookieRefresh();
        Session::processCookieAutoSignOut();
        Session::processAutoSignOut();

        if (!Session::processEuroCookie()) {
            return false;
        }

        Session::processUrlCloaking();
        Session::updatePagesLoaded();
        Session::processSessionDomains();
        Session::processSessionIp();
        Session::processSessionFlashMessages();

        return true;
    }


    /**
     * Returns true if a crawler was detected
     *
     * @return bool
     */
    protected static function detectCrawler(): bool
    {
        if (isset_get(Core::readRegister('session', 'client')['type']) === 'crawler') {
            // Do not send cookies to crawlers!
            Log::information(ts('Detected crawler ":crawler"', [
                ':crawler' => Core::readRegister('session', 'client'),
            ]));

            return true;
        }

        return false;
    }


    /**
     * Processes the session handler, files, memcached, mysql, etc.
     *
     * @return void
     */
    protected static function processHandler(): void
    {
        switch (UserSessions::getHandler()) {
            case 'files':
                $directory = PhoDirectory::new(
                    config()->getString('platforms.web.sessions.path', DIRECTORY_SYSTEM . 'sessions/'),
                    PhoRestrictions::new([
                                             DIRECTORY_DATA,
                                             '/var/lib/php/sessions/',
                                         ], true)
                )->ensure();

                session_save_path($directory->getSource());
                break;
        }
    }

    /**
     * Starts the session for PHP
     *
     * @return void
     */
    protected static function startForPhp(): void
    {
        try {
            // Start session. Two log entries are added around it to more easily debug issues with PHP session starting
            Log::action(ts('About to start session ":session"', [
                ':session' => session_id() ?: 'new',
            ]), 3);

            session_start();
            Session::$open = true;

            Log::success(ts('Started session ":session"', [
                ':session' => session_id() ?: 'new',
            ]), 2);

        } catch (PhpException $e) {
            Session::handleSessionStartException($e);
        }

        Session::$user              = null;
        Session::$impersonated_user = null;
    }


    /**
     * Initialized the session, if needed
     *
     * @return void
     */
    protected static function initialize(): void
    {
        if (empty($_SESSION['init'])) {
            switch (Request::getRequestType()) {
                case EnumRequestTypes::api:
                    throw new UnderConstructionException();

                case EnumRequestTypes::file:
                    // FILE requests cannot ever cookies
                    break;

                case EnumRequestTypes::ajax:
                    // AJAX requests cannot create cookies automatically
                    break;

                default:
                    Session::create();
            }

        } else {
            // The session has already been initialized!
            Log::success(ts('Resumed session ":session" for user ":user" from IP ":ip"', [
                ':session' => session_id(),
                ':user'    => Session::getUsersLogId(),
                ':ip'      => Session::getIpAddress(),
            ]));
        }
    }


    /**
     * Processes session flash messages
     *
     * @return void
     */
    protected static function processSessionFlashMessages(): void
    {
        // If any flash messages were stored in the $_SESSION, import them into the flash messages object
        if (isset($_SESSION['flash_messages'])) {
            Session::getFlashMessagesObject()->import((array) $_SESSION['flash_messages']);
            unset($_SESSION['flash_messages']);
        }
    }


    /**
     * Processes the IP of the current request
     *
     * @return void
     */
    protected static function processSessionIp(): void
    {
        $_SESSION['ip'] = Session::getIpAddress();

        if ($_SESSION['ip'] !== array_get_safe($_SESSION, 'first_ip')) {
            // IP mismatch? What to do here? configurable actions!
            // TODO Implement
        }
    }


    /**
     *
     *
     * @return void
     */
    protected static function processSessionDomains(): void
    {
        if (array_get_safe($_SESSION, 'domain') !== Session::$domain) {
            // Domain mismatch? Okay if this is sub domain, but what if its a different domain? Check whitelist domains?
            // TODO Implement
        }
    }


    /**
     * Performs check related to cloaked URLs
     *
     * @return void
     */
    protected static function processUrlCloaking(): void
    {
        if (config()->getBoolean('security.url-cloaking.enabled', false) and config()->getBoolean('security.url-cloaking.strict', false)) {
            /*
             * URL cloaking was enabled and requires strict checking.
             *
             * Ensure that we have a cloaked URL users_id and that it matches the sessions users_id
             * Only check cloaking rules if we  are not displaying a system page
             */
            if (!Request::isRequestType(EnumRequestTypes::system)) {
                if (empty($core->register['url_cloak_users_id'])) {
                    throw new SessionException(tr('Failed cloaked URL strict checking, no cloaked URL users_id registered'));
                }

                if ($core->register['url_cloak_users_id'] !== array_get_safe(array_get_safe($_SESSION, 'user', []), 'id')) {
                    throw new AccessDeniedException(tr('Failed cloaked URL strict checking, cloaked URL users_id ":cloak_users_id" did not match the users_id ":session_users_id" of this session', [
                        ':session_users_id' => array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'),
                        ':cloak_users_id'   => $core->register['url_cloak_users_id'],
                    ]));
                }
            }
        }
    }


    /**
     * Processes cookies related to European countries
     *
     * @return bool
     */
    protected static function processEuroCookie(): bool
    {
        // Euro cookie check, can we do cookies at all?
        if (config()->getBoolean('platforms.web.sessions.cookies.europe', true) and !config()->getString('platforms.web.sessions.cookies.name', 'phoundation')) {
            if (GeoIp::new()->isEuropean()) {
                // All first visits to european countries require cookie permissions given!
                $_SESSION['euro_cookie'] = true;

                return false;
            }
        }

        return true;
    }


    /**
     * Processes refreshing cookies
     *
     * @return void
     */
    protected static function processCookieRefresh(): void
    {
        // Check cookie refresh
        $cookie_sign_out = config()->getPositiveInteger('platforms.web.sessions.cookies.lifetime', 3600);

        if ($cookie_sign_out) {
            // Session cookie timed out?
            if (isset($_SESSION['created']) and (($_SESSION['created'] + $cookie_sign_out) < time())) {
                // Session expired!
                Log::warning('Regenerated session id');
                session_regenerate_id(true);
            }
        }
    }


    /**
     * Processes auto sign out for cookies
     *
     * @return void
     */
    protected static function processCookieAutoSignOut(): void
    {
// TODO Implement!
//        // Check cookie refresh
//        $cookie_sign_out = config()->getPositiveInteger('platforms.web.sessions.cookies.lifetime', 0);
//
//        if ($cookie_sign_out) {
//            // Session cookie timed out?
//            if (isset($_SESSION['last_activity']) and (($_SESSION['last_activity'] + $cookie_sign_out) < time())) {
//                // Session expired!
//                Log::warning('Regenerated session id');
//                session_regenerate_id(true);
//            }
//        }
    }


    /**
     * Processes auto sign out for sessions
     *
     * @return bool
     */
    protected static function processAutoSignOut(): bool
    {
        // Update the last activity
        if (!Session::updateLastActivityTimestamp()) {
            return false;
        }

        // Pass auto sign out to the client too
        Response::addHeadDataAttribute(Session::getAutoSignOut(), 'auto-sign-out');
        return true;
    }


    /**
     * This method will inject auto-sign-out-submit-code as a global client variable in the <head> tag
     *
     * This variable will permit a single submit for only this page on auto sign out
     *
     * @param string      $selector
     * @param string|null $button_value
     * @param string      $button_name
     *
     * @return void
     */
    public static function enableAutoSignOutAutoSubmit(string $selector, ?string $button_value = null, string $button_name = 'submit-button'): void
    {
        $_SESSION['auto_sign_out_submit_selector']     = $selector;
        $_SESSION['auto_sign_out_submit_button_value'] = $button_value;
        $_SESSION['auto_sign_out_submit_button_name']  = $button_name;
        $_SESSION['auto_sign_out_submit_code']         = Strings::getUuid();
        $_SESSION['auto_sign_out_submit_file']         = Request::getTargetObject()->getSource();

        Response::addHeadDataAttribute(Session::getAutoSignOutSubmitCode()    , 'auto-sign-out-submit-code');
        Response::addHeadDataAttribute(Session::getAutoSignOutSubmitSelector(), 'auto-sign-out-submit-selector');
    }


    /**
     * Returns auto-sign-out timestamp
     *
     * @return string|null
     */
    public static function getAutoSignOutSubmitSelector(): ?string
    {
        return array_get_safe($_SESSION, 'auto_sign_out_submit_selector');
    }


    /**
     * Returns auto-sign-out timestamp
     *
     * @param string $selector
     *
     * @return void
     */
    public static function setAutoSignOutSubmitSelector(string $selector): void
    {
        $_SESSION['auto_sign_out_submit_selector'] = $selector;
    }


    /**
     * Returns integer if there are reasons why the auto sign-out test procedure should be skipped, false otherwise
     *
     * Reasons can be:
     *
     * * This page is not a standard HTML page request
     * * Auto sign out is disabled for this user session
     * * The current user for this session is guest, which cannot sign out
     * * The current page is sign-out, or sign-in
     *
     * @param bool $force
     *
     * @return false|int
     */
    protected static function getAutoSignOutConfiguration(bool $force = false): false|int
    {
        if (!Request::isRequestType(EnumRequestTypes::html) and !$force) {
            // Auto sign-out changes can only be done with standard HTTP page requests, or system/activity/notify.json!
            return false;
        }

        // TODO REDIRECT AJAX REQUESTS THROUGH SIGNOUT MESSAGE!
        // Check cookie sign-out and auto sign out
        $auto_sign_out = config()->getPositiveInteger('security.web.sessions.auto.sign-out.value', 0, true);

        // Auto sign-out only works if it is configured to a non-zero numeric value!
        if (empty($auto_sign_out)) {
            return false;
        }

        // Only auto sign-out when not guest user
        if (Session::getUserObject()->isGuest()) {
            return false;
        }

        // Auto sign-out will NOT work for sign-in or sign-out pages
        switch (Url::newCurrent()->removeAllQueries()->getSource()) {
            case Url::new('signout')->makeWww()->getSource():
                // no break

            case Url::new('signin')->makeWww()->getSource():
                return false;
        }

        return $auto_sign_out;
    }


    /**
     * Executes an automatic sign-out for this session
     *
     * @param int $auto_signout
     *
     * @return void
     */
    protected static function autoSignout(int $auto_signout): void
    {
        Log::warning(tr('Automatically signing out user ":user" because their session surpassed the auto sign-out time of ":time" seconds', [
            ':user' => Session::getUsersLogId(),
            ':time' => $auto_signout,
        ]));

        Response::getFlashMessagesObject()->addWarning(tr('You were signed out automatically because your session timed out'));
        Session::signOut(true);
        Session::setAutoSignedOut();
        Response::redirect(Url::new('signin')->makeWww()->addRedirect(Session::getPreviousPage()));
    }


    /**
     * Handle session_start() exceptions
     *
     * @param Throwable $e
     * @return void
     * @throws SessionStartFailedException
     * @todo Improve implementation. memcached failures, for example, should
     */
    protected static function handleSessionStartException(Throwable $e): void
    {
        switch (ini_get('session.save_handler')) {
            case 'files':
                throw new SessionStartFailedException(tr('Failed to start session using save handler "files" with path ":path"', [
                    ':path' => session_save_path(),
                ]), $e);

            case 'memcached':
                if ($e->messageContains('SERVER HAS FAILED AND IS DISABLED')) {
                    // Memcached server failed
                    throw new SessionStartFailedException(tr('Failed to start session using save handler "memcached" with servers ":servers" because memcached server failed. Is memcached installed and running?', [
                        ':servers' => session_save_path(),
                    ]), $e);

                } elseif($e->messageContains('Unable to clear session lock record')) {
                    // This might happen from time to time
                    throw new SessionStartFailedException(tr('Failed to start session using save handler "memcached" with servers ":servers" because memcached could not achieve a session record lock', [
                        ':servers' => session_save_path(),
                    ]), $e);
                }

                throw new SessionStartFailedException(tr('Failed to start session using save handler "memcached" with servers ":servers"', [
                    ':servers' => session_save_path(),
                ]), $e);

            default:
                throw new SessionStartFailedException(tr('Failed to start session using unknown handler ":handler"', [
                    ':handler' => ini_get('session.save_handler'),
                ]), $e);
        }
    }


    /**
     * Initializes the user for a new session
     *
     * @return void
     */
    protected static function initializeUser(): void
    {
        switch (PLATFORM) {
            case 'cli':
                Session::$user = User::newSystem();
                break;

            case 'web':
                Session::$user = match (Request::getRequestType()) {
                    EnumRequestTypes::api => User::newSystem(),
                    default               => User::newGuest(),
                };
        }
    }


    /**
     * Create a new session with basic data
     *
     * @return bool
     */
    protected static function create(): bool
    {
        // Register the session
        if (UserSession::exists(session_id())) {
            // Wut? This session has already been registered yet?
            $_session = UserSession::new(session_id());

            Incident::new()
                    ->setSeverity(EnumSeverity::high)
                    ->setType('Sessions')
                    ->setTitle(tr('Encountered duplicate session ID'))
                    ->setBody(tr('Session code ":code" has already been registered for user ":user", generating new ID', [
                        ':code' => session_id(),
                        ':user' => $_session->getUserObject()->getLogId(),
                    ]))
                    ->setNotifyRoles('developer')
                    ->addDetails([
                        'id'             => $_session->getId(),
                        'user'           => $_session->getCreatedByObject()->getLogId(),
                        'code'           => $_session->getCode(),
                        'domain'         => $_session->getDomain(),
                        'remote_ip'      => $_session->getRemoteIp(),
                        'remote_ip_real' => $_session->getRemoteIp(),
                        'session_data'   => $_session->getSource()
                    ])
                    ->setLog(9)
                    ->save();

            session_regenerate_id();
        }

        // Initialize the session for the user
        Session::initializeUser();

        Log::success(ts('Created new session ":session" for IP ":ip" with user ":user" from HTTP referrer ":referrer"', [
            ':session'  => session_id(),
            ':ip'       => Session::getIpAddress(),
            ':user'     => Session::getUsersLogId(),
            ':referrer' => array_get_safe($_SERVER, 'HTTP_REFERER'),
        ]));

        // Initialize the session
        $_SESSION['init']         = microtime(true);
        $_SESSION['first_domain'] = Session::$domain;
        $_SESSION['domain']       = Session::$domain;
        $_SESSION['first_ip']     = Session::getIpAddress();

//        $_SESSION['client']       = Core::readRegister('system', 'session', 'client');
//        $_SESSION['location']     = Core::readRegister('system', 'session', 'location');
//        $_SESSION['language']     = Core::readRegister('system', 'session', 'language');

        // Set users timezone
        if (empty($_SESSION['user']['timezone'])) {
            $_SESSION['user']['timezone'] = config()->get('timezone.display', 'UTC');

        } else {
            try {
                $_check = new DateTimeZone($_SESSION['user']['timezone']);

            } catch (Exception $e) {
                // Timezone invalid for this user. Notification developers, and fix timezone for user
                $_SESSION['user']['timezone'] = config()->get('timezone.display', 'UTC');

                Notification::new()
                            ->setException(SessionException::new(tr('Reset timezone for user ":user" to ":timezone"', [
                                ':user'     => Session::getUsersLogId(),
                                ':timezone' => $_SESSION['user']['timezone'],
                            ]), $e)->makeWarning())
                            ->send();
            }
        }

        // Detect and log client type
        Client::detect();
        return true;
    }


    /**
     * Returns the PhoLocale object for this session
     *
     * @return ?PhoLocaleInterface
     */
    public static function getLocaleObject(): ?PhoLocaleInterface
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return null;
            }
        }

        return Session::getUserObject()->getLocaleObject();
    }


    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public static function hasSomeRights(array|string $rights, ?string $always_match = 'god'): bool
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return false;
            }
        }

        return Session::getUserObject()->hasSomeRights($rights, $always_match);
    }


    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public static function hasAllRights(array|string $rights, ?string $always_match = 'god'): bool
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return false;
            }
        }

        return Session::getUserObject()->hasAllRights($rights, $always_match);
    }


    /**
     * Returns the database id for the current session user
     *
     * When the session has no user yet, NULL will be returned which, by definition, is the "system" user
     *
     * @return int|null
     */
    public static function getUsersId(): ?int
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return null;
            }
        }

        return Session::getUserObject()->getId();
    }


    /**
     * Returns the human-readable log id for the session user
     *
     * @return string|null
     */
    public static function getUsersLogId(): ?string
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return null;
            }
        }

        return Session::getUserObject()->getLogId();
    }


    /**
     * Returns the email for the session user
     *
     * @return string|null
     */
    public static function getUsersEmail(): ?string
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return null;
            }
        }

        return Session::getUserObject()->getEmail();
    }


    /**
     * Returns the display name for this object
     *
     * @return string|null
     */
    public static function getUsersDisplayName(): ?string
    {
        if (empty(Session::$user)) {
            if (empty(Session::$impersonated_user)) {
                return null;
            }
        }

        return Session::getUserObject()->getDisplayName();
    }


    /**
     * Returns the user for this session
     *
     * @note Executing this method requires that the session data has already been initialized. If this method is called before session data was initialized, a
     *       SessionNotInitializedException will be thrown
     *
     * @return UserInterface
     * @return UserInterface
     *
     * @throws SessionNotInitializedException Thrown when this method is accessed before the session itself has been initialized
     * @throws EndlessLoopException           Thrown when this method detects that it was called recursively, which SHOULD never happen
     */
    public static function getUserObject(): UserInterface
    {
        static $busy = false;

        if ($busy) {
            // This method is NOT allowed to call itself recursively as it will cause endless looping
            throw new EndlessLoopException(tr('Detected endless loop while processing Session->getUserObject()'));
        }

        $busy = true;

        if (!Session::isInitialized()) {
            if (PLATFORM_WEB) {
                // TODO Add support for session users. For now we return the system user
                if (Request::isRequestType(EnumRequestTypes::api)) {
                    $busy = false;
                    return Session::getSystemUserObject();
                }

                throw new SessionNotInitializedException(tr('Cannot access session data yet, session has not yet been initialized'));
            }

            $return = Session::getSystemUserObject();

        } elseif (!empty($_SESSION['user']['impersonate_id'])) {
            // We can return impersonated user IF exists
            // Return impersonated user
            if (empty(Session::$impersonated_user)) {
                // Load impersonated user into cache variable
                Session::$impersonated_user = Session::loadUser(array_get_safe(array_get_safe($_SESSION, 'user', []), 'impersonate_id'));
            }

            $return = Session::$impersonated_user;

        } else {
            $return = Session::getRealUserObject();
        }

        $busy = false;
        return $return;
    }


    /**
     * Reloads the user data
     *
     * @return void
     */
    public static function reloadUser(): void
    {
        if (empty($_SESSION['user']['id'])) {
            throw new SessionException(tr('Cannot reload user for session ":session", this session has no user', [
                ':session' => session_id(),
            ]));
        }

        Session::$user = Session::loadUser($_SESSION['user']['id']);
    }


    /**
     * Load the userdata into this session
     *
     * @param int $users_id
     *
     * @return UserInterface
     */
    protected static function loadUser(int $users_id): UserInterface
    {
        // Create a new user object and ensure it is still good to go
        try {
            // This user is loaded by the session object and should NOT use meta-tracking!
            return User::new()
                       ->setMetaEnabled(false)
                       ->load($users_id);

        } catch (DataEntryNotExistsException) {
            Log::warning(ts('The session user ":id" does not exist, removing session entry and dropping to guest user', [
                ':id' => array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'),
            ]));

        } catch (DataEntryStatusException $e) {
            Log::warning($e->getMessage());

        } catch (Throwable $e) {
            Log::warning(ts('Failed to fetch user ":user" for session with ":e", removing session entry and dropping to guest user', [
                ':e'    => $e->getMessage(),
                ':user' => array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'),
            ]));
        }

        // Remove user information for this session and return to guest user
        unset($_SESSION['user']['id']);

        return User::newGuest();
    }


    /**
     * Authenticate a user with the specified password
     *
     * @param string      $user
     * @param string      $password
     * @param string      $user_class
     * @param string|null $domain
     *
     * @return UserInterface
     */
    public static function signIn(string $user, string $password, string $user_class = User::class, ?string $domain = null): UserInterface
    {
        try {
            if (!Csrf::getConfigEnabled()) {
                // CSRF generally should be turned on, it is a bad idea to have it off!
                if (Core::isProductionEnvironment()) {
                    if (Csrf::getConfigIncidentEnabled()) {
                        // CSRF is off on production environment, this is a bad idea!
                        Incident::new()
                                ->setSeverity(EnumSeverity::high)
                                ->setType('security')
                                ->setTitle(ts('CSRF is disabled on production'))
                                ->setBody(ts('The CSRF protection is disabled on production. This is a security risk and should be enabled immediately'))
                                ->setNotifyRoles('security')
                                ->setLog(9)
                                ->save();
                    }

                } else {
                    // All other environments just get a log warning
                    Log::warning(ts('CSRF is disabled on environment ":environment". While this is generally considered a bad bad idea, but may be needed on development or trial type environments. Please make sure your system is safe enough without CSRF!', [
                        ':environment' => ENVIRONMENT
                    ]));
                }
            }

            $_user = $user_class::authenticate(['email' => $user], $password, EnumAuthenticationAction::signin)
                                ->authenticateDomain(EnumAuthenticationAction::signin, $domain);

            return Session::signInWithUserObject($_user);

        } catch (DataEntryNotExistsException $e) {
            if ($e->getDataKey('class')) {
                switch (Strings::fromReverse($e->getDataKey('class'), '\\')) {
                    case '':
                        // no break

                    case 'User':
                        Incident::new()
                                ->setType('User does not exist')
                                ->setSeverity(EnumSeverity::low)
                                ->setTitle(tr('Cannot sign in user ":user", the user does not exist', [
                                    ':user' => $user
                                ]))
                                ->setDetails([
                                    'user'               => $user,
                                    'remote_ip'          => Session::getIpAddress(),
                                    'original_remote_ip' => Session::getOriginalIpAddress()
                                ])
                                ->setNotifyRoles('security')
                                ->save()
                                ->throw(AuthenticationException::class);
                }
            }

            throw $e;
        }
    }


    /**
     * Redirects the user after they have signed in
     *
     * @param string|null $redirect
     * @param string|null $email
     *
     * @return void
     */
    #[NoReturn] public static function redirectAfterSignIn(string|null $redirect, ?string $email = null): void
    {
        // Try to redirect the user back where they were. If the email is different (new, different user) do not do
        // this as we would redirect the new user to what the previous user was doing
        if (empty($email) or (Session::getUsersEmail() === $email)) {

            // If auto-signed-out, check if it has been more than 12 hours (in which case do not redirect back to previous page)
            if (Session::getAutoSignedOut()) {
                $auto_sign_out_time      = PhoDateTime::new(Session::getAutoSignedOut());
                $auto_sign_out_hours_ago = $auto_sign_out_time->diff(PhoDateTime::new())->getTotalHours();

                // If auto-sign-out was more than 12 hours ago, redirect back to default page
                if ($auto_sign_out_hours_ago >= config()->getPositiveInteger('medinet.billing.restore-page.timeout.hours', 12)) {
                    $default_page = Session::getUserObject()->getConfigurationsObject()->get('default_page');
                    if ($default_page) {
                        Response::redirect($default_page);

                    } else {
                        Response::redirect(Url::new('index')->makeWww());
                    }
                }
            }

            // First try the specified URL (likely from GET)
            $redirect_urls[] = $redirect;

            // Next try the URL from session
            $redirect_urls[] = Session::getPreviousPage();

            // Next try the accounts_users "previous_page" column
//            $redirect_urls[] = Session::getUserObject()->getPreviousPage();

            // Next try the user default page
            $redirect_urls[] = Session::getUserObject()->getConfigurationsObject()->get('default_page');

            foreach ($redirect_urls as $redirect_url) {
                $redirect_url = Url::filter($redirect_url, ['sign-out', 'sign-in', 'auto-sign-out']);

                if ($redirect_url) {
                    $redirect_url = Url::newRedirect($redirect_url);
                    Response::redirect($redirect_url);
                }
            }
        }

        // GET email did not match or was not specified, redirect the default page for this user
        Response::redirect(Url::new('index')->makeWww());
    }


    /**
     * Authenticate a user with the specified password
     *
     * @param UserInterface $_user
     *
     * @return UserInterface
     */
    public static function signInWithUserObject(UserInterface $_user): UserInterface
    {
        try {
            // Update the users sign-in and last sign-in information
            Session::$user         = $_user;
            Session::$user_changed = true;
            $auto_sign_out_time   = Session::getAutoSignedOut();

            Session::clear();
            Session::updateSignInTracking();
            Session::clearSignInKey();
            Session::updateLastActivityTimestamp();

            session_regenerate_id();

            // Open a new user session
            UserSession::new()
                       ->setUserObject($_user)
                       ->setOpened(PhoDateTime::new())
                       ->setCode(Session::getId())
                       ->setDomain(Request::getDomain())
                       ->setRemoteIp(Request::getRemoteIpAddress())
                       ->setRemoteIpReal(Request::getRemoteIpAddressReal())
                       ->save();

            if ($auto_sign_out_time) {
                Session::setAutoSignedOut($auto_sign_out_time);
            }

            Incident::new()
                    ->setType(tr('User sign in'))
                    ->setSeverity(EnumSeverity::notice)
                    ->setTitle(tr('The user ":user" signed in', [':user' => Session::$user->getLogId()]))
                    ->setDetails(['user' => Session::$user->getLogId()])
                    ->setLog(8)
                    ->save();

            $_SESSION['first_visit'] = 1;
            $_SESSION['user']['id']  = Session::$user->getId();

            return Session::$user;

        } catch (DataEntryNotExistsException $e) {
            if ($e->getDataKey('class')) {
                switch (Strings::fromReverse($e->getDataKey('class'), '\\')) {
                    case '':
                        // no break
                    case 'User':
                        Incident::new()
                                ->setType('User does not exist')
                                ->setSeverity(EnumSeverity::low)
                                ->setTitle(tr('Cannot sign in user ":user", the user does not exist', [
                                    ':user' => $_user
                                ]))
                                ->setDetails([
                                    'user'               => $_user,
                                    'remote_ip'          => Session::getIpAddress(),
                                    'original_remote_ip' => Session::getOriginalIpAddress()
                                ])
                                ->setNotifyRoles('security')
                                ->save()
                                ->throw(AuthenticationException::class);
                }
            }

            throw $e;
        }
    }


    /**
     * Clear the current session
     *
     * @return int|null
     */
    public static function clear(): ?int
    {
        global $_SESSION;

        $users_id = array_get_safe(array_get_safe($_SESSION, 'user', []), 'id');

        if (isset($_SESSION['init'])) {
            // Conserve init data and flash messages
            $messages = array_get_safe($_SESSION, 'flash_messages');
            $display  = array_get_safe($_SESSION, 'display');

            $_SESSION = [
                'domain'          => array_get_safe($_SESSION, 'domain'),
                'init'            => array_get_safe($_SESSION, 'init'),
                'first_ip'        => array_get_safe($_SESSION, 'first_ip'),
                'first_domain'    => array_get_safe($_SESSION, 'first_domain'),
                'previous_page'   => array_get_safe($_SESSION, 'previous_page'),
            ];

            if ($messages) {
                $_SESSION['flash_messages'] = $messages;
            }

            if ($display) {
                $_SESSION['display'] = $display;
            }

        } else {
            $_SESSION = [];
        }

        return $users_id;
    }


    /**
     * Updates the sign in tracking information for this user
     *
     * This method will reset the last_sign_in value for this user to NOW and increase the sign_in_count by one
     *
     * @return void
     */
    protected static function updateSignInTracking(): void
    {
        sql()->query('UPDATE `accounts_users`
                      SET    `last_sign_in`  = NOW(), 
                             `sign_in_count` = `sign_in_count` + 1
                      WHERE  `id`            = :id', [
            ':id' => Session::$user->getId(),
        ]);
    }


    /**
     * Shut down the session object
     *
     * @return void
     */
    public static function exit(): void
    {
        if (PLATFORM_WEB) {
            if (Session::$sign_out_on_exit) {
                Session::autoSignOut(Session::$sign_out_on_exit);
            }

            // If this page has flash messages that have not yet been displayed, then store them in the session variable so that they can be displayed on the
            // next page load
            Session::getFlashMessagesObject()->addSource(Response::getFlashMessagesObject());

            if (Session::$flash_messages?->getCount()) {
                // There are flash messages in this session static object, export them to $_SESSIONS for the next page load
                $_SESSION['flash_messages'] = Session::$flash_messages->export();
            }
        }

        Session::setPreviousPage();
        Session::release();
    }


    /**
     * Returns the user for this session
     *
     * @return UserInterface
     */
    public static function getRealUserObject(): UserInterface
    {
        // Return the real user
        if (empty(Session::$user)) {
            // User object does not yet exist
            if (array_get_safe(array_get_safe($_SESSION, 'user', []), 'id')) {
                Session::$user = Session::loadUser($_SESSION['user']['id']);

            } else {
                if (PLATFORM_WEB) {
                    // There is no user, this is a guest session
                    Session::$user = User::newGuest();

                } else {
                    // There is no user, this is a system session
                    Session::$user = User::newSystem();
                }
            }
        }

        // Return from cache
        return Session::$user;
    }


    /**
     * Returns the user for this session
     *
     * @return UserInterface
     */
    protected static function getSystemUserObject(): UserInterface
    {
        // Return the system user
        if (Session::$user) {
            if (!Session::$user->isSystem()) {
                Session::$user = null;
            }
        }

        if (empty(Session::$user)) {
            // There is no user, this is a system session
            Session::$user = User::newSystem();
        }

        // Return from cache
        return Session::$user;
    }


    /**
     * Validate sign in data
     *
     * @param ValidatorInterface|null $_validator
     *
     * @return array
     */
    public static function validateSignIn(?ValidatorInterface $_validator = null): array
    {
        $_validator = $_validator ?? PostValidator::new();

        return $_validator->select('email')->isEmail()
                         ->select('password')->isPassword()
                         ->validate();
    }


    /**
     * Validate sign up data
     *
     * @param ValidatorInterface|null $_validator
     *
     * @return array
     */
    public static function validateSignUp(?ValidatorInterface $_validator = null): array
    {
        if (!$_validator) {
            $_validator = PostValidator::new();
        }

        return $_validator->select('email')
                         ->isEmail()
                         ->select('password')
                         ->isPassword()
                         ->validate();
    }


    /**
     * Returns the language for this session
     *
     * @param string $default
     *
     * @return string
     */
    public static function getLanguage(string $default = 'en'): string
    {
        if (empty(Session::$language)) {
            Session::setLanguage();
        }

        return Session::$language ?? $default;
    }


    /**
     * Returns the language for this session
     *
     * @return string
     */
    protected static function setLanguage(): string
    {
        // Check what languages are accepted by the client (in order of importance) and see if we support any of those
        $supported_languages = Arrays::force(config()->get('locale.languages.supported', []));
        $requested_languages = Request::acceptsLanguages();

        foreach ($requested_languages as $requested_language) {
            if (in_array($requested_language['language'], $supported_languages, true)) {
                Session::$language = $requested_language['language'];

                return Session::$language;
            }
        }

        // No supported language found, set the default language
        return config()->getString('locale.languages.default', 'en');
    }


    /**
     * Returns true if the specified sign in method is supported
     *
     * @param string $method
     *
     * @return bool
     */
    public static function supports(string $method): bool
    {
        // TODO Implement
        switch ($method) {
            case 'github':
                // no break

            case 'email':
                // no break

            case 'copyright':
                // no break

            case 'lost-password':
                return true;

            case 'facebook':
                // no break

            case 'google':
                // no break

            case 'signup':
                // no break

            case 'sign-up':
                // no break

            case 'register':
                // no break

            case 'registration':
                return false;

            default:
                throw new OutOfBoundsException(tr('Unknown Session support ":method" specified', [
                    ':method' => $method,
                ]));
        }
    }


    /**
     * Returns true if this session is impersonated
     *
     * @return bool
     */
    public static function isImpersonated(): bool
    {
        return isset($_SESSION['user']['impersonate_id']);
    }


    /**
     * Update this session so that it impersonates this person
     *
     * @param User $user
     *
     * @return void
     */
    public static function impersonate(User $user): void
    {
        // Just an extra check, this SHOULD never happen
        if (!$user->getEmail()) {
            throw new OutOfBoundsException(tr('Cannot impersonate user ":user", it has no email address', [
                ':user' => $user->getLogId(),
            ]));
        }

        if (isset($_SESSION['user']['impersonate_id'])) {
            // We are already impersonating a user!
            Authentication::new()
                          ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                          ->setStatus('cannot-impersonate-double')
                          ->save();

            Incident::new()
                    ->setType('User impersonation')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle('User impersonation failed')
                    ->setBody(tr('Cannot impersonate user ":user", we are already impersonating', [
                        ':user' => $user->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => Session::getUsersLogId(),
                        'impersonating'       => User::new()->load($_SESSION['user']['impersonate_id'])->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw();
        }

        if (!$user->canBeImpersonated()) {
            // Impersonation  is not allowed
            Authentication::new()
                          ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                          ->setStatus('impersonation-not-allowed')
                          ->save();

            Incident::new()
                    ->setType('User impersonation')
                    ->setTitle('User impersonation failed')
                    ->setSeverity(EnumSeverity::high)
                    ->setBody(tr('Cannot impersonate user ":user", this user account is not able or allowed to be impersonated', [
                        ':user' => Session::getUsersLogId(),
                    ]))
                    ->setDetails([
                        'user'                => Session::getUsersLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw();
        }

        if ($user->getId() === Session::getUsersId()) {
            // We cannot impersonate self!
            Authentication::new()
                          ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                          ->setStatus('cannot-impersonate-self')
                          ->save();

            Incident::new()
                    ->setType('User impersonation')
                    ->setTitle('User impersonation failed')
                    ->setSeverity(EnumSeverity::high)
                    ->setBody(tr('Cannot impersonate user ":user", the user to impersonate is this user itself', [
                        ':user' => Session::getUsersLogId(),
                    ]))
                    ->setDetails([
                        'user'                => Session::getUsersLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw();
        }

        if ($user->hasAllRights('god')) {
            // Cannot impersonate a god level user!
            Authentication::new()
                          ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                          ->setStatus('cannot-impersonate-god')
                          ->save();

            Incident::new()
                    ->setType('User impersonation')
                    ->setTitle('User impersonation failed')
                    ->setSeverity(EnumSeverity::severe)
                    ->setBody(tr('Cannot impersonate user ":user", the user to impersonate has the "god" role', [
                        ':user' => Session::getUsersLogId(),
                    ]))
                    ->setDetails([
                        'user'                => Session::getUsersLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw();
        }

        // Impersonate the user
        $original_user = Session::getUserObject();
        $_SESSION['user']['impersonate_id']  = $user->getId();
        $_SESSION['user']['impersonate_url'] = (string) Url::newCurrent();

        Session::$user_changed = true;

        Authentication::new()
                      ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                      ->setAction(EnumAuthenticationAction::startimpersonation)
                      ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                      ->save();

        // Register an incident
        Incident::new()
                ->setType('User impersonation')
                ->setSeverity(EnumSeverity::medium)
                ->setTitle('User impersonation started')
                ->setBody(tr('The user ":user" started impersonating user ":impersonate"', [
                    ':user'        => $original_user->getLogId(),
                    ':impersonate' => $user->getLogId(),
                ]))
                ->setDetails([
                    'user'        => $original_user->getLogId(),
                    'impersonate' => $user->getLogId(),
                ])
                ->setNotifyRoles('security')
                ->save();

        // Notify the target user
        Notification::new()
                    ->setUrl(Url::new('profiles/profile+' . $original_user->getId() . '.html')->makeWww())
                    ->setMode(EnumDisplayMode::warning)
                    ->setUsersId(array_get_safe(array_get_safe($_SESSION, 'user', []), 'impersonate_id'))
                    ->setTitle(tr('Your account was impersonated'))
                    ->setMessage(tr('Your account was impersonated by the user ":user". For questions or more information about this, please contact the user', [
                        ':user' => $original_user->getLogId(),
                    ]))
                    ->send();
    }


    /**
     * Returns the session store
     *
     * @return array
     */
    public static function getSource(): array
    {
        return $_SESSION;
    }


    /**
     * Clears the sign-key from the session
     *
     * @return void
     */
    public static function clearSignKey(): void
    {
        Session::$key  = null;
        unset($_SESSION['sign-key']);

        Session::setRedirect();
    }


    /**
     * Create a new session with basic data from the specified sign in key
     *
     * @param SignInKeyInterface $key
     *
     * @return UserInterface
     */
    public static function setSignKey(SignInKeyInterface $key): UserInterface
    {
        Session::$key  = $key;
        Session::$user = $key->getUserObject();

        Session::clear();

        // Update the users sign-in and last sign-in information
        Session::updateSignInTracking();

        Incident::new()
                ->setType(tr('User sign in'))
                ->setSeverity(EnumSeverity::notice)
                ->setTitle(tr('The user ":user" signed in using UUID key ":key"', [
                    ':key'  => $key->getUuid(),
                    ':user' => Session::$user->getLogId(),
                ]))
                ->setDetails([
                    ':key' => $key->getUuid(),
                    'user' => Session::$user->getLogId(),
                ])
                ->save();

        $_SESSION['user']['id'] = Session::$user->getId();
        $_SESSION['sign-key']   = $key->getUuid();

        return Session::$user;
    }


    public static function getUUID(): string
    {
        if (empty($_SESSION['uuid'])) {
            $_SESSION['uuid'] = Strings::getUuid();
        }

        return $_SESSION['uuid'];
    }


    /**
     * Returns the session sign in key, or NULL if not available
     *
     * @return SignInKeyInterface|null
     */
    public static function getSignInKey(): ?SignInKeyInterface
    {
        if (empty(Session::$key)) {
            if (isset($_SESSION['sign-key'])) {
                try {
                    Session::$key = SignInKey::new()->load(['uuid' => $_SESSION['sign-key']]);

                } catch (DataEntryNotExistsException) {
                    // This session key does not exist, WTF? If it exists in session, it should exist in the DB. Since it
                    // does not exist, assume the session contains invalid data. Drop the session
                    Incident::new()
                            ->setType(tr('Invalid session data'))
                            ->setSeverity(EnumSeverity::medium)
                            ->setTitle(tr('Session has sign-key that does not exist, session will be dropped'))
                            ->setDetails([
                                'sign-key' => $_SESSION['sign-key'],
                                'users_id' => Session::getUserObject()
                                                     ->getLogId(),
                            ])
                            ->save();

                    Response::signOut(function () {
                        Response::getFlashMessagesObject()
                                ->addWarning(tr('Something went wrong with your session, please sign in again'));
                    });
                }
            }
        }

        return Session::$key;
    }


    /**
     * This method will clear the users sign in key
     *
     * @return void
     */
    public static function clearSignInKey(): void
    {
        unset($_SESSION['sign-key']);
        Session::$key = null;
    }


    /**
     * Destroy the current user session
     *
     * @param bool $hard If true will sign out impersonated sessions completely
     *
     * @return UserInterface|null
     */
    public static function signOut(bool $hard = false): ?UserInterface
    {
        if (!session_id()) {
            Incident::new()
                    ->setType('User session')
                    ->setSeverity(EnumSeverity::medium)
                    ->setTitle(tr('User sign out requested on non existing session'))
                    ->save();

            return null;
        }

        // Always unset session redirects
        unset($_SESSION['redirect']);

        try {
            if (isset($_SESSION['user']['impersonate_id'])) {
                // This session was impersonation a user. Do not sign out, stop impersonating
                try {
                    // We are impersonating a user, return to the original user.
                    $url            = array_get_safe($_SESSION['user'], 'impersonate_url');
                    $users_id       = array_get_safe($_SESSION['user'], 'id');
                    $impersonate_id = array_get_safe($_SESSION['user'], 'impersonate_id');

                    unset($_SESSION['user']['impersonate_id']);
                    unset($_SESSION['user']['impersonate_url']);

                    Session::$user_changed = true;

                    Authentication::new()
                                  ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                                  ->setAction(EnumAuthenticationAction::stopimpersonation)
                                  ->setCreatedBy($users_id)
                                  ->save();

                    Incident::new()
                            ->setType('User impersonation')
                            ->setSeverity(EnumSeverity::low)
                            ->setTitle('User impersonation stopped')
                            ->setBody(tr('The user ":user" stopped impersonating user ":impersonate"', [
                                ':user'        => User::new()->load($users_id)->getLogId(),
                                ':impersonate' => User::new()->load($impersonate_id)->getLogId(),
                            ]))
                            ->setDetails([
                                'user'        => User::new()->load($users_id)->getLogId(),
                                'impersonate' => User::new()->load($impersonate_id)->getLogId(),
                            ])
                            ->setNotifyRoles('security')
                            ->save();

                    Response::getFlashMessagesObject()
                            ->addSuccess(tr('You have stopped impersonating user ":user"', [
                                ':user' => User::new()->load($users_id)->getLogId(),
                            ]));

                    if (!$hard) {
                        Response::redirect($url);
                    }

                } catch (Throwable $e) {
                    // Oops?
                    Log::error($e);

                    Notification::new()
                                ->setException($e)
                                ->save();

                    Authentication::new()
                                  ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                                  ->setAction(EnumAuthenticationAction::stopimpersonation)
                                  ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                                  ->setStatus('failed')
                                  ->save();

                    Incident::new()
                            ->setType('User impersonation')
                            ->setSeverity(EnumSeverity::low)
                            ->setTitle(tr('User impersonation failed'))
                            ->setBody(tr('User impersonation sign out failed users id ":id", impersonate id ":impersonate_id", closing sessions', [
                                ':id'             => array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'),
                                ':impersonate_id' => array_get_safe(array_get_safe($_SESSION, 'user', []), 'impersonate_id'),
                            ]))
                            ->save();
                }
            }

            Authentication::new()
                          ->setAccount(Json::encode(['email' => Session::getUsersEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::signout)
                          ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                          ->save();

            Incident::new()
                    ->setType('User session')
                    ->setSeverity(EnumSeverity::notice)
                    ->setTitle(tr('The user ":user" signed out', [
                        ':user' => Session::getUsersLogId(),
                    ]))
                    ->setDetails([
                        'user' => Session::getUsersLogId(),
                    ])
                    ->save();

        } catch (Throwable $e) {
            // Oops! Session sign out just completely failed for some reason. Just log, destroy the session, and continue
            Log::error($e);

            Authentication::new()
                          ->setAction(EnumAuthenticationAction::signout)
                          ->setCreatedBy(array_get_safe(array_get_safe($_SESSION, 'user', []), 'id'))
                          ->setStatus('failed')
                          ->save();

            Incident::new()
                    ->setType('User session')
                    ->setSeverity(EnumSeverity::notice)
                    ->setTitle(tr('The sign out of user ":user" failed', [
                        ':user' => Session::getUsersLogId(),
                    ]))
                    ->setDetails([
                        'user' => Session::getUsersLogId(),
                    ])
                    ->save()
                    ->setNotifyRoles('developer');
        }

        // Close the session
        UserSession::new()->loadNullOrNull(Session::getId())?->close();

        // Attempt sign-out
        Session::$user_changed = !Session::getUserObject()->isGuest();

        // Destroy all in the session but the flash messages
        // Return the user that signed out
        return new User(Session::clear());
    }


    /**
     * Returns true if the session has been initialized, with user information available
     *
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return !empty(session_id());
    }


    /**
     * Returns true if the current session has a guest user
     *
     * @return bool
     */
    public static function isGuest(): bool
    {
        if (Session::isInitialized()) {
            return Session::getUserObject()->isGuest();
        }

        // Session has not yet initialized, always assume guest / system user
        return true;
    }


    /**
     * Returns true if the current session has a registered user
     *
     * @return bool
     */
    public static function isUser(): bool
    {
        return !Session::isGuest();
    }


    /**
     * Returns true if the current session user is the same as the specified user
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public static function iSpecificUser(UserInterface $user): bool
    {
        return Session::getUserObject()->getId(false) === $user->getId(false);
    }


    /**
     * Releases the lock on this session so that other requests may pass without blocking
     *
     * @note This will write session data to the datastore and then closes the session. any updates to $_SESSION will no
     *       longer be recorded for future page loads!
     *
     * @return void
     */
    public static function release(): void
    {
        if (Session::$open) {
            session_write_close();
            Session::$open = false;
        }
    }


    /**
     * Returns a MultiFactorAuthenticationInterface for the user of this session
     *
     * @return MultiFactorAuthenticationInterface
     */
    public static function getMultiFactorAuthenticationObject(): MultiFactorAuthenticationInterface
    {
        return Session::getUserObject()->getMultiFactorAuthenticationObject();
    }


    /**
     * Returns the UserSession object for this session
     *
     * @return UserSessionInterface
     */
    public static function getUserSession(): UserSessionInterface
    {
        if (empty(Session::$_user_session)) {
            Session::$_user_session = new UserSession(Session::getId());
        }

        return Session::$_user_session;
    }


    /**
     * Updated the pages_loaded_this_session variable for this Session
     *
     * @return void
     */
    protected static function updatePagesLoaded(): void
    {
        if (empty($_SESSION['pages_loaded_this_session'])) {
            $_SESSION['pages_loaded_this_session'] = 1;

        } else {
            $_SESSION['pages_loaded_this_session']++;
        }
    }


    /**
     * Returns the number of pages loaded for this session (including this page)
     *
     * @return int
     */
    public static function getPagesLoadedForThisSession(): int
    {
        return array_get_safe($_SESSION, 'pages_loaded_this_session', 0);
    }


    /**
     * Returns true if this page load is the first for this session after signing in
     *
     * @return bool
     */
    public static function isFirstPage(): bool
    {
        return array_get_safe($_SESSION, 'pages_loaded_this_session', 0) === 1;
    }


    /**
     * Returns last-activity timestamp
     *
     * @return float|null
     */
    public static function getLastActivityTimestamp(): ?float
    {
        return Session::get('last_activity');
    }


    /**
     * Updates last-activity timestamp to the number of seconds ago
     *
     * @param int  $seconds
     * @param bool $force
     *
     * @return bool
     */
    public static function updateLastActivityTimestamp(int $seconds = 0, bool $force = false): bool
    {
        $auto_sign_out = Session::getAutoSignOutConfiguration($force);

        if (empty($auto_sign_out)) {
            return false;
        }

        // Pass auto-sign-out to client too
        Response::addHeadDataAttribute(Session::getLastActivityTimestamp(), 'sign-out');

        // Only auto sign-out when last_activity timed out
        Session::tryAutoSignout($auto_sign_out);

        // Update last activity and auto_sign_out values
        $_SESSION['last_activity'] = time() - $seconds;
        $_SESSION['auto_sign_out'] = $_SESSION['last_activity'] + $auto_sign_out;

        return true;
    }


    /**
     * Clears the post and submit data from the session
     *
     * @return void
     */
    protected static function clearAutoSignoutSubmit(): void
    {
        unset($_SESSION['auto_sign_out_submit_button_value']);
        unset($_SESSION['auto_sign_out_submit_button_name']);
        unset($_SESSION['auto_sign_out_submit_selector']);
        unset($_SESSION['auto_sign_out_submit_code']);
        unset($_SESSION['auto_sign_out_submit_file']);
    }


    /**
     * Attempts to execute an auto sign out
     *
     * @param int $auto_sign_out
     *
     * @return void
     */
    protected static function tryAutoSignout(int $auto_sign_out): void
    {
        // Only auto sign-out when not guest user
        if (!Session::getUserObject()->isGuest()) {
            // Only sign-out if we are not on the sign-out page!
            if (Url::newCurrent()->removeAllQueries()->getSource() !== Url::new('signout')->makeWww()->getSource()) {
                // Only auto sign-out when last_activity timed out
                if (isset($_SESSION['last_activity']) and (($_SESSION['last_activity'] + $auto_sign_out) < microtime(true))) {
                    $_SESSION['last_activity'] = microtime(true);

                    // If the page request is POST, it MIGHT be post-and-sign-out!
                    if (Request::isRequestMethod(EnumHttpRequestMethod::post)) {
                        $post = PostValidator::new()
                                             ->select('__auto_sign_out_submit_code')->isOptional()->isCode()
                                             ->validate(false);

                        if (array_get_safe($post, '__auto_sign_out_submit_code')) {
                            Session::autoSubmit($auto_sign_out, $post);
                            return;
                        }
                    }

                    // Execute the auto sign out
                    Session::autoSignout($auto_sign_out);
                }
            }
        }

        if (Request::isRequestType(EnumRequestTypes::html)) {
            // Normal page loads clear the auto sign-out code to ensure it will not be abused by other pages
            Session::clearAutoSignoutSubmit();
        }
    }


    /**
     * Attempts to setup the session to permit an auto submit
     *
     * @param int|null $auto_sign_out
     * @param array    $post
     *
     * @return void
     */
    protected static function autoSubmit(?int $auto_sign_out, array $post): void
    {
        // This is an attempt at post-and-sign-out!
        if ($post['__auto_sign_out_submit_code'] !== array_get_safe($_SESSION, 'auto_sign_out_submit_code')) {
            throw SessionPostAndSignoutException::new(ts('Cannot perform post-and-sign-out, the client specified auto_sign_out_submit_code ":code" is not authorized', [
                ':code' => $post['__auto_sign_out_submit_code'],
            ]))->addData([
                'session auto_sign_out_submit_code'  => array_get_safe($_SESSION, 'auto_sign_out_submit_code'),
                'client __auto_sign_out_submit_code' => $post['__auto_sign_out_submit_code'],
            ]);
        }

        if (array_get_safe($_SESSION, 'auto_sign_out_submit_file') !== Request::getTargetObject()->getSource()) {
            throw SessionPostAndSignoutException::new(ts('Cannot perform post-and-sign-out on page ":page", the client specified auto_sign_out_submit_code ":code" is only authorized on page ":authorized"', [
                ':code'       => $post['__auto_sign_out_submit_code'],
                ':page'       => Request::getTargetObject()->getRootname(),
                ':authorized' => $_SESSION['auto_sign_out_submit_file'],
            ]))->addData([
                'session_auto_sign_out_submit_file' => array_get_safe($_SESSION, 'auto_sign_out_submit_file'),
                'current_file'                      => Request::getTargetObject()->getSource(),
            ]);
        }

        Log::warning(ts('Allowing auto submit on auto sign out with code ":code"', [
            ':code' => $post['__auto_sign_out_submit_code'],
        ]));

        // If a submit button was specified, then setup PostValidator for this.
        if (array_get_safe($_SESSION, 'auto_sign_out_submit_button_value')) {
            PostValidator::new()->set(Session::get('auto_sign_out_submit_button_value'), Session::get('auto_sign_out_submit_button_name'));
        }

        // Yay, we are cleared for submission! Clear the submit-and-signout codes so they will not be used again
        Session::clearAutoSignoutSubmit();
        Session::setSignOutOnExit($auto_sign_out);
    }


    /**
     * Returns auto-sign-out timestamp
     *
     * @return string|null
     */
    public static function getAutoSignOutSubmitCode(): ?string
    {
        return array_get_safe($_SESSION, 'auto_sign_out_submit_code');
    }


    /**
     * Returns auto-sign-out timestamp
     *
     * @return float|null
     */
    public static function getAutoSignOutTimestamp(): ?float
    {
        return array_get_safe($_SESSION, 'auto_sign_out');
    }


    /**
     * Returns auto-sign-out timestamp
     *
     * @return float|null
     */
    public static function getAutoSignOutTimeLeft(): ?float
    {
        return Session::getAutoSignOutTimestamp() - time();
    }


    /**
     * Returns true if this page load is the first for this session after signing in
     *
     * @return bool
     */
    public static function getAutoShowMenu(): bool
    {
        return config()->getBoolean('platforms.web.interface.user.menu.open', false, true) and Session::isFirstPage();
    }


    /**
     * Returns the auto sign out value for this user, in seconds, if available, null otherwise
     *
     * @return int|null
     */
    public static function getAutoSignOut(): ?int
    {
        return config()->getInteger('security.web.sessions.auto.sign-out.value', null, true);
    }


    /**
     * Returns a default page if this is the first page after signing in
     *
     * @return ?string
     */
    public static function getDefaultPage(): ?string
    {
        if (Session::isFirstPage()) {
            $page = config()->getString('platforms.web.pages.default', '', true);

            if ($page) {
                return $page;
            }
        }

        return null;
    }


    /**
     * Returns the display mode for this session
     *
     * @return string
     */
    public static function getDisplayMode(): string
    {
        return Session::get('display', 'display_mode') ?? config()->getString('platforms.web.display.mode', 'light', true);
    }


    /**
     * Returns the compact mode for this session
     *
     * @return bool
     */
    public static function getCompactMode(): bool
    {
        return Session::get('display', 'compact_mode') ?? config()->getBoolean('platforms.web.display.compact', false, true);
    }


    /**
     * Returns if the session should sign out when Session::exit() is called
     *
     * @return int|null
     */
    public static function getSignOutOnExit(): ?int
    {
        return Session::$sign_out_on_exit;
    }


    /**
     * Sets if the session should sign out when Session::exit() is called
     *
     * @param int|null $sign_out_on_exit
     *
     * @return void
     */
    public static function setSignOutOnExit(?int $sign_out_on_exit): void
    {
        Session::$sign_out_on_exit = $sign_out_on_exit;
    }


    /**
     * Returns the value stored in the "previous_page" key
     *
     * @return string|null
     */
    public static function getPreviousPage(): ?string
    {
        return Session::get('previous_page');
    }


    /**
     * Sets the value for the "previous_page" key
     *
     * @param UrlInterface|string|null $url
     *
     * @return void
     */
    protected static function setPreviousPage(UrlInterface|string|null $url = null): void
    {
        $url = $url ?? Request::getUrl();

        if (is_string($url)) {
            $url = Url::new($url);
        }

        if (!Request::isRequestType(EnumRequestTypes::html)) {
            return;
        }

        // Check if this is from the sign-in/sign-out page. If so, do not reset the session previous page
        if (empty(Url::filter($url, ['sign-out', 'sign-in', 'auto-sign-out']))) {
            return;
        }

        Session::set($url->getSource(), 'previous_page');
    }


    /**
     * Returns the auto sign out value for this user, in seconds, if available, null otherwise
     *
     * @return ?int
     */
    public static function getAutoSignedOut(): ?int
    {
        return array_get_safe($_SESSION, 'auto_signed_out');
    }


    /**
     * Sets the auto sign out value for this user, in seconds, if available, null otherwise
     *
     * @param int|null $time
     *
     * @return void
     */
    protected static function setAutoSignedOut(?int $time = null): void
    {
        if ($time) {
            $_SESSION['auto_signed_out'] = $time;

        } else {
            $_SESSION['auto_signed_out'] = time();
        }
    }


    /**
     * Returns session State object
     *
     * @return SessionStateInterface
     */
    public static function getStateObject(): SessionStateInterface
    {
        return Session::getUserObject()->getSessionStateObject();
    }
}
