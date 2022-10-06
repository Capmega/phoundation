<?php
try {
    /*
     * Correctly detect the remote IP
     */
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }



    /*
     * New session? Detect client type, language, and mobile device
     */
    if (empty($_COOKIE[$_CONFIG['sessions']['cookie_name']])) {
        load_libs('detect');
        detect();
    }



    /*
     * Add a powered-by header
     */
    if ($_CONFIG['security']['signature']) {
        if ($_CONFIG['security']['signature'] === 'limited') {
            header('Powered-By: Phoundation');

        } else {
            header('Powered-By: Phoundation version "'.FRAMEWORKCODEVERSION.'"');
        }
    }



// :TODO: The next section may be included in the whitelabel domain check
    /*
     * Check if the requested domain is allowed
     */
    $domain = cfm($_SERVER['HTTP_HOST']);

    if (!$domain) {
        /*
         * No domain was requested at all, so probably instead of a domain
         * name, an IP was requested. Redirect to the domain name
         */
        redirect(PROTOCOL.$_CONFIG['domain']);
    }



    /*
     * Check the detected domain against the configured domain.
     * If it doesnt match then check if its a registered whitelabel domain
     */
    if ($domain === $_CONFIG['domain']) {
        /*
         * This is the registered domain
         */

    } else {
        /*
         * This is not the registered domain!
         */
        if ($_CONFIG['whitelabels'] === false) {
            /*
             * white label domains are disabled, so the requested domain
             * MUST match the configured domain
             */
            log_file(tr('Whitelabels are disabled, redirecting domain ":source" to ":target"', array(':source' => $_SERVER['HTTP_HOST'], ':target' => $_CONFIG['domain'])), 'manage-session', 'yellow');
            redirect(PROTOCOL.$_CONFIG['domain']);

        } elseif ($_CONFIG['whitelabels'] === 'all') {
            /*
             * All domains are allowed
             */

        } elseif ($_CONFIG['whitelabels'] === 'sub') {
            /*
             * White label domains are disabled, but sub domains from the
             * $_CONFIG[domain] are allowed
             */
            if (Strings::from($domain, '.') !== $_CONFIG['domain']) {
                log_file(tr('Whitelabels are set to sub domains only, redirecting domain ":source" to ":target"', array(':source' => $_SERVER['HTTP_HOST'], ':target' => $_CONFIG['domain'])), 'manage-session', 'VERBOSE/yellow');
                redirect(PROTOCOL.$_CONFIG['domain']);
            }

        } elseif ($_CONFIG['whitelabels'] === 'list') {
            /*
             * This domain must be registered in the whitelabels list
             */
            $domain = sql_get('SELECT `domain` FROM `whitelabels` WHERE `domain` = :domain AND `status` IS NULL', true, array(':domain' => $_SERVER['HTTP_HOST']));

            if (empty($domain)) {
                log_file(tr('Whitelabel check failed because domain was not found in database, redirecting domain ":source" to ":target"', array(':source' => $_SERVER['HTTP_HOST'], ':target' => $_CONFIG['domain'])), 'manage-session', 'VERBOSE/yellow');
                redirect(PROTOCOL.$_CONFIG['domain']);
            }

        } elseif (is_array($_CONFIG['whitelabels'])) {
            /*
             * Domain must be specified in one of the array entries
             */
            if (!in_array($domain, $_CONFIG['whitelabels'])) {
                log_file(tr('Whitelabel check failed because domain was not found in configured array, redirecting domain ":source" to ":target"', array(':source' => $_SERVER['HTTP_HOST'], ':target' => $_CONFIG['domain'])), 'manage-session', 'VERBOSE/yellow');
                redirect(PROTOCOL.$_CONFIG['domain']);
            }

        } else {
            /*
             * The domain must match either $_CONFIG[domain] or the domain
             * specified in $_CONFIG[whitelabels][enabled]
             */
            if ($domain !== $_CONFIG['whitelabels']) {
                log_file(tr('Whitelabel check failed because domain did not match only configured alternative, redirecting domain ":source" to ":target"', array(':source' => $_SERVER['HTTP_HOST'], ':target' => $_CONFIG['domain'])), 'manage-session', 'VERBOSE/yellow');
                redirect(PROTOCOL.$_CONFIG['domain']);
            }

        }
    }



    /*
     * Check the cookie domain configuration to see if its valid.
     *
     * NOTE: In case whitelabel domains are used, $_CONFIG[cookie][domain]
     * must be one of "auto" or ".auto"
     */
    switch ($_CONFIG['sessions']['domain']) {
        case false:
            /*
             * This domain has no cookies
             */
            break;

        case 'auto':
            $_CONFIG['sessions']['domain'] = $domain;
            ini_set('session.cookie_domain', $domain);
            break;

        case '.auto':
            $_CONFIG['sessions']['domain'] = '.'.$domain;
            ini_set('session.cookie_domain', '.'.$domain);
            break;

        default:
            /*
             * Test cookie domain limitation
             *
             * If the configured cookie domain is different from the current domain then all cookie will inexplicably fail without warning,
             * so this must be detected to avoid lots of hair pulling and throwing arturo off the balcony incidents :)
             */
            if ($_CONFIG['sessions']['domain'][0] == '.') {
                $test = substr($_CONFIG['sessions']['domain'], 1);

            } else {
                $test = $_CONFIG['sessions']['domain'];
            }

            if (!strstr($domain, $test)) {
                notify(array('code'    => 'configuration',
                             'groups'  => 'developers',
                             'title'   => tr('Invalid cookie domain'),
                             'message' => tr('core::startup(): Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain". Please fix $_CONFIG[cookie][domain]! Redirecting to ":domain"', array(':domain' => Strings::startsNotWith($_CONFIG['sessions']['domain'], '.'), ':cookie_domain' => $_CONFIG['sessions']['domain'], ':current_domain' => $domain))));

                redirect(PROTOCOL.Strings::startsNotWith($_CONFIG['sessions']['domain'], '.'));
            }

            ini_set('session.cookie_domain', $_CONFIG['sessions']['domain']);
            unset($test);
            unset($length);
    }



    /*
     * Set session and cookie parameters
     */
    try {
        if ($_CONFIG['sessions']['enabled']) {
            /*
             * Force session cookie configuration
             */
            ini_set('session.gc_maxlifetime' , $_CONFIG['sessions']['timeout']);
            ini_set('session.cookie_lifetime', $_CONFIG['sessions']['lifetime']);
            ini_set('session.use_strict_mode', $_CONFIG['sessions']['strict']);
            ini_set('session.name'           , $_CONFIG['sessions']['cookie_name']);
            ini_set('session.cookie_httponly', $_CONFIG['sessions']['http']);
            ini_set('session.cookie_secure'  , $_CONFIG['sessions']['secure']);
            ini_set('session.cookie_samesite', $_CONFIG['sessions']['same_site']);
            ini_set('session.use_strict_mode', $_CONFIG['sessions']['strict']);

            if ($_CONFIG['sessions']['check_referrer']) {
                ini_set('session.referer_check', $domain);
            }

            if (Debug::enabled() or !$_CONFIG['cache']['http']['enabled']) {
                 ini_set('session.cache_limiter', 'nocache');

            } else {
                if ($_CONFIG['cache']['http']['enabled'] === 'auto') {
                    ini_set('session.cache_limiter', $_CONFIG['cache']['http']['php_cache_limiter']);
                    ini_set('session.cache_expire' , $_CONFIG['cache']['http']['php_cache_expire']);
                }
            }



            /*
             * Do not send cookies to crawlers!
             */
            if (isset_get($core->register['session']['client']['type']) === 'crawler') {
                log_file(tr('Crawler ":crawler" on URL ":url"', array(':crawler' => $core->register['session']['client'], ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])), 'manage-session', 'white');

            } else {
                /*
                 * Setup session handlers
                 */
                switch ($_CONFIG['sessions']['handler']) {
                    case false:
                        file_ensure_path(ROOT.'data/cookies/');
                        ini_set('session.save_path', ROOT.'data/cookies/');
                        break;

                    case 'sql':
                        /*
                         * Store session data in MySQL
                         */
                        load_libs('sessions-sql');
                        session_set_save_handler('sessions_sql_open', 'sessions_sql_close', 'sessions_sql_read', 'sessions_sql_write', 'sessions_sql_destroy', 'sessions_sql_gc', 'sessions_sql_create_sid');
                        register_shutdown_function('session_write_close');

                    case 'mc':
                        /*
                         * Store session data in memcached
                         */
                        load_libs('sessions-mc');
                        session_set_save_handler('sessions_memcached_open', 'sessions_memcached_close', 'sessions_memcached_read', 'sessions_memcached_write', 'sessions_memcached_destroy', 'sessions_memcached_gc', 'sessions_memcached_create_sid');
                        register_shutdown_function('session_write_close');

                    case 'mm':
                        /*
                         * Store session data in shared memory
                         */
                        load_libs('sessions-mm');
                        session_set_save_handler('sessions_mm_open', 'sessions_mm_close', 'sessions_mm_read', 'sessions_mm_write', 'sessions_mm_destroy', 'sessions_mm_gc', 'sessions_mm_create_sid');
                        register_shutdown_function('session_write_close');
                }



                /*
                 * Set cookie, but only if page is not API and domain has
                 * cookie configured
                 */
                if ($_CONFIG['sessions']['euro_cookies'] and empty($_COOKIE[$_CONFIG['sessions']['cookie_name']])) {
                    load_libs('geo,geoip');

                    if (geoip_is_european()) {
                        /*
                         * All first visits to european countries require cookie permissions given!
                         */
                        $_SESSION['euro_cookie'] = true;
                        return;
                    }
                }

                if (!Core::getCallType('api')) {
                    /*
                     *
                     */
                    try {
                        if (isset($_COOKIE[$_CONFIG['sessions']['cookie_name']])) {
                            if (!is_string($_COOKIE[$_CONFIG['sessions']['cookie_name']]) or !preg_match('/[a-z0-9]{22,128}/i', $_COOKIE[$_CONFIG['sessions']['cookie_name']])) {
                                log_file(tr('Received invalid cookie ":cookie", dropping', array(':cookie' => $_COOKIE[$_CONFIG['sessions']['cookie_name']])), 'manage-session', 'yellow');
                                unset($_COOKIE[$_CONFIG['sessions']['cookie_name']]);
                                $_POST = array();

                                /*
                                 * Received cookie but it didn't pass
                                 * Start a new session without a cookie
                                 */
                                session_start();

                            } elseif (!file_exists(ROOT.'data/cookies/sess_'.$_COOKIE[$_CONFIG['sessions']['cookie_name']])) {
                                /*
                                 * Cookie code is valid, but it doesn't exist.
                                 *
                                 * Start a session with this non-existing cookie. Rename
                                 * our session after the cookie, as deleting the cookie
                                 * from the browser turned out to be problematic to say
                                 * the least
                                 */
                                log_file(tr('Received non existing cookie ":cookie", recreating', array(':cookie' => $_COOKIE[$_CONFIG['sessions']['cookie_name']])), 'manage-session', 'white');

                                session_start();

                                if ($_CONFIG['sessions']['notify_expired']) {
                                    html_flash_set(tr('Your browser cookie was expired, or does not exist. You may have to sign in again'), 'warning');
                                }

                                $_POST = array();

                            } else {
                                /*
                                 * Cookie valid and found.
                                 * Start a normal session with whit cookie
                                 */
                                session_start();
                            }

                        } else {
                            /*
                             * No cookie received, start a fresh session
                             */
                            session_start();
                        }

                    }catch(Exception $e) {
                        /*
                         * Session startup failed. Clear session and try again
                         */
                        try {
                            session_regenerate_id(true);

                        }catch(Exception $e) {
                            /*
                             * Woah, something really went wrong..
                             *
                             * This may be
                             * headers already sent (the $core->register['script'] file has a space or BOM at the beginning maybe?)
                             * permissions of PHP session directory?
                             */
// :TODO: Add check on $core->register['script'] file if it contains BOM!
                            throw new CoreException('startup-webpage(): session start and session regenerate both failed, check PHP session directory', $e);
                        }
                    }

                    if ($_CONFIG['security']['url_cloaking']['enabled'] and $_CONFIG['security']['url_cloaking']['strict']) {
                        /*
                         * URL cloaking was enabled and requires strict checking.
                         *
                         * Ensure that we have a cloaked URL users_id and that it
                         * matches the sessions users_id
                         *
                         * Only check cloaking rules if we are NOT displaying a
                         * system page
                         */
                        if (!Core::getCallType('system')) {
                            if (empty($core->register['url_cloak_users_id'])) {
                                throw new CoreException(tr('startup-webpage(): Failed cloaked URL strict checking, no cloaked URL users_id registered'), 403);
                            }

                            if ($core->register['url_cloak_users_id'] !== $_SESSION['user']['id']) {
                                throw new CoreException(tr('startup-webpage(): Failed cloaked URL strict checking, cloaked URL users_id ":cloak_users_id" did not match the users_id ":session_users_id" of this session', array(':session_users_id' => $_SESSION['user']['id'], ':cloak_users_id' => $core->register['url_cloak_users_id'])), 403);
                            }
                        }
                    }

                    if ($_CONFIG['sessions']['regenerate_id']) {
                        if (isset($_SESSION['created']) and (time() - $_SESSION['created'] > $_CONFIG['sessions']['regenerate_id'])) {
                            /*
                             * Use "created" to monitor session id age and
                             * refresh it periodically to mitigate attacks on
                             * sessions like session fixation
                             */
                            session_regenerate_id(true);
                            $_SESSION['created'] = time();
                        }
                    }

                    if ($_CONFIG['sessions']['lifetime']) {
                        if (isset($_SESSION['last_activity']) and (time() - $_SESSION['last_activity'] > $_CONFIG['sessions']['lifetime'])) {
                            /*
                             * Session expired!
                             */
                            session_unset();
                            session_destroy();
                            session_start();
                            session_regenerate_id(true);
                        }
                    }



                    /*
                     * Set last activity, and first_visit variables
                     */
                    $_SESSION['last_activity'] = time();

                    if (isset($_SESSION['first_visit'])) {
                        if ($_SESSION['first_visit']) {
                            $_SESSION['first_visit']--;
                        }

                    } else {
                        $_SESSION['first_visit'] = 1;
                    }



                    /*
                     * Auto extended sessions?
                     */
                    check_extended_session();



                    /*
                     * Set users timezone
                     */
                    if (empty($_SESSION['user']['timezone'])) {
                        $_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];

                    } else {
                        try {
                            $check = new DateTimeZone($_SESSION['user']['timezone']);

                        }catch(Exception $e) {
                            /*
                             * Timezone invalid for this user. Notify
                             * developers, and fix timezone for user
                             */
                            $_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];

                            load_libs('user');
                            user_update($_SESSION['user']);

                            $e = new CoreException(tr('core::manage_session(): Reset timezone for user ":user" to ":timezone"', array(':user' => name($_SESSION['user']), ':timezone' => $_SESSION['user']['timezone'])), $e);
                            $e->makeWarning(true);
                            notify($e, true, false);
                        }
                    }
                }

                if (empty($_SESSION['init'])) {
                    /*
                     * Initialize the session
                     */
                    $_SESSION['init']         = time();
                    $_SESSION['first_domain'] = $domain;
// :TODO: Make a permanent fix for this isset_get() use. These client, location, and language indices should be set, but sometimes location is NOT set for unknown reasons. Find out why it is not set, and fix that instead!
                    $_SESSION['client']       = isset_get($core->register['session']['client']);
                    $_SESSION['mobile']       = isset_get($core->register['session']['mobile']);
                    $_SESSION['location']     = isset_get($core->register['session']['location']);
                    $_SESSION['language']     = isset_get($core->register['session']['language']);
                }
            }

            if (!isset($_SESSION['cache'])) {
                $_SESSION['cache'] = array();
            }
        }

        $_SESSION['domain'] = $domain;

    }catch(Exception $e) {
        if ($e->getRealCode() == 403) {
            log_file($e->getMessage(), 403, 'yellow');
            $core->register['page_show'] = 403;

        } else {
            if (!is_writable(session_save_path())) {
                throw new CoreException('core::manage_session(): Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
            }

            throw new CoreException('core::manage_session(): Session startup failed', $e);
        }
    }

    http_set_ssl_default_context();

}catch(Exception $e) {
    throw new CoreException(tr('core::manage_session(): Failed'), $e);
}
