<?php

namespace Phoundation\Web;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Templates\Template;
use Throwable;



/**
 * Class RouteSystem
 *
 * Core routing class that will route URL request queries to PHP scripts in the PATH_ROOT/www/LANGUAGE_CODE/ path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class RouteSystem
{
    /**
     * Singleton
     *
     * @var RouteSystem $instance
     */
    protected static RouteSystem $instance;

    /**
     * System page template
     *
     * @var Template $system_page
     */
    protected static Template $system_page;



    /**
     * RouteSystem class constructor
     */
    protected function __construct()
    {
        // Initialize the template
        self::$system_page = Template::page('system/error');
    }



    /**
     * Singleton access
     *
     * @return RouteSystem
     */
    public static function getInstance(): RouteSystem
    {
        if (!isset(self::$instance)) {
            self::$instance = new RouteSystem();
        }

        return self::$instance;
    }



    /**
     * Show the 400 - BAD REQUEST page
     *
     * @see Route::add()
     * @see Route::shutdown()
     * @return void
     */
    #[NoReturn] public static function execute400(): void
    {
        self::execute([
            'code'    => 400,
            'title'   => tr('bad request'),
            'message' => tr('Server cannot or will not process the request because of incorrect information sent by client')
        ]);
    }



    /**
     * Show the 403 - FORBIDDEN page
     *
     * @see Route::add()
     * @see Route::shutdown()
     * @return void
     */
    #[NoReturn] public static function execute401(): void
    {
        self::execute([
            'code'    => 401,
            'title'   => tr('Unauthorized'),
            'message' => tr('You need to login to access the specified resource')
        ]);
    }



    /**
     * Show the 403 - FORBIDDEN page
     *
     * @see Route::add()
     * @see Route::shutdown()
     * @return void
     */
    #[NoReturn] public static function execute403(): void
    {
        self::execute([
            'code'    => 403,
            'title'   => tr('forbidden'),
            'message' => tr('You do not have access to the requested URL on this server')
        ]);
    }



    /**
     * Show the 404 - NOT FOUND page
     *
     * @see Route::add()
     * @see Route::shutdown()
     * @return void
     */
    #[NoReturn] public static function execute404(): void
    {
        self::execute([
            'code'    => 404,
            'title'   => tr('forbidden'),
            'message' => tr('The requested URL does not exist on this server'),
        ]);
    }



    /**
     * Protect exceptions generated whilst trying to execute system pages
     *
     * @param array $variables
     * @return void
     */
    #[NoReturn] protected static function execute(array $variables): void
    {
        self::getInstance();

        Arrays::default($variables, 'code'   , -1);
        Arrays::default($variables, 'title'  , '');
        Arrays::default($variables, 'message', '');
        Arrays::default($variables, 'details', ((Config::get('security.expose.phoundation', false)) ? '<address>Phoundation ' . Core::FRAMEWORKCODEVERSION . '</address>' : ''));

        try {
            Route::execute($page ?? Config::get('web.pages.' . strtolower(str_replace(' ', '-', $variables['title'])), 'system/' . $variables['code'] . '.php'), false);

        } catch (Throwable $e) {
            if ($e->getCode() === 'not-exists') {
                // We don't have a nice page for this system code
                Log::warning(tr('The system/:code page does not exist, showing basic :code message instead', [
                    ':code' => $variables['code']
                ]));

                echo self::$system_page->render([
                    ':title' => $variables['code'] . ' - ' . Strings::capitalize($variables['title']),
                    ':h1'    => strtoupper($variables['title']),
                    ':p'     => $variables['message'],
                    ':body'  => $variables['details']
                ]);

                die();
            }

            // Something crashed whilst trying to execute the system page
            Log::warning(tr('The ":code" page failed to show with an exception, showing basic ":code" message instead and logging exception below', [
                ':code' => $variables['code']
            ]));

            Log::setBacktraceDisplay('BACKTRACE_DISPLAY_BOTH');
            Log::error($e);

            die($variables['code'] . ' - ' . $variables['title']);
        }
    }
}
