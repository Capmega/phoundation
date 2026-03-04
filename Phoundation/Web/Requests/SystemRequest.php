<?php

/**
 * Class SystemRequest
 *
 * Web request handling class that can execute system HTTP code pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\EnvironmentNotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Pages\Template;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Exception\SystemPageNotFoundException;
use Phoundation\Web\Requests\Interfaces\SystemRequestInterface;
use Phoundation\Web\Routing\Route;
use Phoundation\Web\Web;
use Throwable;


class SystemRequest implements SystemRequestInterface
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
     * @param array $variables The variables that will be passed to the requested system page
     *
     * @return never
     */
    protected function executePage(array $variables): never
    {
        static $retry = false;

        switch (Core::getExposePhoundation()) {
            case 'full':
                $phoundation = '<address>Phoundation ' . Core::PHOUNDATION_VERSION . '</address>';
                break;

            case 'limited':
                $phoundation = '<address>Phoundation</address>';
                break;

            case 'fake':
                // TODO Implement proper fake version for this that won't change with each page reload
                $phoundation = '<address>Phoundation 4.11.1</address>';
                break;

            default:
                $phoundation = '';
        }

        Arrays::default($variables, 'code'   , 500);
        Arrays::default($variables, 'title'  , tr('unspecified'));
        Arrays::default($variables, 'message', tr('unspecified'));

        if ($phoundation) {
            Arrays::default($variables, 'details', $phoundation);
        }

        // Determine the request path
        $request_path = match (Request::getRequestType()) {
            EnumRequestTypes::api  => DIRECTORY_WEB . 'api/',
            EnumRequestTypes::ajax => DIRECTORY_WEB . 'ajax/',
            default                => DIRECTORY_WEB . 'pages/', // HTML plus anything we do not know gets an HTML page
        };

        try {
            // Execute the system page request
            Request::setSystem(true);
            Request::set($variables['message'], 'message');
            Request::executeAndFlush($request_path . 'system/' . $variables['code'] . '.php', true);
            exit();

        } catch (SystemPageNotFoundException $e) {
            if ($retry) {
                // We already tried rebuilding, it still does not exist, so fail instead
                static::displayTemplate($variables, $e);

            } else {
                // A system page that does not exist? Try rebuilding web-cache and retry, then we can fail
                $retry = true;
                static::rebuildWebCacheAndReExecute($variables, $e);
            }

        } catch (Throwable $e) {
            static::displayTemplate($variables, $e);
        }
    }


    /**
     * Executes the specified request for a system page
     *
     * @param int            $http_code          The system page to execute. If specified as a negative number, the page will be executed forcibly, even if
     *                                           debug mode is enabled
     * @param Throwable|null $e           [null] The (optional) exception that caused this system page to be executed
     * @param string|null    $message     [null] The optional user-visible message to add to this system page
     * @param string|null    $log_message [null] The optional log-only message to add to this system page
     *
     * @return never
     */
    #[NoReturn] public function execute(int $http_code, ?Throwable $e = null, ?string $message = null, ?string $log_message = null): never
    {
        $method = 'execute' . $http_code;

        // Log warning message and exception, if specified
        if ($log_message) {
            Log::error($log_message);
        }

        if ($e) {
            Log::exception($e);
        }

        Log::warning(ts('Executing system page ":page"', [':page' => $http_code]));

        if (($http_code < 1) or ($http_code > 1000)) {
            throw new OutOfBoundsException(tr('Specified HTTP code ":code" is invalid', [
                ':code' => $http_code,
            ]));
        }

        if (!method_exists($this, $method)) {
            Log::warning(ts('Specified HTTP code ":code" does not exist', [
                ':code' => $http_code,
            ]));

            $http_code = 500;
        }

        if ($e instanceof EnvironmentNotExistsException) {
            Log::warning('Not registering request as non HTTP-200 URL, invalid environment specified');

        } else {
            if (config()->getBoolean('security.web.monitor.urls.failed', true)) {
                // Do not register anything if we are in readonly mode
                if (!Core::getReadonly()) {
                    // Do not register 404's on favicon requests
                    if (!Route::isFaviconRequest()) {
                        Incident::new()
                                ->setSeverity(EnumSeverity::low)
                                ->setType('failed-pages')
                                ->setTitle(tr('Page generated HTTP:http', [':http' => $http_code]))
                                ->setBody(tr('The page for the URL ":url" generated HTTP:http', [
                                    ':http' => $http_code,
                                    ':url'  => Request::getUrlObject()
                                ]))
                                ->setDetails([
                                    'http'           => $http_code,
                                    'url'            => Request::getUrlObject(),
                                    'remote_ip'      => Route::getRemoteIp(),
                                    'request_method' => Route::getMethod(),
                                    'headers'        => Route::getHeaders(),
                                    'cookies'        => Route::getCookies(),
                                    'get'            => GetValidator::getBackup(),
                                    'post'           => Route::getPostData(),
                                    'session'        => Session::getSource(),
                                ])
                                ->setNotifyRoles(($http_code === 401) ? null : 'developer')
                                ->save();
                    }
                }

                Log::warning('Registered request as non HTTP-200 URL');
            }
        }

        // Clear all method restrictions as we do not need them for system pages
        Request::getMethodRestrictionsObject()->clear();

        // Remove all GET and POST data to prevent any of it being used from here on out
        GetValidator::new()->clear();
        PostValidator::new()->clear();

        // Clear flash messages
        // Clear the page buffer to ensure that whatever pages have echoed so far is gone
        Session::getFlashMessagesObject()->clear();
        Response::clean();

        // Wait a small random time before execution (Between 0mS and 100mS) to avoid timing attacks on system pages
        // TODO Improve this, should be biassed randomness. System pages should always take 500mS+ random extra, no matter what. see following sources
        // https://devcodef1.com/news/1132536/basic-auth-timing-attacks
        // https://security.stackexchange.com/questions/220446/how-can-i-prevent-side-channel-attacks-against-authentication
        // https://security.stackexchange.com/questions/94432/should-i-implement-incorrect-password-delay-in-a-website-or-a-webservice
        // https://security.stackexchange.com/questions/96489/can-i-prevent-timing-attacks-with-random-delays
        // https://stackoverflow.com/questions/28395665/could-a-random-sleep-prevent-timing-attacks/28406531#28406531
        // https://blog.ircmaxell.com/2014/11/its-all-about-time.html

        Numbers::getRandomInt(1, 100000);
        $this->$method($message);
        exit();
    }


    /**
     * Will rebuild the web cache and retry executing the system page
     *
     * @param array     $variables
     * @param Throwable $e
     *
     * @return never
     */
    protected function rebuildWebCacheAndReExecute(array $variables, Throwable $e): never
    {
        if ($e instanceof SystemPageNotFoundException) {
            // A system page does not exist? Has the web cache directory been built? Rebuild it once and try again.

            Log::warning(ts('The ":code" page does not exist in the web cache directory. Trying to rebuild the web cache ', [
                ':code' => $variables['code'],
            ]));

            Web::rebuildCache();
            static::executePage($variables);
        }

        throw $e;
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
            // We do not have a nice page for this system code
            Log::warning(ts('The ":code" page failed to show with an exception, showing ":code" template message instead and logging exception below', [
                ':code' => $variables['code'],
            ]));

            Log::setBacktraceDisplay('BACKTRACE_DISPLAY_BOTH');
            Log::error($e);

            // Build and return the error page
            $_template = Template::new('system/http-error');
            $_template->getTextsObject()->setSource([
                ':h2'     => $variables['code'],
                ':h3'     => Strings::capitalize($variables['title']),
                ':p'      => $variables['message'],
                ':body'   => $variables['details'],
                ':type'   => 'warning',
                ':search' => tr('Search'),
                ':img'    => '',
                ':action' => Url::new('search/')->makeWww(),
            ]);

            echo $_template;

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
            Log::toAlternateLog('SystemRequest::execute() failed with multiple exceptions, failed to show the "' . array_get_safe($variables, 'code') . '" page, and the "' . array_get_safe($variables, 'code') . '" template. Displaying hardcoded 500 page instead. See exception below for more information.');
            Log::toAlternateLog($e->getMessage());
            Log::toAlternateLog($f->getMessage());
            Log::toAlternateLog($g->getMessage());
        }
    }


    /**
     * Show the 401 - UNAUTHORIZED page (actually will redirect to sign-in page)
     *
     * @param string|null $message The optional message to use, or a default message will be displayed instead
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute401(?string $message = null): never
    {
        $this->executePage([
            'code'    => 401,
            'title'   => tr('Unauthorized'),
            'message' => $message ?? tr('You need to sign-in to be able to access the specified resource'),
        ]);
    }


    /**
     * Show the 403 - FORBIDDEN page
     *
     * @param string|null $message The optional message to use, or a default message will be displayed instead
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute403(?string $message = null): never
    {
        $this->executePage([
            'code'    => 403,
            'title'   => tr('Forbidden'),
            'message' => $message ?? tr('You do not have access to the requested URL on this server'),
        ]);
    }


    /**
     * Show the 404 - NOT FOUND page
     *
     * @param string|null $message The optional message to use, or a default message will be displayed instead
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute404(?string $message = null): never
    {
        Log::warning(ts('Found no applicable routes or webserver called for 404, testing for hacks'));

        // Test the URI for known hacks. If so, apply configured response
        if (config()->getArrayBoolean('web.route.known-hacks', false)) {
            Log::warning(ts('Applying known hacking rules'));

            foreach (config()->getArray('web.route.known-hacks') as $hacks) {
                // TODO Fix this. This is old code and the specified method does not even exist anymore
                static::try($hacks['regex'], array_get_safe($hacks, 'url'), array_get_safe($hacks, 'flags'));
            }
        }

        // No hack detected, execute the 404 page.
        $this->executePage([
            'code'    => 404,
            'title'   => tr('Not found'),
            'message' => $message ?? tr('The requested URL does not exist on this server'),
        ]);
    }


    /**
     * Show the 410 - GONE page
     *
     * @param string|null $message The optional message to use, or a default message will be displayed instead
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute410(?string $message = null): never
    {
        $this->executePage([
            'code'    => 410,
            'title'   => tr('Forbidden'),
            'message' => $message ?? tr('The requested resource is no longer available'),
        ]);
    }


    /**
     * Show the 500 - Internal Server Error page
     *
     * @param string|null $message The optional message to use, or a default message will be displayed instead
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute500(?string $message = null): never
    {
        $this->executePage([
            'code'    => 500,
            'title'   => tr('Internal Server Error'),
            'message' => $message ?? tr('The server encountered an unexpected condition that prevented it from fulfilling the request'),
        ]);
    }


    /**
     * Show the 503 - Service unavailable page
     *
     * @param string|null $message The optional message to use, or a default message will be displayed instead
     *
     * @return never
     * @see Route::exit()
     * @see Route::add()
     */
    #[NoReturn] protected function execute503(?string $message = null): never
    {
        $this->executePage([
            'code'    => 503,
            'title'   => tr('Service unavailable'),
            'message' => $message ?? tr('The server is currently unable to handle the request due to a temporary overload or scheduled maintenance'),
        ]);
    }
}
