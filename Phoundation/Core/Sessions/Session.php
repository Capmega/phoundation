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
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Accounts\Users\Interfaces\SignInKeyInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\SignIn;
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
use Phoundation\Security\Incidents\Severity;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Client;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessages;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessagesInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Throwable;

class Session implements SessionInterface
{
    /**
     * Singleton
     *
     * @var SessionInterface
     */
    protected static SessionInterface $instance;

    /**
     * The current user for this session
     *
     * @var UserInterface $user
     */
    protected static UserInterface $user;

    /**
     * The current impersonated user for this session
     *
     * @var UserInterface $impersonated_user
     */
    protected static UserInterface $impersonated_user;

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
     * Session level flash messages
     *
     * @var FlashMessages|null $flash_messages
     */
    protected static ?FlashMessages $flash_messages = null;

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

        // Correctly detect the remote IP
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
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
                                                       AND `status` IS NULL', [':domain' => $_SERVER['HTTP_HOST']]);

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
                                ->setUrl('security/incidents.html')
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
                ini_set('session.gc_maxlifetime', Config::getBoolString('web.sessions.timeout', true));
                ini_set('session.cookie_lifetime', Config::getInteger('web.sessions.cookies.lifetime', 0));
                ini_set('session.use_strict_mode', Config::getBoolean('web.sessions.cookies.strict_mode', true));
                ini_set('session.name', Config::getString('web.sessions.cookies.name', 'phoundation'));
                ini_set('session.cookie_httponly', Config::getBoolean('web.sessions.cookies.http-only', true));
                ini_set('session.cookie_secure', Config::getBoolean('web.sessions.cookies.secure', true));
                ini_set('session.cookie_samesite', Config::getBoolean('web.sessions.cookies.same-site', true));
                ini_set('session.save_handler', Config::getString('sessions.handler', 'files'));
                ini_set('session.save_path', Config::getString('sessions.path', DIRECTORY_DATA . 'data/sessions/'));

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
                $directory = FsDirectory::new(Config::getString('web.sessions.path', DIRECTORY_DATA . 'sessions/'), FsRestrictions::new([
                    DIRECTORY_DATA,
                    '/var/lib/php/sessions/',
                ],                                                                                                                      true, 'system/sessions'))
                                        ->ensure();
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

        // Initialize session?
        if (empty($_SESSION['init'])) {
            static::create();

        } else {
            // Check for extended sessions
            // TODO Why are we still doing this? We should be able to do extended sessions better
            static::checkExtended();

            Log::success(tr('Resumed session ":session" for user ":user" from IP ":ip"', [
                ':session' => session_id(),
                ':user'    => static::getUser()->getLogId(),
                ':ip'      => $_SERVER['REMOTE_ADDR'],
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

        $_SESSION['ip'] = isset_get($_SERVER['REMOTE_ADDR']);

        if ($_SESSION['ip'] !== $_SESSION['first_ip']) {
            // IP mismatch? What to do here? configurable actions!
            // TODO Implement
        }

        // If any flash messages were stored in the $_SESSION, import them into the flash messages object
        if (isset($_SESSION['flash_messages'])) {
            static::getFlashMessages()->import((array) $_SESSION['flash_messages']);
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
            ':user'    => static::getUser()->getLogId(),
        ]));

        // Initialize the session
        $_SESSION['init']         = microtime(true);
        $_SESSION['first_domain'] = static::$domain;
        $_SESSION['domain']       = static::$domain;
        $_SESSION['first_ip']     = isset_get($_SERVER['REMOTE_ADDR']);
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
                                ':user'     => static::getUser()->getLogId(),
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
    public static function getUser(): UserInterface
    {
        return static::returnUser(false);
    }


    /**
     * Returns the user for this session
     *
     * @param bool $real_user
     *
     * @return UserInterface
     * @todo Add caching for real_user
     */
    protected static function returnUser(bool $real_user): UserInterface
    {
        if (!$real_user) {
            // We can return impersonated user IF exists
            if (!empty($_SESSION['user']['impersonate_id'])) {
                // Return impersonated user
                if (empty(static::$impersonated_user)) {
                    // Load impersonated user into cache variable
                    static::$impersonated_user = static::loadUser($_SESSION['user']['impersonate_id']);
                }

                return static::$impersonated_user;
            }
        }

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
            $user = User::load($users_id, 'id');

            if (!$user->getStatus()) {
                return $user;
            }

            // Only status NULL is allowed
            Log::warning(tr('The user ":user" has the status ":status" which is not allowed, killed session and dropping to guest user', [
                ':user'   => $user->getLogId(),
                ':status' => $user->getStatus(),
            ]));

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


//    /*
//     * Read value for specified key from $_SESSION[cache][$key]
//     *
//     * If $_SESSION[cache][$key] does not exist, then execute the callback and
//     * store the resulting value in $_SESSION[cache][$key]
//     */
//    function session_cache($key, $callback)
//    {
//        try {
//            if (empty($_SESSION)) {
//                return null;
//            }
//
//            if (!isset($_SESSION['cache'])) {
//                $_SESSION['cache'] = array();
//            }
//
//            if (!isset($_SESSION['cache'][$key])) {
//                $_SESSION['cache'][$key] = $callback();
//            }
//
//            return $_SESSION['cache'][$key];
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('session_cache(): Failed'), $e);
//        }
//    }


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
            static::$user = $user_class::authenticate($user, $password);
            static::clear();

            // Update the users sign-in and last sign-in information
            static::updateSignInTracking();

            Incident::new()
                    ->setType(tr('User sign in'))
                    ->setSeverity(Severity::notice)
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
                                ->setSeverity(Severity::low)
                                ->setTitle(tr('The specified user ":user" does not exist', [':user' => $user]))
                                ->setDetails(['user' => $user])
                                ->notifyRoles('accounts')
                                ->save();
                        // The specified user does not exist
                        throw AuthenticationException::new(tr('The specified user ":user" does not exist', [
                            ':user' => $user,
                        ]))->makeWarning()
                           ->log();
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
        if (Config::getBoolean('sessions.tracking.enabled', true)) {
            sql()->query('UPDATE `accounts_users`
                                SET    `last_sign_in` = NOW(), `sign_in_count` = `sign_in_count` + 1
                                WHERE  `id` = :id', [
                ':id' => static::$user->getId(),
            ]);

            // Store this sign in
            Signin::detect()->save();
        }
    }


    /**
     * Returns the page flash messages
     *
     * @return FlashMessagesInterface
     * @todo Load flash messages for the session!
     */
    public static function getFlashMessages(): FlashMessagesInterface
    {
        if (!static::$flash_messages) {
            static::$flash_messages = FlashMessages::new();
        }

        return static::$flash_messages;
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
            static::getFlashMessages()->pullMessagesFrom(Response::getFlashMessages());

            if (static::$flash_messages->getCount()) {
                // There are flash messages in this session static object, export them to $_SESSIONS for the next page load
                $_SESSION['flash_messages'] = static::$flash_messages->export();
            }
        }
    }


    /**
     * Returns the user for this session
     *
     * @return UserInterface
     */
    public static function getRealUser(): UserInterface
    {
        return static::returnUser(true);
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
            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(Severity::high)
                    ->setTitle(tr('Cannot impersonate user ":user", we are already impersonating', [
                        ':user' => $user->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUser()
                                                       ->getLogId(),
                        'impersonating'       => User::load($_SESSION['user']['impersonate_id'], 'id')
                                                     ->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }
        if (!$user->canBeImpersonated()) {
            // We are already impersonating a user!
            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(Severity::high)
                    ->setTitle(tr('Cannot impersonate user ":user", this user account is not able or allowed to be impersonated', [
                        ':user' => static::getUser()
                                         ->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUser()
                                                       ->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }
        if (
            $user->getId() === static::getUser()
                                     ->getId()
        ) {
            // We are already impersonating a user!
            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(Severity::high)
                    ->setTitle(tr('Cannot impersonate user ":user", the user to impersonate is this user itself', [
                        ':user' => static::getUser()
                                         ->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUser()
                                                       ->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }
        if ($user->hasAllRights('god')) {
            // Can't impersonate a god level user!
            Incident::new()
                    ->setType('User impersonation failed')
                    ->setSeverity(Severity::severe)
                    ->setTitle(tr('Cannot impersonate user ":user", the user to impersonate has the "god" role', [
                        ':user' => static::getUser()
                                         ->getLogId(),
                    ]))
                    ->setDetails([
                        'user'                => static::getUser()
                                                       ->getLogId(),
                        'want_to_impersonate' => $user->getLogId(),
                    ])
                    ->notifyRoles('accounts')
                    ->save()
                    ->throw();
        }
        // Impersonate the user
        $original_user = static::getUser();
        $_SESSION['user']['impersonate_id']  = $user->getId();
        $_SESSION['user']['impersonate_url'] = (string) Url::getCurrent();
        // Register an incident
        Incident::new()
                ->setType('User impersonation')
                ->setSeverity(Severity::medium)
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
        static::$user = $key->getUser();
        static::clear();

        // Update the users sign-in and last sign-in information
        static::updateSignInTracking();

        // Store this sign in
        Signin::detect()->save();

        Incident::new()
                ->setSeverity(Severity::notice)
                ->setType(tr('User sign in'))
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
                    static::$key = SignInKey::new($_SESSION['sign-key'], 'uuid');

                } catch (DataEntryNotExistsException) {
                    // This session key doesn't exist, WTF? If it exists in session, it should exist in the DB. Since it
                    // does not exist, assume the session contains invalid data. Drop the session
                    Incident::new()
                            ->setSeverity(Severity::medium)
                            ->setType(tr('Invalid session data'))
                            ->setTitle(tr('Session has sign-key that does not exist, session will be dropped'))
                            ->setDetails([
                                'sign-key' => $_SESSION['sign-key'],
                                'users_id' => Session::getUser()
                                                     ->getLogId(),
                            ])
                            ->save();

                    Session::signOut();

                    Response::getFlashMessages()
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
                    ->setSeverity(Severity::low)
                    ->setTitle(tr('User sign out requested on non existing session'))
                    ->save();

            return;
        }
        try {
            if (isset($_SESSION['user']['impersonate_id'])) {
                try {
                    Incident::new()
                            ->setType('User impersonation')
                            ->setSeverity(Severity::low)
                            ->setTitle(tr('The user ":user" stopped impersonating user ":impersonate"', [
                                ':user'        => User::load($_SESSION['user']['id'], 'id')
                                                      ->getLogId(),
                                ':impersonate' => User::load($_SESSION['user']['impersonate_id'], 'id')
                                                      ->getLogId(),
                            ]))
                            ->setDetails([
                                'user'        => User::load($_SESSION['user']['id'], 'id')
                                                     ->getLogId(),
                                'impersonate' => User::load($_SESSION['user']['impersonate_id'], 'id')
                                                     ->getLogId(),
                            ])
                            ->notifyRoles('accounts')
                            ->save();

                    // We're impersonating a user, return to the original user.
                    $url      = $_SESSION['user']['impersonate_url'];
                    $users_id = $_SESSION['user']['impersonate_id'];

                    unset($_SESSION['user']['impersonate_id']);
                    unset($_SESSION['user']['impersonate_url']);

                    Response::getFlashMessages()
                           ->addSuccess(tr('You have stopped impersonating user ":user"', [
                               ':user' => User::load($users_id, 'id')
                                              ->getLogId(),
                           ]));
                    Response::redirect($url);

                } catch (Throwable $e) {
                    // Oops?
                    Log::error($e);
                    Notification::new()
                                ->setException($e)
                                ->save();
                    Incident::new()
                            ->setType('User impersonation sign out failed')
                            ->setSeverity(Severity::low)
                            ->setTitle(tr('User impersonation sign out failed users id ":id", impersonate id ":impersonate_id", closing sessions', [
                                ':id'             => isset_get($_SESSION['user']['id']),
                                ':impersonate_id' => isset_get($_SESSION['user']['impersonate_id']),
                            ]))
                            ->save();
                }
            }
            Incident::new()
                    ->setType('User sign out')
                    ->setSeverity(Severity::notice)
                    ->setTitle(tr('The user ":user" signed out', [
                        ':user' => static::getUser()
                                         ->getLogId(),
                    ]))
                    ->setDetails([
                        'user' => static::getUser()
                                        ->getLogId(),
                    ])
                    ->save();

        } catch (Throwable $e) {
            // Oops! Session sign out just completely failed for some reason. Just log, destroy the session, and continue
            Log::error($e);
        }
        session_destroy();
    }


    /**
     * Returns true if the current session has a guest user
     *
     * @return bool
     */
    public static function isGuest(): bool
    {
        return static::getUser()->isGuest();
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
}
