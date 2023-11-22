<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Templates\Template;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Throwable;


/**
 * Class RouteSystem
 *
 * Core routing class that will route URL request queries to PHP scripts in the DIRECTORY_ROOT/www/LANGUAGE_CODE/ path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class RouteSystem
{
    /**
     * System page template
     *
     * @var Template $system_page
     */
    protected Template $system_page;

    /**
     * Routing parameters for this system page
     *
     * @var RoutingParameters $parameters
     */
    protected RoutingParameters $parameters;


    /**
     * RouteSystem class constructor
     */
    protected function __construct(RoutingParameters $parameters)
    {
        $this->parameters  = $parameters;
        $this->system_page = Template::page('system/error');
    }


    /**
     * Returns new RouteSystem object
     *
     * @param RoutingParameters $parameters
     * @return static
     */
    public static function new(RoutingParameters $parameters): static
    {
        return new static($parameters);
    }


    /**
     * Show the 400 - BAD REQUEST page
     *
     * @return never
     *@see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] public function execute400(): never
    {
        $this->execute([
            'code'    => 400,
            'title'   => tr('Bad request'),
            'message' => tr('Server cannot or will not process the request because of incorrect information sent by client')
        ]);
    }


    /**
     * Show the 403 - FORBIDDEN page
     *
     * @return never
     *@see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] public function execute401(): never
    {
        $this->execute([
            'code'    => 401,
            'title'   => tr('Unauthorized'),
            'message' => tr('You need to login to access the specified resource')
        ]);
    }


    /**
     * Show the 403 - FORBIDDEN page
     *
     * @return never
     *@see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] public function execute403(): never
    {
        $this->execute([
            'code'    => 403,
            'title'   => tr('Forbidden'),
            'message' => tr('You do not have access to the requested URL on this server')
        ]);
    }


    /**
     * Show the 404 - NOT FOUND page
     *
     * @return never
     *@see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] public function execute404(): never
    {
        $this->execute([
            'code'    => 404,
            'title'   => tr('Not found'),
            'message' => tr('The requested URL does not exist on this server'),
        ]);
    }


    /**
     * Show the 500 - Internal Server Error page
     *
     * @return never
     *@see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] public function execute500(): never
    {
        $this->execute([
            'code'    => 500,
            'title'   => tr('Internal Server Error'),
            'message' => tr('The server encountered an unexpected condition that prevented it from fulfilling the request'),
        ]);
    }


    /**
     * Show the 503 - Service unavailable page
     *
     * @return never
     *@see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] public function execute503(): never
    {
        $this->execute([
            'code'    => 503,
            'title'   => tr('Service unavailable'),
            'message' => tr('The server is currently unable to handle the request due to a temporary overload or scheduled maintenance'),
        ]);
    }


    /**
     * Protect exceptions generated whilst trying to execute system pages
     *
     * @param array $variables
     * @return never
     */
    #[NoReturn] protected function execute(array $variables): never
    {
        Arrays::default($variables, 'code'   , -1);
        Arrays::default($variables, 'title'  , '');
        Arrays::default($variables, 'message', '');
        Arrays::default($variables, 'details', ((Config::getBoolString('security.expose.phoundation', 'limited')) ? '<address>Phoundation ' . Core::FRAMEWORKCODEVERSION . '</address>' : ''));

        try {
            Route::execute($page ?? Config::getString('web.pages.' . strtolower(str_replace(' ', '-', $variables['title'])), 'system/' . $variables['code'] . '.php'), false, $this->parameters);

        } catch (Throwable $e) {
            if ($e->getCode() === 'not-exists') {
                // We don't have a nice page for this system code
                Log::warning(tr('The system/:code page does not exist, showing basic :code message instead', [
                    ':code' => $variables['code']
                ]));

                echo $this->system_page->render([
                    ':title' => $variables['code'] . ' - ' . Strings::capitalize($variables['title']),
                    ':h1'    => strtoupper($variables['title']),
                    ':p'     => $variables['message'],
                    ':body'  => $variables['details']
                ]);

                exit();
            }

            // Something crashed whilst trying to execute the system page
            Log::warning(tr('The ":code" page failed to show with an exception, showing basic ":code" message instead and logging exception below', [
                ':code' => $variables['code']
            ]));

            Log::setBacktraceDisplay('BACKTRACE_DISPLAY_BOTH');
            Log::error($e);

            exit($variables['code'] . ' - ' . $variables['title']);
        }
    }
}
