<?php

/**
 * Class Access
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 <copyright@capmega.com>
 * @package Phoundation\Core
 */
cclass Access
{

    /*
     * Returns true if the current session user has the specified right
     * This function will automatically load the rights for this user if
     * they are not yet in the session variable
     */
    function has_rights($rights, &$user = null)
    {
        global $_CONFIG;

        try {
            if ($user === null) {
                if (empty($_SESSION['user'])) {
                    /*
                     * No user specified and there is no session user either,
                     * so there are absolutely no rights at all
                     */
                    return false;
                }

                $user = &$_SESSION['user'];

            } elseif (!is_array($user)) {
                throw new OutOfBoundsException(tr('has_rights(): Specified user is not an array'), 'invalid');
            }

            /*
             * Dynamically load the user rights
             */
            if (empty($user['rights'])) {
                if (empty($user)) {
                    /*
                     * There is no user, so there are no rights at all
                     */
                    return false;
                }

                load_libs('user');
                $user['rights'] = user_load_rights($user);
            }

            if (empty($rights)) {
                throw new OutOfBoundsException('has_rights(): No rights specified');
            }

            if (!empty($user['rights']['god'])) {
                return true;
            }

            foreach (array_force($rights) as $right) {
                if (empty($user['rights'][$right]) or !empty($user['rights']['devil']) or !empty($fail)) {
                    if ((PLATFORM_CLI) and VERBOSE) {
                        load_libs('user');
                        log_console(tr('has_rights(): Access denied for user ":user" in page ":page" for missing right ":right"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':right' => $right)), 'yellow');
                    }

                    return false;
                }
            }

            return true;

        } catch (Exception $e) {
            throw new OutOfBoundsException('has_rights(): Failed', $e);
        }
    }


    /*
     * Returns true if the current session user has the specified group
     * This function will automatically load the groups for this user if
     * they are not yet in the session variable
     */
    function has_groups($groups, &$user = null)
    {
        global $_CONFIG;

        try {
            if ($user === null) {
                if (empty($_SESSION['user'])) {
                    /*
                     * No user specified and there is no session user either,
                     * so there are absolutely no groups at all
                     */
                    return false;
                }

                $user = &$_SESSION['user'];

            } elseif (!is_array($user)) {
                throw new OutOfBoundsException(tr('has_groups(): Specified user is not an array'), 'invalid');
            }

            /*
             * Dynamically load the user groups
             */
            if (empty($user['groups'])) {
                if (empty($user)) {
                    /*
                     * There is no user, so there are no groups at all
                     */
                    return false;
                }

                load_libs('user');
                $user['groups'] = user_load_groups($user);
            }

            if (empty($groups)) {
                throw new OutOfBoundsException('has_groups(): No groups specified');
            }

            if (!empty($user['rights']['god'])) {
                return true;
            }

            foreach (array_force($groups) as $group) {
                if (empty($user['groups'][$group]) or !empty($user['rights']['devil']) or !empty($fail)) {
                    if ((PLATFORM_CLI) and VERBOSE) {
                        load_libs('user');
                        log_console(tr('has_groups(): Access denied for user ":user" in page ":page" for missing group ":group"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':group' => $group)), 'yellow');
                    }

                    return false;
                }
            }

            return true;

        } catch (Exception $e) {
            throw new OutOfBoundsException('has_groups(): Failed', $e);
        }
    }


    /*
     * Either a user is logged in or the person will be redirected to the specified URL
     */
    function user_or_signin()
    {
        global $_CONFIG, $core;

        try {
            if (PLATFORM_CLI) {
                return $_SESSION['user'];
            }

            if (empty($_SESSION['user']['id'])) {
                /*
                 * No session
                 */
                if ($core->callType('api') or $core->callType('ajax')) {
                    json_reply(tr('Specified token ":token" has no session', array(':token' => isset_get($_POST['PHPSESSID']))), 'signin');
                }

                $url = domain(isset_get($_CONFIG['redirects']['signin'], 'signin.php') . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));

                html_flash_set('Unauthorized: Please sign in to continue');
                log_file(tr('No user, redirecting to sign in page ":url"', array(':url' => $url)), 'user-or-signin', 'VERBOSE/yellow');
                redirect($url, 302);
            }

            if (!empty($_SESSION['force_page'])) {
                /*
                 * Session is, but locked
                 * Redirect all pages EXCEPT the lock page itself!
                 */
                if (empty($_CONFIG['redirects'][$_SESSION['force_page']])) {
                    throw new OutOfBoundsException(tr('user_or_signin(): Forced page ":page" does not exist in $_CONFIG[redirects]', array(':page' => $_SESSION['force_page'])), 'not-exist');
                }

                if ($_CONFIG['redirects'][$_SESSION['force_page']] !== str_until(str_rfrom($_SERVER['REQUEST_URI'], '/'), '?')) {
                    log_file(tr('User ":user" has forced page ":page"', array(':user' => name($_SESSION['user']), ':page' => $_SESSION['force_page'])), 'user-or-signin', 'VERBOSE/yellow');
                    redirect(domain($_CONFIG['redirects'][$_SESSION['force_page']] . '?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
                }
            }

            /*
             * Is user restricted to a page? if so, keep him there
             */
            if (empty($_SESSION['lock']) and !empty($_SESSION['user']['redirect'])) {
                if (str_from($_SESSION['user']['redirect'], '://') != $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) {
                    log_file(tr('User ":user" has is restricted to page ":page"', array(':user' => name($_SESSION['user']), ':page' => $_SESSION['user']['redirect'])), 'user-or-signin', 'VERBOSE/yellow');
                    redirect(domain($_SESSION['user']['redirect']));
                }
            }

            return $_SESSION['user'];

        } catch (Exception $e) {
            throw new OutOfBoundsException('user_or_signin(): Failed', $e);
        }
    }


    /*
     * The current user has the specified rights, or will be redirected or get shown "access denied"
     */
    function rights_or_access_denied($rights, $url = null)
    {
        global $_CONFIG;

        try {
            if (!$rights) {
                return true;
            }

            user_or_signin();

            if (PLATFORM_CLI or has_rights($rights)) {
                /*
                 * We're on CLI or the user has the required rights
                 */
                return $_SESSION['user'];
            }

            /*
             * If user has no admin permissions we're not even showing 403, we're
             * simply showing the signin page
             */
            if (in_array('admin', array_force($rights))) {
                redirect(domain(isset_get($url, $_CONFIG['redirects']['signin'])));
            }

            log_file(tr('User ":user" is missing one or more of the rights ":rights"', array(':user' => name($_SESSION['user']), ':rights' => $rights)), 'rights-or-access-denied', 'yellow');
            page_show(403);

        } catch (Exception $e) {
            throw new OutOfBoundsException('rights_or_access_denied(): Failed', $e);
        }
    }


    /*
     * The current user has the specified groups, or will be redirected or get shown "access denied"
     */
    function groups_or_access_denied($groups)
    {
        global $_CONFIG;

        try {
            user_or_signin();

            if (PLATFORM_CLI or has_groups($groups)) {
                return $_SESSION['user'];
            }

            if (in_array('admin', array_force($groups))) {
                redirect(domain($_CONFIG['redirects']['signin']));
            }

            page_show($_CONFIG['redirects']['access-denied']);

        } catch (Exception $e) {
            throw new OutOfBoundsException('groups_or_access_denied(): Failed', $e);
        }
    }


    /*
     * Either a user is logged in or  the person will be shown specified page.
     */
    function user_or_page($page)
    {
        if (empty($_SESSION['user'])) {
            page_show($page);
            return false;
        }

        return $_SESSION['user'];
    }


    /*
     * Return $with_rights if the current user has the specified rights
     * Return $without_rights if not
     */
    function return_with_rights($rights, $with_rights, $without_rights = null)
    {
        try {
            if (has_rights($rights)) {
                return $with_rights;
            }

            return $without_rights;

        } catch (Exception $e) {
            throw new OutOfBoundsException('return_with_rights(): Failed', $e);
        }
    }


    /*
     * Return $with_groups if the current user is member of the specified groups
     * Return $without_groups if not
     */
    function return_with_groups($groups, $with_groups, $without_groups = null)
    {
        try {
            if (has_groups($groups)) {
                return $with_groups;
            }

            return $without_groups;

        } catch (Exception $e) {
            throw new OutOfBoundsException('return_with_groups(): Failed', $e);
        }
    }

}