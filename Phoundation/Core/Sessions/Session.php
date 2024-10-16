<?php

/**
 * Class Session
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Sessions;

use DateTimeZone;
use Exception;
use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Users\Authentication;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Accounts\Users\Interfaces\SignInKeyInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\SignInKey;
use Phoundation\Accounts\Users\SystemUser;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\SessionException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Interfaces\SessionConfigInterface;
use Phoundation\Core\Sessions\Interfaces\SessionInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryStatusException;
use Phoundation\Data\Traits\TraitDataStaticFlashMessages;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Client;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Throwable;


class Session implements SessionInterface
{
    use TraitDataStaticFlashMessages;


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
     * The user session configuration object
     *
     * @var SessionConfigInterface $config
     */
    protected static SessionConfigInterface $config;

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
     * Singleton, ensure to always return the same Log object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
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
            // We're on command line interface, there is no IP!
            return null;
        }

        if (empty(static::$ip_address)) {
            // Correctly detect the remote IP

            // Set fields to check for IP
            $fields = [
                'x_real_ip'      => true,
                'http_client_ip' => true,
                'remote_addr'    => true,static::$ip_address
            ];

            foreach ($_SERVER as $key => $value) {
                $key = strtolower($key);

                if (array_key_exists($key, $fields)) {
                    static::$ip_address = $value;
                    break;
                }
            }
        }

        return static::$ip_address;
    }


    /**
     * Returns the IP address for this session when it was started
     *
     * @return string|null
     */
    public static function getOriginalIpAddress(): ?string
    {
        return $_SESSION['first_ip'];
    }


    /**
     * Start this session
     *
     * @return void
     */
    public static function startup(): void
    {
        if (static::$has_started_up) {
            Log::warning(tr('Session has already started, not starting again'));
            return;
        }

        Log::action(tr('Starting session object'), 1);

        static::checkDomains();
        static::configureCookies();
        static::resume();

        static::$has_started_up = true;

        Http::setSslDefaultContext();
    }


    /**
     * @return bool
     */
    public static function hasStartedUp(): bool
    {
        return static::$has_started_up;
    }


    /**
     * Returns true if the user changed during this page load
     *
     * @return bool
     */
    public static function userChanged(): bool
    {
        return static::$user_changed;
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
        static::$domain = $_SERVER['HTTP_HOST'];

        if (!static::$domain) {
            // No domain was requested at all, so probably instead of a domain name, an IP was requested. Redirect to
            // the domain name
            Response::redirect();
        }

        // Check the detected domain against the configured domain. If it doesn't match then check if it's a registered
        // whitelabel domain
        if (static::$domain === Request::getDomain()) {
            // This is the primary domain

        } else {
            // This is not the registered domain!
            switch (Config::getBoolean('web.domains.whitelabels', false)) {
                case '':
                    // White label domains are disabled, so the requested domain MUST match the configured domain
                    Log::warning(tr('White labels are disabled, redirecting domain ":source" to ":target"', [
                        ':source' => $_SERVER['HTTP_HOST'],
                        ':target' => Request::getDomain(),
                    ]));

                    Response::redirect(PROTOCOL . Request::getDomain());

                case 'all':
                    // All domains are allowed
                    break;

                case 'sub':
                    // White label domains are disabled, but subdomains from the primary domain are allowed
                    if (Strings::from(static::$domain, '.') !== Request::getDomain()) {
                        Log::warning(tr('Whitelabels are set to subdomains only, redirecting domain ":source" to ":target"', [
                            ':source' => $_SERVER['HTTP_HOST'],
                            ':target' => Request::getDomain(),
                        ]));
                        Response::redirect(PROTOCOL . Request::getDomain());
                    }
                    break;

                case 'list':
                    // This domain must be registered in the whitelabels list
                    static::$domain = sql()->getColumn('SELECT `domain` 
                                                        FROM   `whitelabels` 
                                                        WHERE  `domain` = :domain 
                                                          AND  `status` IS NULL', [
                                                              ':domain' => $_SERVER['HTTP_HOST']
                    ]);

                    if (empty(static::$domain)) {
                        Log::warning(tr('Whitelabel check failed because domain was not found in database, redirecting domain ":source" to ":target"', [
                            ':source' => $_SERVER['HTTP_HOST'],
                            ':target' => Request::getDomain(),
                        ]));

                        Response::redirect(PROTOCOL . Request::getDomain());
                    }
                    break;

                default:
                    if (is_array(Config::get('web.domains.whitelabels', false))) {
                        // Domain must be specified in one of the array entries
                        if (!in_array(static::$domain, Config::get('web.domains.whitelabels', false))) {
                            Log::warning(tr('Whitelabel check failed because domain was not found in configured array, redirecting domain ":source" to ":target"', [
                                ':source' => $_SERVER['HTTP_HOST'],
                                ':target' => Request::getDomain(),
                            ]));

                            Response::redirect(PROTOCOL . Request::getDomain());
                        }

                    } else {
                        // The domain must match either domain configuration or the domain specified in configuration
                        // "whitelabels.enabled"
                        if (static::$domain !== Config::get('web.domains.whitelabels', false)) {
                            Log::warning(tr('Whitelabel check failed because domain did not match only configured alternative, redirecting domain ":source" to ":target"', [
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
        return static::$domain;
    }


    /**
     * Sets the domain for this session
     *
     * @return string
     */
    protected static function setDomain(): string
    {
        // Check what domains are accepted by the client (in order of importance) and see if we support any of those
        $supported_domains = Config::get('web.domains');

        if (array_key_exists($_SERVER['HTTP_HOST'], $supported_domains)) {
            static::$domain = $_SERVER['HTTP_HOST'];

            return static::$domain;
        }

        // No supported domain found, redirect to the primary domain
        Response::redirect(true);
    }


    /**
     * Returns the value for the given key / sub_key
     *
     * @param string      $key
     * @param string|null $sub_key
     *
     * @return mixed
     */
    public static function get(string $key, ?string $sub_key = null): mixed
    {
        if ($sub_key) {
            $section = isset_get($_SESSION[$key]);

            if (is_array($section)) {
                // Key exists and is an array, yay!
                return isset_get($section[$sub_key]);
            }

            if ($section === null) {
                // Key does not exist or was null, either way, nothing to return!
                return null;
            }

            // Sub must either not exist or be an array. Here its neither
            throw new OutOfBoundsException(tr('Cannot read session key ":key" sub key ":sub-key" because session key is not an array', [
                ':key'     => $key,
                ':sub-key' => $sub_key,
            ]));
        }

        return isset_get($_SESSION[$key]);
    }


    /**
     * Configure cookies
     *
     * @return void
     */
    protected static function configureCookies(): void
    {
        if (Response::getHttpHeadersSent()) {
            // Cannot configure cookies, headers have already been sent!
            throw new SessionException(tr('Cannot startup session, HTTP headers have already been sent'));
        }

        // Check the cookie domain configuration to see if it's valid.
        // NOTE: In case whitelabel domains are used, $_CONFIG[cookie][domain] must be one of "auto" or ".auto"
        switch (Config::getBoolString('web.sessions.cookies.domain', '.auto')) {
            case false:
                // This domain has no cookies
                break;

            case 'auto':
                Config::set('sessions.cookies.domain', static::$domain);
                ini_set('session.cookie_domain', static::$domain);
                break;

            case '.auto':
                Config::get('web.sessions.cookies.domain', '.' . static::$domain);
                ini_set('session.cookie_domain', '.' . static::$domain);
                break;

            default:
                // Test cookie domain limitation
                //
                // If the configured cookie domain is different from the current domain then all cookie will
                // inexplicably fail without warning, so this must be detected to avoid lots of hair pulling and
                // throwing arturo off the balcony incidents :)
                if (Config::getBoolString('web.sessions.cookies.domain')[0] == '.') {
                    $test = substr(Config::get('web.sessions.cookies.domain'), 1);

                } else {
                    $test = Config::getBoolString('web.sessions.cookies.domain');
                }

                if (!str_contains(static::$domain, $test)) {
                    Notification::new()
                                ->setUrl(Url::getWww('security/incidents.html'))
                                ->setMode(EnumDisplayMode::warning)
                                ->setCode('configuration')
                                ->setRoles('developer')
                                ->setTitle(tr('Invalid cookie domain'))
                                ->setMessage(tr('Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain". Please fix $_CONFIG[cookie][domain]! Redirecting to ":domain"', [
                                    ':domain'         => Strings::ensureStartsNotWith(Config::getBoolString('web.sessions.cookies.domain'), '.'),
                                    ':cookie_domain'  => Config::getBoolString('web.sessions.cookies.domain'),
                                    ':current_domain' => static::$domain,
                                ]))
                                ->send();
                    Response::redirect(PROTOCOL . Strings::ensureStartsNotWith(Config::getBoolString('web.sessions.cookies.domain'), '.'));
                }

                ini_set('session.cookie_domain', Config::getBoolString('web.sessions.cookies.domain'));

                unset($test);
                unset($length);
        }

        // Set session and cookie parameters
        try {
            if (Config::getBoolean('web.sessions.enabled', true)) {
                // Force session cookie configuration
                ini_set('session.gc_maxlifetime' , Config::getBoolString('web.sessions.timeout', true));
                ini_set('session.cookie_lifetime', Config::getInteger('web.sessions.cookies.lifetime', 0));
                ini_set('session.use_strict_mode', Config::getBoolean('web.sessions.cookies.strict_mode', true));
                ini_set('session.name'           , Config::getString('web.sessions.cookies.name', 'phoundation'));
                ini_set('session.cookie_httponly', Config::getBoolean('web.sessions.cookies.http-only', true));
                ini_set('session.cookie_secure'  , Config::getBoolean('web.sessions.cookies.secure', true));
                ini_set('session.cookie_samesite', Config::getBoolean('web.sessions.cookies.same-site', true));
                ini_set('session.save_handler'   , Config::getString('sessions.handler', 'files'));
                ini_set('session.save_path'      , Config::getString('sessions.path', DIRECTORY_SYSTEM . 'sessions/'));

                if (Config::getBoolean('web.sessions.check-referrer', true)) {
                    ini_set('session.referer_check', static::$domain);
                }

                if (Debug::isEnabled() or !Config::getBoolean('cache.http.enabled', true)) {
                    ini_set('session.cache_limiter', 'nocache');

                } else {
                    if (Config::get('cache.http.enabled', true) === 'auto') {
                        ini_set('session.cache_limiter', Config::getBoolean('cache.http.php-cache-limiter', true));
                        ini_set('session.cache_expire', Config::getBoolean('cache.http.php-cache-php-cache-expire', true));
                    }
                }
            }

        } catch (Exception $e) {
            if ($e->getCode() == 403) {
                // TODO Check if any of this is still required? we're no longer using page_show...
                Core::writeRegister(403, 'page_show');

            } else {
                if (!is_writable(session_save_path())) {
                    throw new SessionException(tr('Session startup failed because the session directory ":directory" is not writable for platform ":platform"', [
                        ':directory' => session_save_path(),
                        ':platform'  => PLATFORM,
                    ]), $e);
                }
                throw new SessionException(tr('Session startup failed'), $e);
            }
        }
    }


    /**
     * Returns the value for the given key
     *
     * @param string      $value
     * @param string      $key
     * @param string|null $sub_key
     *
     * @return void
     */
    public static function set(mixed $value, string $key, ?string $sub_key = null): void
    {
        if ($sub_key) {
            if (array_key_exists($key, $_SESSION)) {
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
     * Resume an existing session
     *
     * @return bool
     */
    public static function resume(): bool
    {
        if (!Config::get('web.sessions.enabled', true)) {
            return false;
        }

        switch (Request::getRequestType()) {
            case EnumRequestTypes::api:
                // API's don't do cookies at all
                return false;
            case EnumRequestTypes::ajax:
                // TODO Implement
        }

        if (isset_get(Core::readRegister('session', 'client')['type']) === 'crawler') {
            // Do not send cookies to crawlers!
            Log::information(tr('Crawler ":crawler" on URL ":url"', [
                ':crawler' => Core::readRegister('session', 'client'),
                ':url'     => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            ]));

            return false;
        }

//show(session_get_cookie_params());
//show('IMPLEMENT LONG SESSIONS SUPPORT');
//show('IMPLEMENT MYSQL SESSIONS SUPPORT');
//show('IMPLEMENT MEMCACHED SUPPORT WITH FALLBACK TO MYSQL');
//showdie('IMPLEMENT RETURN TO PREVIOUS PAGE AFTER LOGOUT SUPPORT');
        // What handler to use?
        switch (Config::getString('web.sessions.handler', 'files')) {
            case 'files':
                $directory = FsDirectory::new(
                    Config::getString('web.sessions.path', DIRECTORY_SYSTEM . 'sessions/'),
                    FsRestrictions::new([
                        DIRECTORY_DATA,
                        '/var/lib/php/sessions/',
                    ], true)
                )->ensure();

                session_save_path($directory->getSource());
                break;

            case 'memcached':
                // no break

            case 'redis':
                // no break

            case 'mongo':
                // no break

            case 'sql':
                // TODO Implement these session handlers ASAP
                throw new UnderConstructionException();
                break;

            default:
                throw new ConfigException(tr('Unknown session handler ":handler" specified in configuration path "web.sessions.handler"', [
                    ':handler' => Config::getString('web.sessions.handler', 'files'),
                ]));
        }

        // Start session
        session_start();

        static::$user              = null;
        static::$impersonated_user = null;

        // Initialize session?
        if (empty($_SESSION['init'])) {
            static::create();

        } else {
            // Check for extended sessions
            // TODO Why are we still doing this? We should be able to do extended sessions better
            // static::checkExtended();

            Log::success(tr('Resumed session ":session" for user ":user" from IP ":ip"', [
                ':session' => session_id(),
                ':user'    => static::getUserObject()->getLogId(),
                ':ip'      => Session::getIpAddress(),
            ]));
        }

        // check and set last activity
        if (Config::getInteger('web.sessions.cookies.lifetime', 0)) {
            // Session cookie timed out?
            if (isset($_SESSION['last_activity']) and (time() - $_SESSION['last_activity'] > Config::getInteger('web.sessions.cookies.lifetime', 0))) {
                // Session expired!
                session_unset();
                session_destroy();

                Log::warning('RESTART SESSION');

                session_start();
                session_regenerate_id(true);
            }
        }

        $_SESSION['last_activity'] = microtime(true);

        // Euro cookie check, can we do cookies at all?
        if (Config::getBoolean('web.sessions.cookies.europe', true) and !Config::getString('web.sessions.cookies.name', 'phoundation')) {
            if (GeoIp::new()->isEuropean()) {
                // All first visits to european countries require cookie permissions given!
                $_SESSION['euro_cookie'] = true;

                return false;
            }
        }

        if (Config::getBoolean('security.url-cloaking.enabled', false) and Config::getBoolean('security.url-cloaking.strict', false)) {
            /*
             * URL cloaking was enabled and requires strict checking.
             *
             * Ensure that we have a cloaked URL users_id and that it matches the sessions users_id
             * Only check cloaking rules if we are NOT displaying a system page
             */
            if (!Request::isRequestType(EnumRequestTypes::system)) {
                if (empty($core->register['url_cloak_users_id'])) {
                    throw new SessionException(tr('Failed cloaked URL strict checking, no cloaked URL users_id registered'));
                }

                if ($core->register['url_cloak_users_id'] !== $_SESSION['user']['id']) {
                    throw new AccessDeniedException(tr('Failed cloaked URL strict checking, cloaked URL users_id ":cloak_users_id" did not match the users_id ":session_users_id" of this session', [
                        ':session_users_id' => $_SESSION['user']['id'],
                        ':cloak_users_id'   => $core->register['url_cloak_users_id'],
                    ]));
                }
            }
        }

        if (Config::getBoolean('web.sessions.regenerate-id', false)) {
            // Regenerate session identifier
            if (isset($_SESSION['created']) and (time() - $_SESSION['created'] > Config::getBoolean('web.sessions.regenerate_id', false))) {
                // Use "created" to monitor session id age and refresh it periodically to mitigate
                // attacks on sessions like session fixation
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }

        // Is this first visit?
        // TODO Fix this crap. We should be able to redirect on first visit, or show modal or flash messages. Do much more!
        if (isset($_SESSION['first_visit'])) {
            if ($_SESSION['first_visit']) {
                $_SESSION['first_visit']--;
            }

        } else {
            $_SESSION['first_visit'] = 1;
        }

        if ($_SESSION['domain'] !== static::$domain) {
            // Domain mismatch? Okay if this is sub domain, but what if its a different domain? Check whitelist domains?
            // TODO Implement
        }

        $_SESSION['ip'] = Session::getIpAddress();

        if ($_SESSION['ip'] !== $_SESSION['first_ip']) {
            // IP mismatch? What to do here? configurable actions!
            // TODO Implement
        }

        // If any flash messages were stored in the $_SESSION, import them into the flash messages object
        if (isset($_SESSION['flash_messages'])) {
            static::getFlashMessagesObject()->import((array) $_SESSION['flash_messages']);
            unset($_SESSION['flash_messages']);
        }

        return true;
    }


    /**
     * Create a new session with basic data
     *
     * @return bool
     */
    protected static function create(): bool
    {
        Log::success(tr('Created new session ":session" for user ":user"', [
            ':session' => session_id(),
            ':user'    => static::getUserObject()->getLogId(),
        ]));

        // Initialize the session
        $_SESSION['init']         = microtime(true);
        $_SESSION['first_domain'] = static::$domain;
        $_SESSION['domain']       = static::$domain;
        $_SESSION['first_ip']     = static::getIpAddress();
//                        $_SESSION['client']       = Core::readRegister('system', 'session', 'client');
//                        $_SESSION['mobile']       = Core::readRegister('system', 'session', 'mobile');
//                        $_SESSION['location']     = Core::readRegister('system', 'session', 'location');
//                        $_SESSION['language']     = Core::readRegister('system', 'session', 'language');

        // Set users timezone
        if (empty($_SESSION['user']['timezone'])) {
            $_SESSION['user']['timezone'] = Config::get('timezone.display', 'UTC');

        } else {
            try {
                $check = new DateTimeZone($_SESSION['user']['timezone']);

            } catch (Exception $e) {
                // Timezone invalid for this user. Notification developers, and fix timezone for user
                $_SESSION['user']['timezone'] = Config::get('timezone.display', 'UTC');
                Notification::new()
                            ->setException(SessionException::new(tr('Reset timezone for user ":user" to ":timezone"', [
                                ':user'     => static::getUserObject()->getLogId(),
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
     * Returns the user for this session
     *
     * @return UserInterface
     */
    public static function getUserObject(): UserInterface
    {
        if (empty(session_id())) {
            if (PLATFORM_WEB) {
                throw new SessionException(tr('Cannot access session data yet, session has not yet been initialized'));
            }

            return new SystemUser();
        }

        // We can return impersonated user IF exists
        if (!empty($_SESSION['user']['impersonate_id'])) {
            // Return impersonated user
            if (empty(static::$impersonated_user)) {
                // Load impersonated user into cache variable
                static::$impersonated_user = static::loadUser($_SESSION['user']['impersonate_id']);
            }

            return static::$impersonated_user;
        }

        return static::getRealUserObject();
    }


    /**
     * Reloads the user data
     *
     * @return void
     */
    public static function reloadUser(): void
    {
        if (empty($_SESSION['user']['id'])) {
            throw new OutOfBoundsException(tr('Cannot reload user for session ":session", this session has no user', [
                ':session' => session_id(),
            ]));
        }

        static::$user = static::loadUser($_SESSION['user']['id']);
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
        // Create a new user object and ensure it's still good to go
        try {
            // This user is loaded by the session object and should NOT use meta-tracking!
            return User::load($users_id);

        } catch (DataEntryNotExistsException) {
            Log::warning(tr('The session user ":id" does not exist, removing session entry and dropping to guest user', [
                ':id' => $_SESSION['user']['id'],
            ]));

        } catch (DataEntryStatusException $e) {
            Log::warning($e->getMessage());

        } catch (Throwable $e) {
            Log::warning(tr('Failed to fetch user ":user" for session with ":e", removing session entry and dropping to guest user', [
                ':e'    => $e->getMessage(),
                ':user' => $_SESSION['user']['id'],
            ]));
        }

        // Remove user information for this session and return to guest user
        unset($_SESSION['user']['id']);

        return new GuestUser();
    }


    /**
     * Checks if an extended session is available for this user
     *
     * @return bool
     */
    protected static function checkExtended(): bool
    {
        if (empty($_CONFIG['sessions']['extended']['enabled'])) {
            return false;
        }
        if (isset($_COOKIE['extsession']) and !isset($_SESSION['user'])) {
            // Pull  extsession data
            $ext = sql_get('SELECT `users_id` 
                            FROM   `extended_sessions` 
                            WHERE  `session_key` = ":session_key" 
                              AND  DATE(`addedon`) < DATE(NOW());', [
                                  ':session_key' => cfm($_COOKIE['extsession'])
                   ]);

            if ($ext['users_id']) {
                $user = sql_get('SELECT * 
                                 FROM   `accounts_users` 
                                 WHERE  `accounts_users`.`id` = :id', [
                                     ':id' => cfi($ext['users_id'])
                        ]);

                if ($user['id']) {
                    // Auto sign in user
                    static::$user = Users::signin($user, true);

                    return true;

                } else {
                    // Remove cookie
                    setcookie('extsession', 'stub', 1);
                }

            } else {
                // Remove cookie
                setcookie('extsession', 'stub', 1);
            }
        }

        return false;
    }


    /**
     * Authenticate a user with the specified password
     *
     * @param string $user
     * @param string $password
     * @param string $user_class
     *
     * @return UserInterface
     */
    public static function signIn(string $user, string $password, string $user_class = User::class): UserInterface
    {
        try {
            static::$user         = $user_class::authenticate(['email' => $user], $password, EnumAuthenticationAction::signin);
            static::$user_changed = true;

            // Update the users sign-in and last sign-in information
            static::clear();
            static::updateSignInTracking();

            Incident::new()
                    ->setType(tr('User sign in'))
                    ->setSeverity(EnumSeverity::notice)
                    ->setTitle(tr('The user ":user" signed in', [':user' => static::$user->getLogId()]))
                    ->setDetails(['user' => static::$user->getLogId()])
                    ->save();

            $_SESSION['user']['id'] = static::$user->getId();

            return static::$user;

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
                                ->setDetails(['user' => $user])
                                ->notifyRoles('accounts')
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
     * @return void
     */
    public static function clear(): void
    {
        global $_SESSION;

        if (isset($_SESSION['init'])) {
            // Conserve init data
            $_SESSION = [
                'init'         => $_SESSION['init'],
                'domain'       => static::$domain,
                'first_ip'     => $_SESSION['first_ip'],
                'first_domain' => $_SESSION['first_domain'],
            ];

        } else {
            $_SESSION = [];
        }
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
                      SET    `last_sign_in` = NOW(), `sign_in_count` = `sign_in_count` + 1
                      WHERE  `id` = :id', [
            ':id' => static::$user->getId(),
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
            // If this page has flash messages that have not yet been displayed then store them in the session variable
            // so that they can be displayed on the next page load
            static::getFlashMessagesObject()->addSource(Response::getFlashMessagesObject());

            if (static::$flash_messages?->getCount()) {
                // There are flash messages in this session static object, export them to $_SESSIONS for the next page load
                $_SESSION['flash_messages'] = static::$flash_messages->export();
            }
        }

        session_write_close();
    }


    /**
     * Returns the user for this session
     *
     * @return UserInterface
     */
    public static function getRealUserObject(): UserInterface
    {
        // Return the real user
        if (empty(static::$user)) {
            // User object does not yet exist
            if (isset_get($_SESSION['user']['id'])) {
                static::$user = static::loadUser($_SESSION['user']['id']);

            } else {
                // TODO What if we run setup from HTTP? Change this to some sort of system flag
                if (PLATFORM_WEB) {
                    // There is no user, this is a guest session
                    static::$user = new GuestUser();

                } else {
                    // There is no user, this is a system session
                    static::$user = new SystemUser();
                }
            }
        }

        // Return from cache
        return static::$user;
    }


    /**
     * Validate sign in data
     *
     * @param ValidatorInterface|null $validator
     *
     * @return array
     */
    public static function validateSignIn(ValidatorInterface $validator = null): array
    {
        $validator = $validator ?? PostValidator::new();

        return $validator->select('email')->isEmail()
                         ->select('password')->isPassword()
                         ->validate();
    }


    /**
     * Validate sign up data
     *
     * @param ValidatorInterface|null $validator
     *
     * @return array
     */
    public static function validateSignUp(ValidatorInterface $validator = null): array
    {
        if (!$validator) {
            $validator = PostValidator::new();
        }

        return $validator->select('email')
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
        if (empty(static::$language)) {
            static::setLanguage();
        }

        return static::$language ?? $default;
    }


    /**
     * Returns the language for this session
     *
     * @return string
     */
    protected static function setLanguage(): string
    {
        // Check what languages are accepted by the client (in order of importance) and see if we support any of those
        $supported_languages = Arrays::force(Config::get('language.supported', []));
        $requested_languages = Request::acceptsLanguages();

        foreach ($requested_languages as $requested_language) {
            if (in_array($requested_language['language'], $supported_languages)) {
                static::$language = $requested_language['language'];

                return static::$language;
            }
        }

        // No supported language found, set the default language
        return Config::getString('languages.default', 'en');
    }


    /**
     * Returns the user session configuration object
     *
     * @return SessionConfigInterface
     */
    public static function getConfig(): SessionConfigInterface
    {
        if (empty(static::$config)) {
            static::$config = new SessionConfig();
        }

        return static::$config;
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
            case 'facebook':
            case 'google':
            case 'github':
            case 'twitter':
                return false;

            case 'email':
                return true;

            case 'lost-password':
                return true;

            case 'signup':
                // no break
            case 'sign-up':
                // no break
            case 'register':
                // no break
            case 'registration':
                return false;

            case 'copyright':
                return true;

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
                          ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy($_SESSION['user']['id'])
                          ->setStatus('cannot-impersonate-double')
                          ->save();

            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(tr('Cannot impersonate user ":user", we are already impersonating', [
                        ':user' => $user->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUserObject()->getLogId(),
                        'impersonating'       => User::load($_SESSION['user']['impersonate_id'])->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }

        if (!$user->canBeImpersonated()) {
            // Impersonation isn't allowed
            Authentication::new()
                          ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy($_SESSION['user']['id'])
                          ->setStatus('impersonation-not-allowed')
                          ->save();

            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(tr('Cannot impersonate user ":user", this user account is not able or allowed to be impersonated', [
                        ':user' => static::getUserObject()->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUserObject()->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }

        if ($user->getId() === static::getUserObject()->getId()) {
            // We cannot impersonate self!
            Authentication::new()
                          ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy($_SESSION['user']['id'])
                          ->setStatus('cannot-impersonate-self')
                          ->save();

            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(tr('Cannot impersonate user ":user", the user to impersonate is this user itself', [
                        ':user' => static::getUserObject()->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUserObject()->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }

        if ($user->hasAllRights('god')) {
            // Can't impersonate a god level user!
            Authentication::new()
                          ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                          ->setAction(EnumAuthenticationAction::startimpersonation)
                          ->setCreatedBy($_SESSION['user']['id'])
                          ->setStatus('cannot-impersonate-god')
                          ->save();

            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(EnumSeverity::severe)
                    ->setTitle(tr('Cannot impersonate user ":user", the user to impersonate has the "god" role', [
                        ':user' => static::getUserObject()->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUserObject()->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }

        // Impersonate the user
        $original_user = static::getUserObject();
        $_SESSION['user']['impersonate_id']  = $user->getId();
        $_SESSION['user']['impersonate_url'] = (string) Url::getCurrent();

        static::$user_changed = true;

        Authentication::new()
                      ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                      ->setAction(EnumAuthenticationAction::startimpersonation)
                      ->setCreatedBy($_SESSION['user']['id'])
                      ->save();

        // Register an incident
        Incident::new()
                ->setType('User impersonation')
                ->setSeverity(EnumSeverity::medium)
                ->setTitle(tr('The user ":user" started impersonating user ":impersonate"', [
                    ':user'        => $original_user->getLogId(),
                    ':impersonate' => $user->getLogId(),
                ]))
                ->setDetails([
                    'user'        => $original_user->getLogId(),
                    'impersonate' => $user->getLogId(),
                ])
                ->notifyRoles('accounts')
                ->save();

        // Notify the target user
        Notification::new()
                    ->setUrl('profiles/profile+' . $original_user->getId() . '.html')
                    ->setMode(EnumDisplayMode::warning)
                    ->setUsersId($_SESSION['user']['impersonate_id'])
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
     * Create a new session with basic data from the specified sign in key
     *
     * @param SignInKeyInterface $key
     *
     * @return UserInterface
     */
    public static function signKey(SignInKeyInterface $key): UserInterface
    {
        static::$key  = $key;
        static::$user = $key->getUserObject();

        static::clear();

        // Update the users sign-in and last sign-in information
        static::updateSignInTracking();

        Incident::new()
                ->setType(tr('User sign in'))
                ->setSeverity(EnumSeverity::notice)
                ->setTitle(tr('The user ":user" signed in using UUID key ":key"', [
                    ':key'  => $key->getUuid(),
                    ':user' => static::$user->getLogId(),
                ]))
                ->setDetails([
                    ':key' => $key->getUuid(),
                    'user' => static::$user->getLogId(),
                ])
                ->save();

        $_SESSION['user']['id'] = static::$user->getId();
        $_SESSION['sign-key']   = $key->getUuid();

        return static::$user;
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
        if (empty(static::$key)) {
            if (isset($_SESSION['sign-key'])) {
                try {
                    static::$key = SignInKey::new(['uuid' => $_SESSION['sign-key']]);

                } catch (DataEntryNotExistsException) {
                    // This session key doesn't exist, WTF? If it exists in session, it should exist in the DB. Since it
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

                    Session::signOut();

                    Response::getFlashMessagesObject()
                           ->addWarning(tr('Something went wrong with your session, please sign in again'));

                    Response::redirect('sign-in');
                }
            }
        }

        return static::$key;
    }


    /**
     * Destroy the current user session
     *
     * @return void
     */
    public static function signOut(): void
    {
        if (!session_id()) {
            Incident::new()
                ->setType('User sign out')
                ->setSeverity(EnumSeverity::low)
                ->setTitle(tr('User sign out requested on non existing session'))
                ->save();

            return;
        }

        try {
            if (isset($_SESSION['user']['impersonate_id'])) {
                // This session was impersonation a user. Don't sign out, stop impersonating
                try {
                    // We're impersonating a user, return to the original user.
                    $url            = $_SESSION['user']['impersonate_url'];
                    $users_id       = $_SESSION['user']['id'];
                    $impersonate_id = $_SESSION['user']['impersonate_id'];

                    unset($_SESSION['user']['impersonate_id']);
                    unset($_SESSION['user']['impersonate_url']);

                    static::$user_changed = true;

                    Authentication::new()
                        ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                       ->setAction(EnumAuthenticationAction::stopimpersonation)
                       ->setCreatedBy($users_id)
                       ->save();

                    Incident::new()
                        ->setType('User impersonation')
                        ->setSeverity(EnumSeverity::low)
                        ->setTitle(tr('The user ":user" stopped impersonating user ":impersonate"', [
                            ':user'        => User::load($users_id)->getLogId(),
                            ':impersonate' => User::load($impersonate_id)->getLogId(),
                        ]))
                        ->setDetails([
                            'user'        => User::load($users_id)->getLogId(),
                            'impersonate' => User::load($impersonate_id)->getLogId(),
                        ])
                        ->notifyRoles('accounts')
                        ->save();

                    Response::getFlashMessagesObject()
                        ->addSuccess(tr('You have stopped impersonating user ":user"', [
                            ':user' => User::load($users_id)->getLogId(),
                        ]));

                    Response::redirect($url);

                } catch (Throwable $e) {
                    // Oops?
                    Log::error($e);

                    Notification::new()
                        ->setException($e)
                        ->save();

                    Authentication::new()
                        ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                        ->setAction(EnumAuthenticationAction::stopimpersonation)
                        ->setCreatedBy($_SESSION['user']['id'])
                        ->setStatus('failed')
                        ->save();

                    Incident::new()
                        ->setType('User impersonation sign out failed')
                        ->setSeverity(EnumSeverity::low)
                        ->setTitle(tr('User impersonation sign out failed users id ":id", impersonate id ":impersonate_id", closing sessions', [
                            ':id'             => isset_get($_SESSION['user']['id']),
                            ':impersonate_id' => isset_get($_SESSION['user']['impersonate_id']),
                        ]))
                        ->save();
                }
            }

            Authentication::new()
                ->setAccount(Json::encode(['email' => static::getUserObject()->getEmail()], JSON_OBJECT_AS_ARRAY))
                ->setAction(EnumAuthenticationAction::signout)
                ->setCreatedBy($_SESSION['user']['id'])
                ->save();

            Incident::new()
                ->setType('User sign out')
                ->setSeverity(EnumSeverity::notice)
                ->setTitle(tr('The user ":user" signed out', [
                    ':user' => static::getUserObject()->getLogId(),
                ]))
                ->setDetails([
                    'user' => static::getUserObject()->getLogId(),
                ])
                ->save();

        } catch (Throwable $e) {
            // Oops! Session sign out just completely failed for some reason. Just log, destroy the session, and continue
            Log::error($e);

            Authentication::new()
                ->setAction(EnumAuthenticationAction::signout)
                ->setCreatedBy($_SESSION['user']['id'])
                ->setStatus('failed')
                ->save();

            Incident::new()
                ->setType('User sign out failed')
                ->setSeverity(EnumSeverity::notice)
                ->setTitle(tr('The sign out of user ":user" failed', [
                    ':user' => static::getUserObject()->getLogId(),
                ]))
                ->setDetails([
                    'user' => static::getUserObject()->getLogId(),
                ])
                ->save()
                ->notifyRoles('developers');
        }

        static::$user_changed = !static::getUserObject()->isGuest();

        // Destroy all in the session but the flash messages
        $messages = isset_get($_SESSION['flash_messages']);
        $_SESSION = [];

        if ($messages) {
            $_SESSION['flash_messages'] = $messages;
        }
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
        if (static::isInitialized()) {
            return static::getUserObject()->isGuest();
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
        return !static::isGuest();
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
        return static::getUserObject()->getId() === $user->getId();
    }
}
