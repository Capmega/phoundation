<?php

namespace Phoundation\Web;



use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Http\Http;

/**
 * Class Web
 *
 * This class is the basic web page management class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Web
{
    /**
     * Execute the specified webpage
     *
     * @param string $page
     * @return void
     */
    public static function execute(string $page): void
    {
        Arrays::ensure($params, 'message');

        // Startup the core object
        Core::startup();

        if ($get) {
            if (!is_array($get)) {
                throw new CoreException(tr('page_show(): Specified $get MUST be an array, but is an ":type"', array(':type' => gettype($get))), 'invalid');
            }

            $_GET = $get;
        }

        if (defined('LANGUAGE')) {
            $language = LANGUAGE;

        } else {
            $language = 'en';
        }

        $params['page'] = $pagename;

        if (is_numeric($pagename)) {
            /*
             * This is a system page, HTTP code. Use the page code as http code as well
             */
            Http::setStatusCode($page_name);
        }

        $core->register['real_script'] = $pagename;

        switch (Core::getCallType()) {
            case 'ajax':
                $include = ROOT.'www/'.$language.'/ajax/'.$pagename.'.php';

                if (isset_get($params['exists'])) {
                    return file_exists($include);
                }

                /*
                 * Execute ajax page
                 */
                log_file(tr('Showing ":language" language ajax page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                return include($include);

            case 'api':
                $include = ROOT.'www/api/'.(is_numeric($pagename) ? 'system/' : '').$pagename.'.php';

                if (isset_get($params['exists'])) {
                    return file_exists($include);
                }

                /*
                 * Execute ajax page
                 */
                log_file(tr('Showing ":language" language api page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                return include($include);

            case 'admin':
                $admin = '/admin';
                // no-break

            default:
                if (is_numeric($pagename)) {
                    $include = ROOT.'www/'.$language.isset_get($admin).'/system/'.$pagename.'.php';

                    if (isset_get($params['exists'])) {
                        return file_exists($include);
                    }

                    log_file(tr('Showing ":language" language system page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'warning');

                    /*
                     * Wait a small random time to avoid timing attacks on
                     * system pages
                     */
                    usleep(mt_rand(1, 250));

                } else {
                    $include = ROOT.'www/'.$language.isset_get($admin).'/'.$pagename.'.php';

                    if (isset_get($params['exists'])) {
                        return file_exists($include);
                    }

                    log_file(tr('Showing ":language" language http page ":page"', array(':page' => $pagename, ':language' => $language)), 'page-show', 'VERBOSE/cyan');
                }

                $result = include($include);

                if (isset_get($params['return'])) {
                    return $result;
                }
        }

        die();
   }



    /**
     * Return the correct current domain
     *
     * @version 2.0.7: Added function and documentation
     * @return string
     */
    function getDomain(): string
    {
        if (PLATFORM_HTTP) {
            return $_SERVER['HTTP_HOST'];
        }

        return Config::get('domain');
    }



    /**
     *
     *
     * @return void
     */
    #[NoReturn] public static function die(): void
    {
        die();
    }
}