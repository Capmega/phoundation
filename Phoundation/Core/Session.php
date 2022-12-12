<?php

namespace Phoundation\Core;

use DateTimeZone;
use Exception;
use GeoIP;
use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\SessionException;
use Phoundation\Data\Exception\DataEntryNotExistsException;
use Phoundation\Data\Exception\DataEntryStatusException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Client;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Web;
use Phoundation\Web\WebPage;
use Throwable;



/**
 * Class Session
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Session
{
    /**
     * The current user for this session
     *
     * @var User|null $user
     */
    protected static ?User $user = null;

    /**
     * Tracks if the session has startup or not
     *
     * @var bool $startup
     */
    protected static bool $startup = false;

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
     * @return void
     */
    public static function startup(): void
    {
        if (self::$startup) {
            return;
        }

        // Correctly detect the remote IP
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        self::checkDomains();
        self::configureCookies();
        self::checkCookie();

        Http::setSslDefaultContext();
    }



    /**
     * @return User
     */
    public static function getUser(): User
    {
        if (self::$user === null) {
            // User object does not yet exist
            if (isset_get($_SESSION['user']['id'])) {
                // Create new user object and ensure its still good to go
                try {
                    $user = User::get($_SESSION['user']['id']);

                    if ($user->getStatus()) {
                        // Only status NULL is allowed
                        throw new DataEntryStatusException(tr('The user ":user" has the status ":status" which is not allowed, removing session entry and dropping to guest user', [
                            ':user'   => $user->getLogId(),
                            ':status' => $user->getStatus()
                        ]));
                    }

                    return $user;

                } catch (DataEntryNotExistsException) {
                    Log::warning(tr('The session user ":id" does not exist, removing session entry and dropping to guest user', [
                        ':id' => $_SESSION['user']['id']
                    ]));

                    // Remove entry and try again
                    unset($_SESSION['user']['id']);

                } catch (DataEntryStatusException $e) {
                    Log::warning($e->getMessage());

                    // Remove entry and try again
                    unset($_SESSION['user']['id']);

                } catch (Throwable $e) {
                    Log::warning(tr('Failed to fetch user ":user" for session with ":e", removing session entry and dropping to guest user', [
                        ':e'    => $e->getMessage(),
                        ':user' => $_SESSION['user']['id']
                    ]));

                    // Remove entry and try again
                    unset($_SESSION['user']['id']);
                }
            }

            // There is no user, this is a guest session
            return new GuestUser();
        }

        // Return the user object
        return self::$user;
    }



    /**
     * Authenticate a user with the specified password
     *
     * @param string $user
     * @param string $password
     * @return User
     */
    public static function signIn(string $user, string $password): User
    {
        self::$user = User::authenticate($user, $password);
        self::clear();
        self::init();

        $_SESSION['user']['id'] = self::$user->getId();
        return self::$user;
    }



    /**
     * Returns the domain for this session
     *
     * @return string
     */
    public static function getDomain(): string
    {
        return self::$domain;
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
            self::$domain = $_SERVER['HTTP_HOST'];
            return self::$domain;
        }

        // No supported domain found, redirect to the primary domain
        WebPage::redirect(true);
    }



    /**
     * Returns the language for this session
     *
     * @return string
     */
    public static function getLanguage(): string
    {
        if (empty(self::$language)) {
            self::setLanguage();
        }

        return self::$language;
    }



    /**
     * Returns the language for this session
     *
     * @return string
     */
    protected static function setLanguage(): string
    {
        // Check what languages are accepted by the client (in order of importance) and see if we support any of those
        $supported_languages = Arrays::force(Config::get('languages.supported', []));
        $requested_languages = WebPage::acceptsLanguages();

        foreach ($requested_languages as $requested_language) {
            if (in_array($requested_language['language'], $supported_languages)) {
                self::$language = $requested_language['language'];
                return self::$language;
            }
        }

        // No supported language found, set the default language
        return Config::get('languages.default', 'en');
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
     * Check if we have a session from a cookie
     *
     * @return void
     */
    public static function checkCookie(): void
    {
        // New session? Detect client type, language, and mobile device
        if (array_key_exists(Config::get('web.sessions.cookies.name', 'phoundation'), $_COOKIE)) {
            try {
                // We have a cookie! Start a session for it
                Session::start();
            } catch (SessionException $e) {
                Log::warning(tr('Failed to resume session due to exception ":e"', [':e' => $e->getMessage()]));
                // Failed to start an existing session, so we'll have to detect the client anyway
                Client::detect();
            }
        } else {
            Client::detect();
        }
    }



    /**
     * Clear the current session
     *
     * @return void
     */
    public static function clear(): void
    {
        $_SESSION = [];
    }



    /**
     * Start a PHP session
     *
     * @return bool
     */
    public static function start(): bool
    {
        if (!Config::get('web.sessions.enabled', true)) {
            return false;
        }

        if (Core::isCallType('api')) {
            // Do not send cookies to API's!
            return false;
        }

        if (isset_get(Core::readRegister('session', 'client')['type']) === 'crawler') {
            // Do not send cookies to crawlers!
            Log::information(tr('Crawler ":crawler" on URL ":url"', [
                ':crawler' => Core::readRegister('session', 'client'),
                ':url'     => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
            ]));

            return false;
        }

        // What handler to use?
        switch (Config::get('web.sessions.handler', 'files')) {
            case 'files':
                $path = Path::new(Config::get('web.sessions.path', PATH_DATA), Restrictions::new([PATH_DATA . 'sessions/', '/var/lib/php/sessions'], true, 'system/sessions'))->ensure();

                session_save_path($path);

                Log::success(tr('Started new session for user ":user" from IP ":ip"', [
                    ':user' => self::getUser()->getLogId(),
                    ':ip'   => $_SERVER['REMOTE_ADDR']
                ]));
                break;

            case 'memcached':
                // no-break
            case 'redis':
                // no-break
            case 'mongo':
                // no-break
            case 'sql':
                // TODO Implement these session handlers ASAP
                throw new UnderConstructionException();
                break;

            default:
                throw new ConfigException(tr('Unknown session handler ":handler" specified in configuration path "web.sessions.handler"', [
                    ':handler' => Config::get('web.sessions.handler', 'files')
                ]));
        }

        // Start session
Log::warning('START SESSION');
        session_start();
        self::checkExtended();

        if (Config::get('web.sessions.cookies.lifetime', 0)) {
            // Session cookie timed out?
            if (isset($_SESSION['last_activity']) and (time() - $_SESSION['last_activity'] > Config::get('web.sessions.cookies.lifetime', 0))) {
                // Session expired!
                session_unset();
                session_destroy();
Log::warning('RESTART SESSION');
                session_start();
                session_regenerate_id(true);
            }
        }

        // Initialize session?
        if (empty($_SESSION['init'])) {
            self::init();
        }

        // Euro cookie check, can we do cookies at all?
        if (Config::get('web.sessions.cookies.europe', true) and !Config::get('web.sessions.cookies.name', 'phoundation')) {
            if (GeoIP::new()->isEuropean()) {
                // All first visits to european countries require cookie permissions given!
                $_SESSION['euro_cookie'] = true;
                return false;
            }
        }

        if (Config::get('security.url-cloaking.enabled', false) and Config::get('security.url-cloaking.strict', false)) {
            /*
             * URL cloaking was enabled and requires strict checking.
             *
             * Ensure that we have a cloaked URL users_id and that it matches the sessions users_id
             * Only check cloaking rules if we are NOT displaying a system page
             */
            if (!Core::getCallType('system')) {
                if (empty($core->register['url_cloak_users_id'])) {
                    throw new SessionException(tr('Failed cloaked URL strict checking, no cloaked URL users_id registered'));
                }

                if ($core->register['url_cloak_users_id'] !== $_SESSION['user']['id']) {
                    throw new AccessDeniedException(tr('Failed cloaked URL strict checking, cloaked URL users_id ":cloak_users_id" did not match the users_id ":session_users_id" of this session', [
                        ':session_users_id' => $_SESSION['user']['id'],
                        ':cloak_users_id'   => $core->register['url_cloak_users_id']
                    ]));
                }
            }
        }

        if (Config::get('web.sessions.regenerate-id', false)) {
            // Regenerate session identifier
            if (isset($_SESSION['created']) and (time() - $_SESSION['created'] > Config::get('web.sessions.regenerate_id', false))) {
                // Use "created" to monitor session id age and refresh it periodically to mitigate
                // attacks on sessions like session fixation
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }

        // Set last activity, and first_visit variables
        $_SESSION['last_activity'] = time();

        if (isset($_SESSION['first_visit'])) {
            if ($_SESSION['first_visit']) {
                $_SESSION['first_visit']--;
            }

        } else {
            $_SESSION['first_visit'] = 1;
        }

        $_SESSION['domain'] = self::$domain;
        return true;
    }



    /**
     * Destroy the current user session
     *
     * @return void
     */
    public static function destroy(): void
    {
        session_destroy();
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
        switch (Config::get('web.sessions.cookies.domain', '.auto')) {
            case false:
                // This domain has no cookies
                break;

            case 'auto':
                Config::set('sessions.cookies.domain', self::$domain);
                ini_set('session.cookie_domain', self::$domain);
                break;

            case '.auto':
                Config::get('web.sessions.cookies.domain', '.'.self::$domain);
                ini_set('session.cookie_domain', '.'.self::$domain);
                break;

            default:
                /*
                 * Test cookie domain limitation
                 *
                 * If the configured cookie domain is different from the current domain then all cookie will inexplicably fail without warning,
                 * so this must be detected to avoid lots of hair pulling and throwing arturo off the balcony incidents :)
                 */
                if (Config::get('web.sessions.cookies.domain')[0] == '.') {
                    $test = substr(Config::get('web.sessions.cookies.domain'), 1);

                } else {
                    $test = Config::get('web.sessions.cookies.domain');
                }

                if (!str_contains(self::$domain, $test)) {
                    Notification::new()
                        ->setCode('configuration')
                        ->setGroups('developers')
                        ->setTitle(tr('Invalid cookie domain'))
                        ->setMessage(tr('Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain". Please fix $_CONFIG[cookie][domain]! Redirecting to ":domain"', [
                            ':domain'         => Strings::startsNotWith(Config::get('web.sessions.cookies.domain'), '.'),
                            ':cookie_domain'  => Config::get('web.sessions.cookies.domain'),
                            ':current_domain' => self::$domain
                        ]))->send();

                    WebPage::redirect(PROTOCOL.Strings::startsNotWith(Config::get('web.sessions.cookies.domain'), '.'));
                }

                ini_set('session.cookie_domain', Config::get('web.sessions.cookies.domain'));
                unset($test);
                unset($length);
        }

        // Set session and cookie parameters
        try {
            if (Config::get('web.sessions.enabled', true)) {
                // Force session cookie configuration
                ini_set('session.gc_maxlifetime' , Config::get('web.sessions.timeout'            , true));
                ini_set('session.cookie_lifetime', Config::get('web.sessions.cookies.lifetime'   , 0));
                ini_set('session.use_strict_mode', Config::get('web.sessions.cookies.strict_mode', true));
                ini_set('session.name'           , Config::get('web.sessions.cookies.name'       , 'phoundation'));
                ini_set('session.cookie_httponly', Config::get('web.sessions.cookies.http-only'  , true));
                ini_set('session.cookie_secure'  , Config::get('web.sessions.cookies.secure'     , true));
                ini_set('session.cookie_samesite', Config::get('web.sessions.cookies.same-site'  , true));
                ini_set('session.save_handler'   , Config::get('sessions.handler'                , 'files'));
                ini_set('session.save_path'      , Config::get('sessions.path'                   , PATH_DATA . 'data/sessions/'));

                if (Config::get('web.sessions.check-referrer', true)) {
                    ini_set('session.referer_check', self::$domain);
                }

                if (Debug::enabled() or !Config::get('cache.http.enabled', true)) {
                    ini_set('session.cache_limiter', 'nocache');

                } else {
                    if (Config::get('cache.http.enabled', true) === 'auto') {
                        ini_set('session.cache_limiter', Config::get('cache.http.php-cache-limiter'         , true));
                        ini_set('session.cache_expire' , Config::get('cache.http.php-cache-php-cache-expire', true));
                    }
                }
            }

        }catch(Exception $e) {
            if ($e->getCode() == 403) {
                $core->register['page_show'] = 403;

            } else {
                if (!is_writable(session_save_path())) {
                    throw new SessionException('Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
                }

                throw new SessionException('Session startup failed', $e);
            }
        }
    }



    /**
     * Check the requested domain, if its a valid main domain, sub domain or whitelabel domain
     *
     * @return void
     */
    protected static function checkDomains(): void
    {
        // :TODO: The next section may be included in the whitelabel domain check
        // Check if the requested domain is allowed
        self::$domain = $_SERVER['HTTP_HOST'];

        if (!self::$domain) {
            // No domain was requested at all, so probably instead of a domain name, an IP was requested. Redirect to
            // the domain name
            WebPage::redirect();
        }

        // Check the detected domain against the configured domain. If it doesn't match then check if it's a registered
        // whitelabel domain
        if (self::$domain === Web::getDomain()) {
            // This is the primary domain

        } else {
            // This is not the registered domain!
            switch (Config::get('web.domains.whitelabels', false)) {
                case '':
                    // White label domains are disabled, so the requested domain MUST match the configured domain
                    Log::warning(tr('Whitelabels are disabled, redirecting domain ":source" to ":target"', [
                        ':source' => $_SERVER['HTTP_HOST'],
                        ':target' => Web::getDomain()
                    ]));

                    WebPage::redirect(PROTOCOL . Web::getDomain());

                case 'all':
                    // All domains are allowed
                    break;

                case 'sub':
                    // White label domains are disabled, but subdomains from the primary domain are allowed
                    if (Strings::from(self::$domain, '.') !== Web::getDomain()) {
                        Log::warning(tr('Whitelabels are set to subdomains only, redirecting domain ":source" to ":target"', [
                            ':source' => $_SERVER['HTTP_HOST'],
                            ':target' => Web::getDomain()
                        ]));

                        WebPage::redirect(PROTOCOL . Web::getDomain());
                    }

                    break;

                case 'list':
                    // This domain must be registered in the whitelabels list
                    self::$domain = sql()->getColumn('SELECT `domain` 
                                                          FROM   `whitelabels` 
                                                          WHERE  `domain` = :domain 
                                                          AND `status` IS NULL',
                        [':domain' => $_SERVER['HTTP_HOST']]);

                    if (empty(self::$domain)) {
                        Log::warning(tr('Whitelabel check failed because domain was not found in database, redirecting domain ":source" to ":target"', [
                            ':source' => $_SERVER['HTTP_HOST'],
                            ':target' => Web::getDomain()
                        ]));

                        WebPage::redirect(PROTOCOL . Web::getDomain());
                    }

                    break;

                default:
                    if (is_array(Config::get('web.domains.whitelabels', false))) {
                        // Domain must be specified in one of the array entries
                        if (!in_array(self::$domain, Config::get('web.domains.whitelabels', false))) {
                            Log::warning(tr('Whitelabel check failed because domain was not found in configured array, redirecting domain ":source" to ":target"', [
                                ':source' => $_SERVER['HTTP_HOST'],
                                ':target' => Web::getDomain()
                            ]));

                            WebPage::redirect(PROTOCOL . Web::getDomain());
                        }

                    } else {
                        // The domain must match either domain configuration or the domain specified in configuration
                        // "whitelabels.enabled"
                        if (self::$domain !== Config::get('web.domains.whitelabels', false)) {
                            Log::warning(tr('Whitelabel check failed because domain did not match only configured alternative, redirecting domain ":source" to ":target"', [
                                ':source' => $_SERVER['HTTP_HOST'],
                                ':target' => Web::getDomain()
                            ]));

                            WebPage::redirect(PROTOCOL . Web::getDomain());
                        }
                    }
            }
        }
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
                            FROM `extended_sessions` WHERE `session_key` = ":session_key" AND DATE(`addedon`) < DATE(NOW());', array(':session_key' => cfm($_COOKIE['extsession'])));

            if ($ext['users_id']) {
                $user = sql_get('SELECT * FROM `accounts_users` WHERE `accounts_users`.`id` = :id', array(':id' => cfi($ext['users_id'])));

                if ($user['id']) {
                    // Auto sign in user
                    self::$user = Users::signin($user, true);
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
     * Initialize the session with basic data
     *
     * @return void
     */
    protected static function init(): void
    {
        Log::action(tr('Initializing new session user ":user"', [':user' => self::getUser()->getLogId()]));

        // Initialize the session
        $_SESSION['init']         = time();
        $_SESSION['first_domain'] = self::$domain;

//                        $_SESSION['client']       = Core::readRegister('system', 'session', 'client');
//                        $_SESSION['mobile']       = Core::readRegister('system', 'session', 'mobile');
//                        $_SESSION['location']     = Core::readRegister('system', 'session', 'location');
//                        $_SESSION['language']     = Core::readRegister('system', 'session', 'language');

        // Set users timezone
        if (empty($_SESSION['user']['timezone'])) {
            $_SESSION['user']['timezone'] = Config::get('timezone.display', 0);

        } else {
            try {
                $check = new DateTimeZone($_SESSION['user']['timezone']);

            }catch(Exception $e) {
                // Timezone invalid for this user. Notification developers, and fix timezone for user
                $_SESSION['user']['timezone'] = Config::get('timezone.display', 0);

                Notification::new()
                    ->setException(SessionException::new(tr('Reset timezone for user ":user" to ":timezone"', [
                        ':user'     => name($_SESSION['user']),
                        ':timezone' => $_SESSION['user']['timezone']
                    ]), $e)->makeWarning(true))
                    ->send();
            }
        }
    }
}