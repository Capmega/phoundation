<?php

namespace Phoundation\Web;

use Exception;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Api\ApiInterface;
use Phoundation\Cache\Cache;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Date\Date;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Utils\Json;
use Phoundation\Web\Exception\WebException;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Exception\HttpException;
use Phoundation\Web\Http\Flash;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\FlashMessages\FlashMessages;
use Phoundation\Web\Http\Html\Menus\Menus;
use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\Http\Html\Template\TemplatePage;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Routing\Route;
use Phoundation\Web\Routing\RoutingParameters;
use Throwable;



/**
 * Class Page
 *
 * This class contains methods to assist in building web pages
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Page
{
    /**
     * Singleton
     *
     * @var Page $instance
     */
    protected static Page $instance;

    /**
     * The server filesystem restrictions
     *
     * @var Restrictions $restrictions
     */
    protected static Restrictions $restrictions;

    /**
     * The TemplatePage class that builds the UI
     *
     * @var TemplatePage $template_page
     */
    protected static TemplatePage $template_page;

    /**
     * The template class that builds the UI
     *
     * @var Template $template
     */
    protected static Template $template;

    /**
     * The Phoundation API interface
     *
     * @var ApiInterface $api_interface
     */
    protected static ApiInterface $api_interface;

    /**
     * The flash object for this user
     *
     * @var Flash|null
     */
    protected static ?Flash $flash = null;

    /**
     * Tracks if static::sendHeaders() sent headers already or not.
     *
     * @note IMPORTANT: Since flush() and ob_flush() will NOT lock headers until the buffers are actually flushed, and
     *                  they will neither actually flush the buffers as long as the process is running AND the buffers
     *                  are not full yet, weird things can happen. With a buffer of 4096 bytes (typically), echo 100
     *                  characters, and then execute static::sendHeaders(), then ob_flush() and flush() and headers_sent()
     *                  will STILL be false, and REMAIN false until the buffer has reached 4096 characters OR the
     *                  process ends. This variable just keeps track if static::sendHeaders() has been executed (and it
     *                  won't execute again), but headers might still be sent out manually. This is rather messed up,
     *                  because it really shows as if information was sent, the buffers are flushed, yet nothing is
     *                  actually flushed, so the headers are also not sent. This is just messed up PHP.
     * @var bool $http_headers_sent
     */
    protected static bool $http_headers_sent = false;

    /**
     * The client specified ETAG for this request
     *
     * @var string|null $etag
     */
    protected static ?string $etag = null;

    /**
     * The target of this page
     *
     * @var string|null $target
     */
    protected static ?string $target = null;

    /**
     * !DOCTYPE variable
     *
     * @var string
     */
    protected static string $doctype = 'html';

    /**
     * The browser page title
     *
     * @var string|null $page_title
     */
    protected static ?string $page_title = null;

    /**
     * The browser page description
     *
     * @var string|null $description
     */
    protected static ?string $description = null;

    /**
     * The page header title
     *
     * @var string|null $header_title
     */
    protected static ?string $header_title = null;

    /**
     * The page header subtitle
     *
     * @var string|null $header_sub_title
     */
    protected static ?string $header_sub_title = null;

    /**
     * Information that goes into the HTML header
     *
     * @var array $headers
     */
    protected static array $headers = [
        'link'       => [],
        'meta'       => [],
        'javascript' => []
    ];

    /**
     * Information that goes into the HTML footer
     *
     * @var array $footers
     */
    protected static array $footers = [
        'javascript' => []
    ];

    /**
     * The files that should be added in the header
     *
     * @var array
     */
    protected static array $header_files = [];

    /**
     * The files that should be added in the footer
     *
     * @var array
     */
    protected static array $footer_files = [];

    /**
     * The unique hash for this page
     *
     * @var string|null $hash
     */
    protected static ?string $hash = null;

    /**
     * Keeps track on if the HTML headers have been sent / generated or not
     *
     * @var bool $html_headers_sent
     */
    protected static bool $html_headers_sent = false;

    /**
     * The status code that will be returned to the client
     *
     * @var int $http_code
     */
    protected static int $http_code = 200;

    /**
     * The list of metadata that the client accepts
     *
     * @var array|null $accepts
     */
    protected static ?array $accepts = null;

    /**
     * CORS headers
     *
     * @var array $cors
     */
    protected static array $cors = [];

    /**
     * Content-type header
     *
     * @var string|null $content_type
     */
    protected static ?string $content_type = null;

    /**
     * Bread crumbs for this page
     *
     * @var BreadCrumbs|null
     */
    protected static ?BreadCrumbs $bread_crumbs = null;

    /**
     * Flash messages control
     *
     * @var FlashMessages|null
     */
    protected static ?FlashMessages $flash_messages = null;

    /**
     * If true, the template will build the <body> tag. If false, the page will have to build it itself
     *
     * @var bool $build_body
     */
    protected static bool $build_body = true;

    /**
     * Contains the routing parameters like root url, template, etc
     *
     * @var RoutingParameters $parameters
     */
    protected static RoutingParameters $parameters;

    /**
     * The menus for this page
     *
     * @var Menus $menus
     */
    protected static Menus $menus;



    /**
     * Page class constructor
     *
     * @throws Exception
     */
    protected function __construct()
    {
        static::$headers['meta']['charset']  = Config::get('languages.encoding.charset', 'UTF-8');
        static::$headers['meta']['viewport'] = Config::get('web.viewport'              , 'width=device-width, initial-scale=1, shrink-to-fit=no');
    }



    /**
     * Singleton
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }



    /**
     * Returns the current tab index and automatically increments it
     *
     * @return Restrictions
     */
    public static function getRestrictions(): Restrictions
    {
        return static::$restrictions;
    }



    /**
     * Sets the current tab index and automatically increments it
     *
     * @param Restrictions $restrictions
     * @return void
     */
    public static function setRestrictions(Restrictions $restrictions): void
    {
        static::$restrictions = $restrictions;
    }



    /**
     * Returns the current tab index and automatically increments it
     *
     * @return Menus
     */
    public static function getMenus(): Menus
    {
        if (!isset(static::$menus)) {
            // Menus have not yet been initialized, do so now.
            static::$menus = new Menus();
        }

        return static::$menus;
    }



    /**
     * Sets the current tab index and automatically increments it
     *
     * @param Menus $menus
     * @return void
     */
    public static function setMenus(Menus $menus): void
    {
        static::$menus = $menus;
    }



    /**
     * Returns page parameters specified by the router
     *
     * @return RoutingParameters
     */
    public static function getRoutingParameters(): RoutingParameters
    {
        return static::$parameters;
    }



    /**
     * Sets page parameters specified by the router
     *
     * @param RoutingParameters $parameters
     * @return void
     */
    public static function setRoutingParameters(RoutingParameters $parameters): void
    {
        static::$parameters = $parameters;

        // Set the server filesystem restrictions and template for this page
        Page::setRestrictions($parameters->getRestrictions());

        // Initialize the template
        if (!$parameters->getTemplate()) {
            if (!static::$template_page) {
                throw new OutOfBoundsException(tr('Cannot use routing parameters ":pattern", it has no template set', [
                    ':pattern' => $parameters->getPattern()
                ]));
            }
        } else {
            // Get a new template page from the specified template
            static::$template      = $parameters->getTemplateObject();
            static::$template_page = static::$template->getPage();
        }
    }



    /**
     * Returns the page flash messages
     *
     * @return FlashMessages|null
     */
    public static function getFlashMessages(): ?FlashMessages
    {
        return static::$flash_messages;
    }



    /**
     * Returns the target of this page
     *
     * @return string|null
     */
    public static function getTarget(): ?string
    {
        return static::$target;
    }



    /**
     * Returns the SEO optimized version of the project name
     *
     * @return string
     */
    public static function getProjectName():string
    {
        return str_replace('_', '-', strtolower(PROJECT));
    }



    /**
     * Sets an alternative class for the <body> tag
     *
     * @param bool $build_body
     * @return void
     */
    public static function setBuildBody(bool $build_body): void
    {
        static::$build_body = $build_body;
    }



    /**
     * Returns the alternative class for the <body> tag or if not preset, the default
     *
     * @return string|null
     */
    public static function getBuildBody(): ?string
    {
        return static::$build_body;
    }



    /**
     * Returns the request method for this page
     *
     * @param bool $default If true, if no referer is available, the current page URL will be returned instead. If
     *                      string, and no referer is available, the default string will be returned instead
     *
     * @return string|null
     */
    public static function getReferer(string|bool $default = false): ?string
    {
        $url = isset_get($_SERVER['HTTP_REFERER']);

        if ($url) {
            return $url;
        }

        if ($default) {
            // We don't have a referer, return the current URL instead
            return UrlBuilder::getWww($default);
        }

        // We got nothing...
        return null;
    }



    /**
     * Returns the request method for this page
     *
     * @return string
     */
    #[ExpectedValues(values: ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'])]
    public static function getRequestMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }



    /**
     * Returns if this request is the specified method
     *
     * @param string $method
     * @return bool
     */
    public static function isRequestMethod(#[ExpectedValues(values: ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'])] string $method): bool
    {
        return strtoupper($method) === strtoupper($_SERVER['REQUEST_METHOD']);
    }



    /**
     * Returns if this request is a POST method
     *
     * @return bool
     */
    public static function isPostRequestMethod(): bool
    {
        return static::isRequestMethod('POST');
    }



    /**
     * Return the domain for this page, or the primary domain on CLI
     *
     * @return string
     */
    public static function getDomain(): string
    {
        if (PLATFORM_HTTP) {
            return $_SERVER['HTTP_HOST'];
        }

        return Domains::getPrimary();
    }



    /**
     * Return the URL for this page
     *
     * @param bool $no_queries
     * @return string
     */
    public static function getUrl(bool $no_queries = false): string
    {
        if (PLATFORM_HTTP) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . static::getUri($no_queries);
        }

        return static::$parameters->getRootUrl();
    }



    /**
     * Return the request URI for this page
     *
     * @note On the CLI platform this method will return "/"
     * @param bool $no_queries
     * @return string
     */
    public static function getUri(bool $no_queries = false): string
    {
        if (PLATFORM_HTTP) {
            return ($no_queries ? Strings::until($_SERVER['REQUEST_URI'], '?') : $_SERVER['REQUEST_URI']);
        }

        return static::$parameters->getUri();
    }



    /**
     * Return the complete request URL for this page (WITH domain)
     *
     * @return string
     */
    public static function getRootUrl(): string
    {
        return static::$parameters->getRootUrl();
    }



    /**
     * Returns the request URI for this page (WITHOUT domain)
     *
     * @return string
     */
    public static function getRootUri(): string
    {
        $uri = static::getRootUrl();
        $uri = Strings::from($uri, '://');
        $uri = Strings::from($uri, '/');

        return $uri;
    }



    /**
     * Returns the bread crumbs for this page
     *
     * @return BreadCrumbs|null
     */
    public static function getBreadCrumbs(): ?BreadCrumbs
    {
        return static::$bread_crumbs;
    }



    /**
     * Sets the bread crumbs for this page
     *
     * @param BreadCrumbs|null $bread_crumbs
     * @return void
     */
    public static function setBreadCrumbs(?BreadCrumbs $bread_crumbs = null): void
    {
        static::$bread_crumbs = $bread_crumbs;
    }



    /**
     * Returns the current Template for this page
     *
     * @return Template
     */
    public static function getTemplate(): Template
    {
        return static::$template;
    }



    /**
     * Returns the current TemplatePage used for this page
     *
     * @return TemplatePage
     */
    public static function getTemplatePage(): TemplatePage
    {
        return static::$template_page;
    }



    /**
     * Will throw an AccessDeniedException if the current session user is "guest"
     *
     * @param string|int|null $new_target
     * @return void
     */
    public static function requiresNotGuest(string|int|null $new_target = 'sign-in'): void
    {
        if (Session::getUser()->isGuest()) {
            throw AccessDeniedException::new(tr('You do not have the required rights to view this page'))
                ->setNewTarget($new_target);
        }
    }



    /**
     * Will throw an AccessDeniedException if the current session user does not have ALL of the specified rights
     *
     * @param array|string $rights
     * @param string|int|null $missing_rights_target
     * @param string|int|null $guest_target
     * @return void
     */
    public static function requiresAllRights(array|string $rights, string|int|null $missing_rights_target = 403, string|int|null $guest_target = 401): void
    {
        static::requiresNotGuest();

        if (Session::getUser()->isGuest()) {
            throw AccessDeniedException::new(tr('You have to sign in to view this page'))
                ->setNewTarget($guest_target);
        }

        if (!Session::getUser()->hasAllRights($rights)) {
            throw AccessDeniedException::new(tr('You do not have the required rights to view this page'))
                ->setNewTarget($missing_rights_target);
        }
    }



    /**
     * Will throw an AccessDeniedException if the current session user does not have SOME of the specified rights
     *
     * @param array|string $rights
     * @param string|int|null $missing_rights_target
     * @param string|int|null $guest_target
     * @return void
     */
    public static function requiresSomeRights(array|string $rights, string|int|null $missing_rights_target = 403, string|int|null $guest_target = 401): void
    {
        static::requiresNotGuest();

        if (Session::getUser()->isGuest()) {
            throw AccessDeniedException::new(tr('You have to sign in to view this page'))
                ->setNewTarget($guest_target);
        }

        if (!Session::getUser()->hasSomeRights($rights)) {
            throw AccessDeniedException::new(tr('You do not have the required rights to view this page'))
                ->setNewTarget($missing_rights_target);
        }
    }



    /**
     * Returns true if the HTTP headers have been sent
     *
     * @return bool
     */
    public static function getHttpHeadersSent(): bool
    {
        return static::$http_headers_sent;
    }



    /**
     * Returns the status code that will be sent to the client
     *
     * @return int
     */
    public static function getHttpCode(): int
    {
        return static::$http_code;
    }



    /**
     * Sets the status code that will be sent to the client
     *
     * @param int $code
     * @return void
     */
    public static function setHttpCode(int $code): void
    {
        // Validate status code
        // TODO implement

        static::$http_code = $code;
    }



    /**
     * Returns the mimetype / content type
     *
     * @return string|null
     */
    public static function getContentType(): ?string
    {
        return static::$content_type;
    }



    /**
     * Sets the mimetype / content type
     *
     * @param string $content_type
     * @return void
     */
    public static function setContentType(string $content_type): void
    {
        // Validate status code
        // TODO implement

        static::$content_type = $content_type;
    }



    /**
     * Returns the CORS headers
     *
     * @return array
     */
    public static function getCors(): array
    {
        return static::$cors;
    }


    /**
     * Sets the status code that will be sent to the client
     *
     * @param string $origin
     * @param string $methods
     * @param string $headers
     * @return void
     */
    public static function setCors(string $origin, string $methods, string $headers): void
    {
        // Validate CORS data
        // TODO implement validation

        static::$cors = [
            'origin'  => '*.',
            'methods' => 'GET, POST',
            'headers' => ''
        ];
    }



    /**
     * Returns the current tab index and automatically increments it
     *
     * @return string
     */
    public static function getDocType(): string
    {
        return static::$doctype;
    }



    /**
     * Sets the current tab index and automatically increments it
     *
     * @param string $doctype
     * @return void
     */
    public static function setDoctype(string $doctype): void
    {
        static::$doctype = $doctype;
    }



    /**
     * Returns the browser page title
     *
     * @return string
     */
    public static function getPageTitle(): string
    {
        return static::$page_title;
    }



    /**
     * Sets the browser page title
     *
     * @param string $page_title
     * @return void
     */
    public static function setPageTitle(string $page_title): void
    {
        static::$page_title = strip_tags($page_title);
    }



    /**
     * Returns the browser page title
     *
     * @return string|null
     */
    public static function getDescription(): ?string
    {
        return static::$description;
    }



    /**
     * Sets the browser page description
     *
     * @param string|null $description
     * @return void
     */
    public static function setDescription(?string $description): void
    {
        static::$description = strip_tags($description);
    }



    /**
     * Returns the page header title
     *
     * @return string|null
     */
    public static function getHeaderTitle(): ?string
    {
        return static::$header_title;
    }



    /**
     * Sets the page header title
     *
     * @param string|null $header_title
     * @return void
     */
    public static function setHeaderTitle(?string $header_title): void
    {
        static::$header_title = $header_title;

        if (!static::$page_title) {
            static::$page_title = Config::get('project.name', 'Phoundation') . $header_title;
        }
    }



    /**
     * Returns the page header subtitle
     *
     * @return string|null
     */
    public static function getHeaderSubTitle(): ?string
    {
        return static::$header_sub_title;
    }



    /**
     * Sets the page header subtitle
     *
     * @param string|null $header_sub_title
     * @return void
     */
    public static function setHeaderSubTitle(?string $header_sub_title): void
    {
        static::$header_sub_title = $header_sub_title;
    }



    /**
     * Returns the page charset
     *
     * @return string|null
     */
    public static function getCharset(): ?string
    {
        return isset_get(static::$headers['meta']['charset']);
    }



    /**
     * Sets the page charset
     *
     * @param string|null $charset
     * @return void
     */
    public static function setCharset(?string $charset): void
    {
        static::$headers['meta']['charset'] = $charset;
    }



    /**
     * Returns the page viewport
     *
     * @return string|null
     */
    public static function getViewport(): ?string
    {
        return isset_get(static::$headers['meta']['viewport']);
    }



    /**
     * Sets the page viewport
     *
     * @param string|null $viewport
     * @return void
     */
    public static function setViewport(?string $viewport): void
    {
        static::$headers['meta']['viewport'] = $viewport;
    }



    /**
     * Executes the target specified by Route::execute()
     *
     * We have a target for the requested route. If the resource is a PHP page, then execute it. Anything else, send it
     * directly to the client
     *
     * @note Since this method required a RoutingParameters object do NOT execute this directly to execute a page, use
     * Route::execute() instead!
     *
     * @param string $target      The target file that should be executed or sent to the client
     * @param boolean $attachment If specified as true, will send the file as a downloadable attachment, to be written
     *                            to disk instead of displayed on the browser. If set to false, the file will be sent as
     *                            a file to be displayed in the browser itself.
     * @return string|null
     *
     * @see Route::execute()
     * @see Template::execute()
     */
    #[NoReturn] public static function execute(string $target, bool $attachment = false): ?string
    {
        try {
            // Startup the page and see if we can use cache
            self::startup($target);
            self::tryCache($target, $attachment);

            Core::writeRegister($target, 'system', 'script_file');
            ob_start();

            // Execute the specified target
            // Build the headers, cache output and headers together, then send the headers
            // TODO Work on the HTTP headers, lots of issues here still, like content-length!
            $output  = self::executeTarget($target);
            $headers = static::buildHttpHeaders($output, $attachment);

            if ($headers) {
                // Only cache if there are headers. If static::buildHeaders() returned null this means that the headers
                // have already been sent before, probably by a debugging function like Debug::show(). DON'T CACHE!
                Cache::write([
                    'output'  => $output,
                    'headers' => $headers,
                ], $target,'pages');

                $length = static::sendHttpHeaders($headers);
                Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);
            }

            // All done, send output to client
            $output = self::filterOutput($output);
            self::sendOutputToClient($output, $target, $attachment);

        } catch (ValidationFailedException $e) {
            // TODO Improve this uncaught validation failure handling
            Log::warning('Page did not catch the following "ValidationFailedException" warning, showing "system/400"');
            Log::warning($e);

            static::getFlashMessages()->add($e);
            Route::executeSystem(400);

        } catch (AuthenticationException $e) {
            Log::warning('Page did not catch the following "AuthenticationException" warning, showing "system/401"');
            Log::warning($e);

            static::getFlashMessages()->add($e);
            Route::executeSystem(401);

        } catch (IncidentsException $e) {
            // TODO Should we also catch AccessDenied exception here?
            Log::warning('Page did not catch the following "IncidentsException" warning, showing "system/401"');
            Log::warning($e);

            static::getFlashMessages()->add($e);
            Route::executeSystem(403);

        } catch (DataEntryNotExistsException $e) {
            Log::warning('Page did not catch the following "DataEntryNotExistsException" warning, showing "system/404"');
            Log::warning($e);

            // Show a 404 page instead
            Route::executeSystem(404);

        } catch (Exception $e) {
            Notification::new()
                ->setTitle(tr('Failed to execute ":type" page ":page" with language ":language"', [
                    ':type'     => Core::getRequestType(),
                    ':page'     => $target,
                    ':language' => LANGUAGE
                ]))
                ->setException($e)
                ->send();

            throw $e;
        }
    }



    /**
     * Ensures that this session user has all the specified rights, or a redirect will happen
     *
     * @param array|string $rights
     * @param string $target
     * @param string|null $rights_redirect
     * @param string|null $guest_redirect
     * @return void
     */
    public static function hasRightsOrRedirects(array|string $rights, string $target, ?string $rights_redirect = null, ?string $guest_redirect = null): void
    {
        if (Session::getUser()->hasAllRights($rights)) {
            return;
        }

        if (!$target) {
            // If target wasn't specified we can safely assume it's the same as the real target.
            $target = self::$target;
        }

        // Oops! Is this a system page though? System pages require no rights to be viewed.
        $system = dirname($target);
        $system = basename($system);

        if ($system === 'system') {
            // Hurrah, its a bo.. system page!
            return;
        }

        if (Session::getUser()->isGuest()) {
            // This user has no rights at all, send to sign-in page
            if (!$guest_redirect) {
                $guest_redirect = '/sign-in.html';
            }

            Incident::new()
                ->setType('401 - unauthorized')->setSeverity(Severity::low)
                ->setTitle(tr('Guest user has no access to target page ":target" (real target ":real_target"), redirecting to ":redirect"', [
                    ':target'      => Strings::from(static::$target, PATH_ROOT),
                    ':real_target' => Strings::from($target, PATH_ROOT),
                    ':redirect'    => $guest_redirect
                ]))
                ->setDetails([
                    'user'         => 0,
                    'uri'          => Page::getUri(),
                    'target'       => Strings::from(static::$target, PATH_ROOT),
                    ':real_target' => Strings::from($target, PATH_ROOT),
                    'rights'       => $rights
                ])
                ->save();

            Page::redirect($guest_redirect);
        }

        // This user is missing rights
        if (!$rights_redirect) {
            $rights_redirect = '403';
        }

        // Do the specified rights exist at all? If they aren't defined then no wonder this user doesn't have them
        if (Rights::getNotExist($rights)) {
            // One or more of the rights do not exist
            Incident::new()
                ->setType('Non existing rights')->setSeverity(in_array('admin', Session::getUser()->getMissingRights($rights)) ? Severity::high : Severity::medium)
                ->setTitle(tr('The requested rights ":rights" for target page ":target" (real target ":real_target") do not exist on this system! Redirecting to ":redirect"', [
                    ':rights'      => Strings::force(Rights::getNotExist($rights), ', '),
                    ':target'      => Strings::from(static::$target, PATH_ROOT),
                    ':real_target' => Strings::from($target, PATH_ROOT),
                    ':redirect'    => $rights_redirect
                ]))
                ->setDetails([
                    'user'           => Session::getUser()->getLogId(),
                    'uri'            => Page::getUri(),
                    'target'         => Strings::from(static::$target, PATH_ROOT),
                    ':real_target'   => Strings::from($target, PATH_ROOT),
                    'rights'         => $rights,
                    'missing_rights' => Rights::getNotExist($rights)
                ])
                ->save();

            Page::redirect($rights_redirect);
        }

        // Registered user does not have the required rights
        Incident::new()
            ->setType('403 - forbidden')
            ->setSeverity(in_array('admin', Session::getUser()->getMissingRights($rights)) ? Severity::high : Severity::medium)
            ->setTitle(tr('User ":user" does not have the required rights ":rights" for target page ":target" (real target ":real_target"), redirecting to ":redirect"', [
                ':user'        => Session::getUser()->getLogId(),
                ':rights'      => $rights,
                ':target'      => Strings::from(static::$target, PATH_ROOT),
                ':real_target' => Strings::from($target, PATH_ROOT),
                ':redirect'    => $rights_redirect
            ]))
            ->setDetails([
                'user'         => Session::getUser()->getLogId(),
                'uri'          => Page::getUri(),
                'target'       => Strings::from(static::$target, PATH_ROOT),
                ':real_target' => Strings::from($target, PATH_ROOT),
                'rights'       => $rights
            ])
            ->save();

        Page::redirect($rights_redirect);
    }



    /**
     * Return the specified URL with a redirect URL stored in $core->register['redirect']
     *
     * @note If no URL is specified, the current URL will be used
     * @param UrlBuilder|string|bool|null $url
     * @param int $http_code
     * @param int|null $time_delay
     * @return void
     *@see UrlBuilder
     * @see UrlBuilder::addQueries()
     *
     */
    #[NoReturn] public static function redirect(UrlBuilder|string|bool|null $url = null, int $http_code = 301, ?int $time_delay = null): void
    {
        if (!PLATFORM_HTTP) {
            throw new WebException(tr('Page::redirect() can only be called on web sessions'));
        }

        // Display a system error page instead?
        if (is_numeric($url)) {
            Route::executeSystem($url);
        }

        // Build URL
        $redirect = UrlBuilder::getWww($url);

        // Protect against endless redirecting.
        if (UrlBuilder::isCurrent($redirect)) {
            // POST requests may redirect to the same page as the redirect will change POST to GET
            if (!Page::isPostRequestMethod()) {
                // If the specifed redirect URL was a short code like "prev" or "referer", then it was not hard coded
                // and the system couldn't know that the short code is the same as the current URL. Redirect to domain
                // root instead
                $redirect = match ($url) {
                    'prev', 'previous', 'referer' => UrlBuilder::getCurrentDomainRootUrl(),
                    default => throw new OutOfBoundsException(tr('Will NOT redirect to ":url", its the current page and the current request method is not POST', [
                        ':url' => $redirect
                    ])),
                };
            }
        }

        if (isset_get($_GET['redirect'])) {
            // Add redirect back query
            $redirect = UrlBuilder::getWww($redirect)->addQueries(['redirect' => urlencode($_GET['redirect'])]);
        }

        /*
         * Validate the specified http_code, must be one of
         *
         * 301 Moved Permanently
         * 302 Found
         * 303 See Other
         * 307 Temporary Redirect
         */
        switch ($http_code) {
            case 0:
                // no-break
            case 301:
                $http_code = 301;
                break;
            case 302:
                // no-break
            case 303:
                // no-break
            case 307:
                // All valid
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid HTTP code ":code" specified', [
                    ':code' => $http_code
                ]));
        }

        // Redirect with time delay
        if ($time_delay) {
            Log::action(tr('Redirecting with ":time" seconds delay to url ":url"', [
                ':time' => $time_delay,
                ':url' => $redirect
            ]));

            header('Refresh: '.$time_delay.';'.$redirect, true, $http_code);
        } else {
            // Redirect immediately
            Log::information(tr('Redirecting to url ":url"', [':url' => $redirect]));
            header('Location:' . $redirect, true, $http_code);
        }

        static::die();
    }



    /**
     * Returns requested main mimetype, or if requested mimetype is accepted or not
     *
     * The function will return true if the specified mimetype is supported, or false, if not
     *
     * @see static::acceptsLanguages()
     * code
     * // This will return true
     * $result = accepts('image/webp');
     *
     * // This will return false
     * $result = accepts('image/foobar');
     *
     * // On a browser, this typically would return text/html
     * $result = accepts();
     * /code
     *
     * This would return
     * code
     * Foo...bar
     * /code
     *
     * @param string $mimetype The mimetype that hopefully is accepted by the client
     * @return mixed True if the client accepts it, false if not
     */
    public static function accepts(string $mimetype): bool
    {
        static $headers = null;

        if (!$mimetype) {
            throw new OutOfBoundsException(tr('No mimetype specified'));
        }

        if (!$headers) {
            // Cleanup the HTTP accept headers (opera aparently puts spaces in there, wtf?), then convert them to an
            // array where the accepted headers are the keys so that they are faster to access
            $headers = isset_get($_SERVER['HTTP_ACCEPT']);
            $headers = str_replace(', ', '', $headers);
            $headers = Arrays::force($headers);
            $headers = array_flip($headers);
        }

        // Return if the client supports the specified mimetype
        return isset($headers[$mimetype]);
    }



    /**
     * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list of languages / locales accepted by the HTTP client
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see accepts()
     * @note: This function is called by the startup system and its output stored in $core->register['accept_language']. There is typically no need to execute this function on any other places
     * @version 1.27.0: Added function and documentation
     *
     * @return array The list of accepted languages and locales as specified by the HTTP client
     */
    public static function acceptsLanguages(): array
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // No accept language headers were specified
            $return  = [
                '1.0' => [
                    'language' => Config::get('languages.default', 'en'),
                    'locale'   => Strings::cut(Config::get('locale.LC_ALL', 'US'), '_', '.')
                ]
            ];

        } else {
            $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $headers = Arrays::force($headers, ',');
            $default = array_shift($headers);
            $return  = [
                '1.0' => [
                    'language' => Strings::until($default, '-'),
                    'locale'   => (str_contains($default, '-') ? Strings::from($default, '-') : null)
                ]
            ];

            if (empty($return['1.0']['language'])) {
                // Specified accepts language headers contain no language
                $return['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
            }

            if (empty($return['1.0']['locale'])) {
                // Specified accept language headers contain no locale
                $return['1.0']['locale'] = Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
            }

            foreach ($headers as $header) {
                $requested =  Strings::until($header, ';');
                $requested =  [
                    'language' => Strings::until($requested, '-'),
                    'locale'   => (str_contains($requested, '-') ? Strings::from($requested, '-') : null)
                ];

                if (empty(Config::get('languages.supported', [])[$requested['language']])) {
                    continue;
                }

                $return[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($return);
        return $return;
    }



    /**
     * Returns the HTML output buffer for this page
     *
     * @return string
     */
    public static function getHtml(): string
    {
        return ob_get_contents();
    }



    /**
     * Returns the HTML unique hash
     *
     * @return string
     */
    public static function getHash(): string
    {
        return static::$hash;
    }



    /**
     * Returns if the HTML headers have been sent
     *
     * @return bool
     */
    public static function getHtmlHeadersSent(): bool
    {
        return static::$html_headers_sent;
    }



    /**
     * Returns the length HTML output buffer for this page
     *
     * @return int
     */
    public static function getContentLength(): int
    {
        return ob_get_length();
    }



    /**
     * Send the current buffer to the client
     *
     * @param string $output
     * @return void
     */
    public static function send(string $output): void
    {
        // Send output to the client
        $length = strlen($output);
        echo $output;

        ob_flush();
        flush();

        Log::success(tr('Sent ":length" bytes of HTML to client', [':length' => $length]), 4);
    }



    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     * @return string|null
     */
    public static function get(): ?string
    {
        return static::$template_page->get();
    }



    /**
     * Access the Flash object
     *
     * @return Flash
     */
    public static function flash(): Flash
    {
        if (!static::$flash) {
            static::$flash = new Flash();
        }

        return static::$flash;
    }



    /**
     * Add meta information
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function addMeta(string $key, string $value): void
    {
        static::$headers['meta'][$key] = $value;
    }



    /**
     * Set the favicon for this page
     *
     * @param string $url
     * @return void
     */
    public static function setFavIcon(string $url): void
    {
        try {
            static::$headers['link'][$url] = [
                'rel'  => 'icon',
                'href' => UrlBuilder::getImg($url),
                'type' => File::new(Filesystem::absolute($url, 'img'), PATH_CDN . LANGUAGE . '/img')->mimetype()
            ];
        } catch (FilesystemException $e) {
            Log::warning($e->makeWarning());
        }
    }



    /**
     * Load the specified javascript file(s)
     *
     * @param string|array $urls
     * @param bool|null $header
     * @param bool $prefix If true, the scripts will be added at the beginning of the scripts list
     * @return void
     */
    public static function loadJavascript(string|array $urls, ?bool $header = null, bool $prefix = false): void
    {
        if ($header === null) {
            $header = !Config::getBoolean('web.javascript.delay', true);
        }

        if ($header and static::$html_headers_sent) {
            Log::warning(tr('Not adding files ":files" to HTML headers as the HTML headers have already been generated', [
                ':files' => $urls
            ]));
        }

        $scripts = [];

        foreach (Arrays::force($urls, ',') as $url) {
            $scripts[$url] = [
                'type' => 'text/javascript',
                'src'  => UrlBuilder::getJs($url)
            ];
        }

        // Add scripts to header or footer
        if ($header) {
            if ($prefix) {
                static::$headers['javascript'] = array_merge($scripts, static::$headers['javascript']);
            } else {
                static::$headers['javascript'] = array_merge(static::$headers['javascript'], $scripts);
            }

        } else {
            if ($prefix) {
                static::$footers['javascript'] = array_merge($scripts, static::$footers['javascript']);
            } else {
                static::$footers['javascript'] = array_merge(static::$footers['javascript'], $scripts);
            }
        }
    }



    /**
     * Load the specified CSS file(s)
     *
     * @param UrlBuilder|array|string $urls
     * @param bool $prefix If true, the scripts will be added at the beginning of the scripts list
     * @return void
     */
    public static function loadCss(UrlBuilder|array|string $urls, bool $prefix = false): void
    {
        $scripts = [];

        foreach (Arrays::force($urls, '') as $url) {
            $scripts[$url] = [
                'rel'  => 'stylesheet',
                'href' => UrlBuilder::getCss($url),
            ];
        }

        if ($prefix) {
            static::$headers['link'] = array_merge($scripts, static::$headers['link']);
        } else {
            static::$headers['link'] = array_merge(static::$headers['link'], $scripts);
        }
    }



    /**
     * Build and return the HTML headers
     *
     * @return string|null
     */
    public static function buildHeaders(): ?string
    {
        $return = '<!DOCTYPE ' . static::$doctype . '>
        <html lang="' . Session::getLanguage() . '">' . PHP_EOL;

        if (static::$page_title) {
            $return .= '<title>' . static::$page_title . '</title>' . PHP_EOL;
        }

        foreach (static::$headers['meta'] as $key => $value) {
            $return .= '<meta name="' . $key . '" content="' . $value . '" />' . PHP_EOL;
        }

        foreach (static::$headers['link'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<link ' . $header . ' />' . PHP_EOL;
        }

        foreach (static::$headers['javascript'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"', true);
            $return .= '<script ' . $header . '></script>' . PHP_EOL;
        }

        return $return . '</head>';
    }



    /**
     * Build and return the HTML footers
     *
     * @return string|null
     */
    public static function buildFooters(): ?string
    {
        $return = '';

        foreach (static::$footers['javascript'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"');
            $return .= '<script ' . $header . '></script>' . PHP_EOL;
        }

        return $return;
    }



    /**
     * Only-on type switch to indicate that the HTML headers have been generated and no more information can be added
     * to them
     *
     * @param bool $set
     * @return bool
     */
    public static function htmlHeadersSent(bool $set = false): bool
    {
        if ($set) {
            static::$html_headers_sent = true;
        }

        return static::$html_headers_sent;
    }



    /**
     * Builds and returns all the HTTP headers
     *
     * @param string $output
     * @param bool $attachment
     * @return array|null
     * @todo Refactor and remove $_CONFIG dependancies
     * @todo Refactor and remove $core dependancies
     * @todo Refactor and remove $params dependancies
     */
    public static function buildHttpHeaders(string $output, bool $attachment = false): ?array
    {
        if (static::httpHeadersSent()) {
            return null;
        }

        // Remove incorrect or insecure headers
        header_remove('Expires');
        header_remove('Pragma');

        /*
         * Ensure that from this point on we have a language configuration available
         *
         * The startup systems already configures languages but if the startup itself fails, or if a show() or showdie()
         * was issued before the startup finished, then this could leave the system without defined language
         */
        if (!defined('LANGUAGE')) {
            define('LANGUAGE', Config::get('http.language.default', 'en'));
        }

        // Create ETAG, possibly send out HTTP304 if client sent matching ETAG
        static::cacheEtag();

        // What to do with the PHP signature?
        $signature = Config::get('security.expose.php-signature', false);

        if (!$signature) {
            // Remove the PHP signature
            header_remove('X-Powered-By');

        } elseif (!is_bool($signature)) {
            // Send custom (fake) X-Powered-By header
            $headers[] = 'X-Powered-By: ' . $signature;
        }

        // Add a powered-by header
        switch (Config::getBoolean('security.expose.phoundation', 'limited')) {
            case 'limited':
                header('Powered-By: Phoundation');
                break;

            case 'full':
                header(tr('Powered-By: Phoundation version ":version"', [':version' => Core::FRAMEWORKCODEVERSION]));
                break;

            case 'none':
                // no-break
            case '':
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid configuration value ":value" for "security.signature" Please use one of "none", "limited", or "full"', [
                    ':value' => Config::get('security.expose.phoundation')
                ]));
        }

        $headers[] = 'Content-Type: ' . static::$content_type . '; charset=' . Config::get('languages.encoding.charset', 'UTF-8');
        $headers[] = 'Content-Language: ' . LANGUAGE;
        $headers[] = 'Content-Length: ' . (ob_get_length() + strlen($output));

        if (static::$http_code == 200) {
            if (empty($params['last_modified'])) {
                $headers[] = 'Last-Modified: ' . Date::convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT') . ' GMT';

            } else {
                $headers[] = 'Last-Modified: ' . Date::convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT') . ' GMT';
            }
        }

        // Add noidex, nofollow and nosnipped headers for non production environments and non normal HTTP pages.
        // These pages should NEVER be indexed
        if (!Debug::production() or !Core::getRequestType('http') or Config::get('web.noindex', false)) {
            $headers[] = 'X-Robots-Tag: noindex, nofollow, nosnippet, noarchive, noydir';
        }

        // CORS headers
        if (Config::get('web.security.cors', true) or static::$cors) {
            // Add CORS / Access-Control-Allow-.... headers
            // TODO This will cause issues if configured web.cors is not an array!
            static::$cors = array_merge(Arrays::force(Config::get('web.cors', [])), static::$cors);

            foreach (static::$cors as $key => $value) {
                switch ($key) {
                    case 'origin':
                        if ($value == '*.') {
                            // Origin is allowed from all subdomains
                            $origin = Strings::from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
                            $length = strlen(isset_get($_SESSION['domain']));

                            if (substr($origin, -$length, $length) === isset_get($_SESSION['domain'])) {
                                // Sub domain matches. Since CORS does not support sub domains, just show the
                                // current sub domain.
                                $value = $_SERVER['HTTP_ORIGIN'];

                            } else {
                                // Sub domain does not match. Since CORS does not support sub domains, just show no
                                // allowed origin domain at all
                                $value = '';
                            }
                        }

                    // no-break

                    case 'methods':
                        // no-break
                    case 'headers':
                        if ($value) {
                            $headers[] = 'Access-Control-Allow-' . Strings::capitalize($key) . ': ' . $value;
                        }

                        break;

                    default:
                        throw new HttpException(tr('Unknown CORS header ":header" specified', [':header' => $key]));
                }
            }
        }

        // Add cache headers and store headers in object headers list
        return static::addCacheHeaders($headers);
    }



    /**
     * Send all the specified HTTP headers
     *
     * @note The amount of sent bytes does NOT include the bytes sent for the HTTP response code header
     * @param array|null $headers
     * @return int The amount of bytes sent. -1 if static::sendHeaders() was called for the second time.
     */
    public static function sendHttpHeaders(?array $headers): int
    {
        if (static::httpHeadersSent(true)) {
            // Headers already sent
            return -1;
        }

        if ($headers === null) {
            // Specified NULL for headers, which is what buildHeaders() returned, so there are no headers to send
            return -1;
        }

        try {
            $length = 0;

            // Set correct headers
            http_response_code(static::$http_code);

            if (static::$http_code === 200) {
                Log::success(tr('Phoundation sent :http for URL ":url"', [
                    ':http' => (static::$http_code ? 'HTTP' . static::$http_code : 'HTTP0'),
                    ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]), 4);
            } else {
                Log::warning(tr('Phoundation sent ":http" for URL ":url"', [
                    ':http' => (static::$http_code ? 'HTTP' . static::$http_code : 'HTTP0'),
                    ':url'  => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]));
            }

            // Send all available headers
            foreach ($headers as $header) {
                $length += strlen($header);
                header($header);
            }

            return $length;

        } catch (Throwable $e) {
            Notification::new()
                ->setException($e)
                ->setTitle(tr('Failed to send headers to client'))
                ->send();

            // static::sendHeaders() itself crashed. Since static::sendHeaders() would send out http 500, and since it
            // crashed, it no longer can do this, send out the http 500 here.
            http_response_code(500);
            throw new $e;
        }
    }


    /**
     * Kill this web page script process
     *
     * @note Even if $kill_message was specified, the normal shutdown functions will still be called
     * @param string|null $kill_message If specified, this message will be displayed and the process will be terminated
     * @return void
     * @todo Implement this and add required functionality
     */
    #[NoReturn] public static function die(?string $kill_message = null): void
    {
        // If something went really, really wrong...
        if ($kill_message) {
            die($kill_message);
        }

        // POST requests should always show a flash message for feedback!
        if (Page::isPostRequestMethod()) {
            if (!Page::getFlashMessages()->getCount()) {
                Log::warning('Detected POST request without a flash message to give user feedback on what happened with this request!');
            }
        }

        // Normal kill request
        Log::action(tr('Killing web page process'), 2);
        die();
    }



    /**
     * Return HTTP caching headers
     *
     * Returns headers Cache-Control and ETag
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see htt_noCache()
     * @see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * @see https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     * @version 2.5.92: Added function and documentation

     * @param array $headers Any extra headers that are required
     * @return array
     */
    protected static function addCacheHeaders(array $headers): array
    {
        if (Config::get('web.cache.enabled', 'auto') === 'auto') {
            // PHP will take care of the cache headers

        } elseif (Config::get('web.cache.enabled', 'auto') === true) {
            // Place headers using phoundation algorithms
            if (!Config::get('web.cache.enabled', 'auto') or (static::$http_code != 200)) {
                // Non HTTP 200 / 304 pages should NOT have cache enabled! For example 404, 503 etc...
                $headers[] = 'Cache-Control: no-store, max-age=0';
                static::$etag = null;

            } else {
                // Send caching headers. Ajax, API, and admin calls do not have proxy caching
                switch (Core::getRequestType()) {
                    case 'api':
                        // no-break
                    case 'ajax':
                        // no-break
                    case 'admin':
                        break;

                    default:
                        // Session pages for specific users should not be stored on proxy servers either
                        if (!empty($_SESSION['user']['id'])) {
                            Config::get('web.cache.cacheability', 'private');
                        }

                        $headers[] = 'Cache-Control: ' . Config::get('web.cache.cacheability', 'private') . ', ' . Config::get('web.cache.expiration', 'max-age=604800') . ', ' . Config::get('web.cache.revalidation', 'must-revalidate') . Config::get('web.cache.other', 'no-transform');

                        if (!empty(static::$etag)) {
                            $headers[] = 'ETag: "' . static::$etag . '"';
                        }
                }
            }
        }

        return $headers;
    }



    /**
     * Send the required headers to ensure that the page will not be cached ever
     *
     * @return void
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see Http::cache()
     * @version 2.5.92: Added function and documentation
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     */
    protected static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
        header('Cache-Control: post-check=0, pre-check=0', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 10 Jan 2000 07:00:00 GMT', true);
    }



    /*
     * Test HTTP caching headers
     *
     * Sends out 304 - Not modified header if ETag matches
     *
     * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     */
    protected static function cacheTest($etag = null): bool
    {
        static::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . $etag);

        if (!Config::get('web.cache.enabled', 'auto')) {
            return false;
        }

        if (Core::getRequestType('ajax') or Core::getRequestType('api')) {
            return false;
        }

        if ((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == static::$etag) {
            if (empty($core->register['flash'])) {
                // The client sent an etag which is still valid, no body (or anything else) necesary
                http_headers(304, 0);
            }
        }

        return true;
    }



    /*
     * Test HTTP caching headers
     *
     * Sends out 304 - Not modified header if ETag matches
     *
     * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     */
    protected static function cacheEtag(): bool
    {
        // ETAG requires HTTP caching enabled. Ajax and API calls do not use ETAG
        if (!Config::get('web.cache.enabled', 'auto') or Core::getRequestType('ajax') or Core::getRequestType('api')) {
            static::$etag = null;
            return false;
        }

        // Create local ETAG
        static::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . Core::readRegister('etag'));

// :TODO: Document why we are trimming with an empty character mask... It doesn't make sense but something tells me we're doing this for a good reason...
        if (trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == static::$etag) {
            if (empty($core->register['flash'])) {
                // The client sent an etag which is still valid, no body (or anything else) necessary
                http_response_code(304);
                die();
            }
        }

        return true;
    }



    /**
     * Checks if HTTP headers have already been sent and logs warnings if so
     *
     * @param bool $send_now
     * @return bool
     */
    protected static function httpHeadersSent(bool $send_now = false): bool
    {
        if (headers_sent($file, $line)) {
            Log::warning(tr('Will not send HTTP headers again, output started at ":file@:line. Adding backtrace to debug this request', [
                ':file' => $file,
                ':line' => $line
            ]));
            Log::backtrace();
            return true;
        }

        if (static::$http_headers_sent) {
            // Since
            Log::warning(tr('HTTP Headers already sent by static::sendHeaders(). This can happen with PHP due to PHP ignoring output buffer flushes, causing this to be called over and over. just ignore this message.'), 2);
            return true;
        }

        if ($send_now) {
            static::$http_headers_sent = true;
        }

        return false;
    }



    /**
     * Starts up this page object
     *
     * @param string $target
     * @return void
     */
    protected static function startup(string $target): void
    {
        // Ensure we have flash messages available
        if (!isset(static::$flash_messages)) {
            static::$flash_messages = FlashMessages::new();
        }

        // Start the session
        if (!Core::getFailed()) {
            Session::startup();
        }

        if (Strings::fromReverse(dirname($target), '/') === 'system') {
            // Wait a small random time to avoid timing attacks on system pages
            usleep(mt_rand(1, 500));
        }

        // Set the page hash and check if we have access to this page?
        static::$hash   = sha1($_SERVER['REQUEST_URI']);
        static::$target = $target;
        static::$restrictions->check($target, false);

        // Check user access rights. Routing parameters should be able to tell us what rights are required now
        if (Core::stateIs('script')) {
            Page::hasRightsOrRedirects(static::$parameters->getRequiredRights($target), $target);
        }
    }



    /**
     * Try to send this page from cache, if available
     *
     * @param string $target
     * @param bool $attachment
     * @return void
     */
    protected static function tryCache(string $target, bool $attachment): void
    {
        // Do we have a cached version available?
        $cache = Cache::read(static::$hash, 'pages');

        if ($cache) {
            try {
                $cache  = Json::decode($cache);
                $length = static::sendHttpHeaders($cache['headers']);
                $output = self::filterOutput($cache['output']);

                Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);
                self::sendOutputToClient($output, $target, $attachment);

            } catch (Throwable $e) {
                // Cache failed!
                Log::warning(tr('Failed to send full cache page ":page" with following exception, ignoring cache and building page', [
                    ':page' => static::$hash,
                ]));

                Log::exception($e);
            }
        }
    }



    /**
     * Returns NULL output if the request method was HEAD (don't return output, only headers)
     *
     * @param string $output
     * @return string|null
     */
    protected static function filterOutput(string $output): ?string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
            // HEAD request, do not send any HTML whatsoever
            return null;
        }

        // 304 requests indicate the browser to use its local cache, send nothing
        // 429 Tell the client that it made too many requests, send nothing
        return match (static::getHttpCode()) {
            304, 429 => null,
            default  => $output,
        };
    }



    /**
     * Executes the target with the correct page driver (API or normal web page for now)
     *
     * @param string $target
     * @return string
     */
    protected static function executeTarget(string $target): string
    {
        try {
            // Execute the file and send the output HTML as a web page
            Log::information(tr('Executing page ":target" with template ":template" in language ":language" and sending output as HTML web page', [
                ':target'   => Strings::from($target, PATH_ROOT),
                ':template' => static::$template->getName(),
                ':language' => LANGUAGE
            ]));

            switch (Core::getRequestType()) {
                case 'api':
                    // no-break
                case 'ajax':
                    static::$api_interface = new ApiInterface();
                    $output = static::$api_interface->execute($target);
                    break;

                default:
                    $output = static::$template_page->execute($target);
            }
        } catch (AccessDeniedException $e) {
            $new_target = $e->getNewTarget();

            Log::warning(tr('Access denied to target ":target" for user ":user", redirecting to new target ":new"', [
                ':target' => $target,
                ':user'   => Session::getUser()->getDisplayId(),
                ':new'    => $new_target
            ]));

            $output = match (Core::getRequestType()) {
                'api', 'ajax' => static::$api_interface->execute($new_target),
                default       => static::$template_page->execute($new_target),
            };
        }

        return $output;
    }



    /**
     * Send the generated page output to the client
     *
     * @param string $output
     * @param string $target
     * @param bool $attachment
     * @return void
     */
    #[NoReturn] protected static function sendOutputToClient(string $output, string $target, bool $attachment): void
    {
        if ($attachment) {
            // Send download headers and send the $html payload
            \Phoundation\Web\Http\File::new(static::$restrictions)
                ->setAttachment(true)
                ->setData($output)
                ->setFilename(basename($target))
                ->send();
        } else {
            // Send the page to the client
            static::send($output);
        }

        static::die();
    }
}