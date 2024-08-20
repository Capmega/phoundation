<?php

/**
 * Class SystemRequest
 *
 * Web request handling class that can execute system HTTP code pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\EnvironmentNotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Pages\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Non200Urls\Non200Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Exception\SystemPageNotFoundException;
use Phoundation\Web\Routing\Route;
use Phoundation\Web\Web;
use Throwable;


class SystemRequest
{
    use TraitStaticMethodNew;

    /**
     * Show the 400 - BAD REQUEST page
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute400(): never
    {
        $this->executePage([
            'code'    => 400,
            'title'   => tr('Bad request'),
            'message' => tr('Server cannot or will not process the request because of incorrect information sent by client'),
        ]);
    }


    /**
     * Execute the specified system page
     *
     * @param array $variables
     *
     * @return never
     */
    protected function executePage(array $variables): never
    {
        Arrays::default($variables, 'code', 500);
        Arrays::default($variables, 'title', 'unspecified');
        Arrays::default($variables, 'message', 'unspecified');
        Arrays::default($variables, 'details', ((Config::getBoolString('security.expose.phoundation', 'limited')) ? '<address>Phoundation ' . Core::FRAMEWORK_CODE_VERSION . '</address>' : ''));

        // Determine the request path
        $request_path = match (Request::getRequestType()) {
            EnumRequestTypes::api  => DIRECTORY_WEB . 'api/',
            EnumRequestTypes::ajax => DIRECTORY_WEB . 'ajax/',
            default                => DIRECTORY_WEB . 'pages/', // HTML plus anything we don't know gets an HTML page
        };

        try {
            // Execute the system page request
            Request::setSystem(true);
            Request::executeAndFlush($request_path . 'system/' . $variables['code'] . '.php', true);
            exit();

        } catch (Throwable $e) {
            static::rebuildWebCacheAndReExecute($variables, $e);
            static::displayTemplate($variables, $e);
        }
    }


    /**
     * @param int            $http_code
     * @param Throwable|null $e
     * @param string|null    $message
     *
     * @return never
     */
    #[NoReturn] public function execute(int $http_code, ?Throwable $e = null, ?string $message = null): never
    {
        if (($http_code < 1) or ($http_code > 1000)) {
            throw new OutOfBoundsException(tr('Specified HTTP code ":code" is invalid', [
                ':code' => $http_code,
            ]));
        }

        $method = 'execute' . $http_code;

        if (!method_exists($this, $method)) {
            Log::warning(tr('Specified HTTP code ":code" does not exist', [
                ':code' => $http_code,
            ]));

            $http_code = 500;
        }

        Log::warning(tr('Executing system page ":page"', [':page' => $http_code]));

        if ($e instanceof EnvironmentNotExistsException) {
            Log::warning('Not registering request as non HTTP-200 URL, invalid environment specified');

        } else {
            if (Config::getBoolean('security.web.monitor.urls.non-200', true)) {
                Non200Url::new()
                         ->generate($http_code)
                         ->save();
                Log::warning('Registered request as non HTTP-200 URL');
            }
        }

        // Log warning message and exception, if specified
        if ($message) {
            Log::warning($message);
        }

        if ($e) {
            Log::warning($e);
        }

        // Remove all GET and POST data to prevent any of it being used from here on out
        GetValidator::new()->clear();
        PostValidator::new()->clear();

        // Clear flash messages
        // Clear the page buffer to ensure that whatever pages have echoed so far is gone
        Session::getFlashMessagesObject()->clear();
        Response::clean();

        // Wait a small random time before execution (Between 0mS and 100mS) to avoid timing attacks on system pages
        Numbers::getRandomInt(1, 100000);
        $this->$method();
        exit();
    }


    /**
     * Will rebuild the web cache and retry executing the system page
     *
     * @param array     $variables
     * @param Throwable $e
     *
     * @return void
     */
    protected function rebuildWebCacheAndReExecute(array $variables, Throwable $e): void
    {
        static $rebuild = false;
        if (($e instanceof SystemPageNotFoundException) and !$rebuild) {
            // A system page doesn't exist? Has the web cache directory been built? Rebuild it once and try again.
            $rebuild = true;
            try {
                Log::warning(tr('The ":code" page does not exist in the web cache directory. Trying to rebuild the web cache ', [
                    ':code' => $variables['code'],
                ]));
                Web::rebuildCache();
                static::executePage($variables);

            } catch (Throwable $e) {
                // Nah, didn't solve the issue
                if (!($e instanceof SystemPageNotFoundException)) {
                    // A different issue?
                    Log::error(tr('Rebuilding web cache failed with exception below'));
                    Log::error($e);
                }
            }
        }
    }


    /**
     * Will try to display a template system page
     *
     * @param array      $variables
     * @param \Throwable $e
     *
     * @return never
     */
    protected function displayTemplate(array $variables, Throwable $e): never
    {
        // System page failed to display? That is weird... Display an exception page template instead
        try {
            // We don't have a nice page for this system code
            Log::warning(tr('The ":code" page failed to show with an exception, showing ":code" template message instead and logging exception below', [
                ':code' => $variables['code'],
            ]));
            Log::setBacktraceDisplay('BACKTRACE_DISPLAY_BOTH');
            Log::error($e);
            echo Template::new('system/http-error')
                         ->setSource([
                             ':h2'     => $variables['code'],
                             ':h3'     => Strings::capitalize($variables['title']),
                             ':p'      => $variables['message'],
                             ':body'   => $variables['details'],
                             ':type'   => 'warning',
                             ':search' => tr('Search'),
                             ':action' => Url::getWww('search/'),
                         ])
                         ->render();

        } catch (Throwable $f) {
            static::displayHardcoded500($e, $f);
        }
        exit();
    }


    /**
     * Displays a hardcoded 500 page
     *
     * @param \Throwable $e
     * @param \Throwable $f
     *
     * @return void
     */
    protected function displayHardcoded500(Throwable $e, Throwable $f): void
    {
        // Something crashed whilst executing the system page template as well. Display hardcoded 500 page.
        try {
            // TODO Add support for JSON 500 page fgor API and AJAX requests
            echo '<!DOCTYPE html>
                          <html lang="en">
                            <body>
                              <h1>500 - Internal Server Error</h1>
                              <p>Something went wrong, please try again later</p>
                            </body>
                          </html>';
            Log::error('System page template failed to display, see exception below');
            Log::error($f);

        } catch (Throwable $g) {
            // Even this failed? Try to log to the system log as a last ditch effort
            Log::errorLog('SystemRequest::execute() failed with multiple exceptions, failed to show the "' . isset_get($variables['code']) . '" page, and the "' . isset_get($variables['code']) . '" template. Displaying hardcoded 500 page instead. See exception below for more information.');
            Log::errorLog($e->getMessage());
            Log::errorLog($f->getMessage());
            Log::errorLog($g->getMessage());
        }
    }


    /**
     * Show the 403 - FORBIDDEN page
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute401(): never
    {
        $this->executePage([
            'code'    => 401,
            'title'   => tr('Unauthorized'),
            'message' => tr('You need to login to access the specified resource'),
        ]);
    }


    /**
     * Show the 403 - FORBIDDEN page
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute403(): never
    {
        $this->executePage([
            'code'    => 403,
            'title'   => tr('Forbidden'),
            'message' => tr('You do not have access to the requested URL on this server'),
        ]);
    }


    /**
     * Show the 404 - NOT FOUND page
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute404(): never
    {
        Log::warning(tr('Found no applicable routes or webserver called for 404, testing for hacks'));

        // Test the URI for known hacks. If so, apply configured response
        if (Config::get('web.route.known-hacks', false)) {
            Log::warning(tr('Applying known hacking rules'));

            foreach (Config::get('web.route.known-hacks') as $hacks) {
                // TODO Fix this. This is old code and the specified method doesn't even exist anymore
                static::try($hacks['regex'], isset_get($hacks['url']), isset_get($hacks['flags']));
            }
        }

        // No hack detected, execute the 404 page.
        $this->executePage([
            'code'    => 404,
            'title'   => tr('Not found'),
            'message' => tr('The requested URL does not exist on this server'),
        ]);
    }


    /**
     * Show the 500 - Internal Server Error page
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute500(): never
    {
        $this->executePage([
            'code'    => 500,
            'title'   => tr('Internal Server Error'),
            'message' => tr('The server encountered an unexpected condition that prevented it from fulfilling the request'),
        ]);
    }


    /**
     * Show the 503 - Service unavailable page
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute503(): never
    {
        $this->executePage([
            'code'    => 503,
            'title'   => tr('Service unavailable'),
            'message' => tr('The server is currently unable to handle the request due to a temporary overload or scheduled maintenance'),
        ]);
    }
}
