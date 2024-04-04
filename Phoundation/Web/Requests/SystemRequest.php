<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Traits\TraitNew;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Pages\Template;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Non200Urls\Non200Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Routing\Route;
use Throwable;


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
class SystemRequest
{
    use TraitNew;


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
            Request::execute($request_path . 'system/' . $variables['code'] . '.php');
            exit();

        } catch (Throwable $e) {
            if ($e->getCode() === 'not-exists') {
                // We don't have a nice page for this system code
                Log::warning(tr('The system/:code page does not exist, showing basic :code message instead', [
                    ':code' => $variables['code'],
                ]));

                echo Template::new('system/http-error')->setSource([
                                                                       ':h2'     => $variables['code'],
                                                                       ':h3'     => Strings::capitalize($variables['title']),
                                                                       ':p'      => $variables['message'],
                                                                       ':body'   => $variables['details'],
                                                                       ':type'   => 'warning',
                                                                       ':search' => tr('Search'),
                                                                       ':action' => UrlBuilder::getWww('search/'),
                                                                   ])->render();

                exit();
            }

            // Something crashed whilst trying to execute the system page
            Log::warning(tr('The ":code" page failed to show with an exception, showing basic ":code" message instead and logging exception below', [
                ':code' => $variables['code'],
            ]));

            Log::setBacktraceDisplay('BACKTRACE_DISPLAY_BOTH');
            Log::error($e);

            exit($variables['code'] . ' - ' . $variables['title']);
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

        if (Config::getBoolean('security.web.monitor.non-200-urls', true)) {
            Non200Url::new()->generate($http_code)->save();
            Log::warning('Registered request as non HTTP-200 URL');
        }

        Log::warning($message);
        Log::warning($e);

        // Remove all GET and POST data to prevent any of it being used from here on out
        GetValidator::new()->clear();
        PostValidator::new()->clear();

        // Clear flash messages
        // Clear the page buffer to ensure that whatever pages have echoed so far is gone
        Session::getFlashMessages()->clear();
        Response::clean();

        // Wait a small random time before execution (Between 0mS and 100mS) to avoid timing attacks on system pages
        Numbers::getRandomInt(1, 100000);
        $this->$method();
        exit();
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
        Log::warning(tr('Found no routes for known pages, testing for hacks'));

        // Test the URI for known hacks. If so, apply configured response
        if (Config::get('web.route.known-hacks', false)) {
            Log::warning(tr('Applying known hacking rules'));

            foreach (Config::get('web.route.known-hacks') as $hacks) {
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
