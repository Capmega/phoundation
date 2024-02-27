<?php

declare(strict_types=1);

namespace Phoundation\Web;

use Exception;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\Interfaces\AuthenticationExceptionInterface;
use Phoundation\Api\Api;
use Phoundation\Cache\Cache;
use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Core\Exception\Interfaces\CoreReadonlyExceptionInterface;
use Phoundation\Core\Locale\Language\Interfaces\LanguageInterface;
use Phoundation\Core\Locale\Language\Language;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryReadonlyExceptionInterface;
use Phoundation\Data\Traits\DataStaticExecuted;
use Phoundation\Data\Validator\Exception\Interfaces\ValidationFailedExceptionInterface;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Date\Date;
use Phoundation\Date\Time;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\Interfaces\AccessDeniedExceptionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\Exception\Interfaces\IncidentsExceptionInterface;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Exception\PageException;
use Phoundation\Web\Exception\RedirectException;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessages;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenusInterface;
use Phoundation\Web\Html\Components\Widgets\Menus\Menus;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelsInterface;
use Phoundation\Web\Html\Components\Widgets\Panels\Panels;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;
use Phoundation\Web\Html\Template\Template;
use Phoundation\Web\Html\Template\TemplatePage;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Exception\Http404Exception;
use Phoundation\Web\Http\Exception\Http405Exception;
use Phoundation\Web\Http\Exception\Http409Exception;
use Phoundation\Web\Http\Exception\HttpException;
use Phoundation\Web\Http\Flash;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Interfaces\PageInterface;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Non200Urls\Non200Url;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;
use Phoundation\Web\Routing\Route;
use Stringable;
use Templates\AdminLte\AdminLte;
use Throwable;


/**
 * Class Page
 *
 * This class manages the execution and processing of web pages, AJAX and API requests.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Page implements PageInterface
{
    use DataStaticExecuted;


    /**
     * Classes to apply on default sections of the page
     *
     * @var array $page_classes
     */
    protected static array $page_classes = [];

    /**
     * Singleton
     *
     * @var PageInterface $instance
     */
    protected static PageInterface $instance;

    /**
     * The server filesystem restrictions
     *
     * @var Restrictions $restrictions
     */
    protected static RestrictionsInterface $restrictions;

    /**
     * The TemplatePage class that builds the UI
     *
     * @var TemplatePage $template_page
     */
    protected static TemplatePage $template_page;

    /**
     * The template class that builds the UI
     *
     * @var TemplateInterface $template
     */
    protected static TemplateInterface $template;

    /**
     * The Phoundation API interface
     *
     * @var Api $api_interface
     */
    protected static Api $api_interface;

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
     *                  characters, and then execute static::sendHeaders(), then ob_flush() and flush() and
     *                  headers_sent() will STILL be false, and REMAIN false until the buffer has reached 4096
     *                  characters OR the process ends. This variable just keeps track if static::sendHeaders() has been
     *                  executed (and it won't execute again), but headers might still be sent out manually. This is
     *                  rather messed up, because it really shows as if information was sent, the buffers are flushed,
     *                  yet nothing is actually flushed, so the headers are also not sent. This is just messed up PHP.
     *
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
    protected static array $headers;

    /**
     * Information that goes into the HTML footer
     *
     * @var array $footers
     */
    protected static array $footers;

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
     * @var RoutingParametersInterface $parameters
     */
    protected static RoutingParametersInterface $parameters;

    /**
     * The menus for this page
     *
     * @var MenusInterface $menus
     */
    protected static MenusInterface $menus;

    /**
     * The panels for this page
     *
     * @var PanelsInterface $panels
     */
    protected static PanelsInterface $panels;

    /**
     * @var string $language_code
     */
    protected static string $language_code;

    /**
     * @var LanguageInterface $language
     */
    protected static LanguageInterface $language;

    /**
     * The number of page levels that we're recursed in. Typically, this will be 0, but when executing pages from within
     * pages, recursing down, each time it will go up by one until that page is finished, then it will be lowered again
     *
     * @var int $levels
     */
    protected static int $levels = 0;


    /**
     * Resets all headers / footers
     *
     * @return void
     */
    protected static function resetHeadersFooters()
    {
        static::$headers = [
            'link'       => [],
            'meta'       => [
                'charset'  => Config::get('languages.encoding.charset', 'UTF-8'),
                'viewport' => Config::get('web.viewport'              , 'width=device-width, initial-scale=1, shrink-to-fit=no'),
            ],
            'javascript' => []
        ];

        static::$footers = [
            'javascript' => []
        ];
    }


    /**
     * Singleton
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * Returns the current tab index and automatically increments it
     *
     * @return RestrictionsInterface
     */
    public static function getRestrictions(): RestrictionsInterface
    {
        return static::$restrictions;
    }


    /**
     * Sets the current tab index and automatically increments it
     *
     * @param RestrictionsInterface $restrictions
     * @return void
     */
    public static function setRestrictions(RestrictionsInterface $restrictions): void
    {
        static::$restrictions = $restrictions;
    }


    /**
     * Returns the current tab index and automatically increments it
     *
     * @return MenusInterface
     */
    public static function getMenusObject(): MenusInterface
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
     * @param MenusInterface $menus
     * @return void
     */
    public static function setMenusObject(MenusInterface $menus): void
    {
        static::$menus = $menus;
    }


    /**
     * Returns the current panels configured for this page
     *
     * @return PanelsInterface
     */
    public static function getPanelsObject(): PanelsInterface
    {
        if (!isset(static::$panels)) {
            // Menus have not yet been initialized, do so now.
            static::$panels = new Panels();
        }

        return static::$panels;
    }


    /**
     * Sets the current panels configured for this page
     *
     * @param PanelsInterface $panels
     * @return void
     */
    public static function setPanelsObject(PanelsInterface $panels): void
    {
        static::$panels = $panels;
    }


    /**
     * Returns page parameters specified by the router
     *
     * @return RoutingParametersInterface
     */
    public static function getRoutingParameters(): RoutingParametersInterface
    {
        if (PLATFORM_CLI) {
            throw new PageException(tr('Cannot return routing parameters, this requires the HTTP platform'));
        }

        if (empty(static::$parameters)) {
            throw new PageException(tr('Cannot return routing parameters, parameters have not yet been set'));
        }

        return static::$parameters;
    }


    /**
     * Sets page parameters specified by the router
     *
     * @param RoutingParametersInterface $parameters
     * @return void
     */
    public static function setRoutingParameters(RoutingParametersInterface $parameters): void
    {
        static::resetHeadersFooters();
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
     * Sets the template to the specified template name
     *
     * @param string $template
     * @return void
     */
    public static function setTemplate(string $template): void
    {
        static::setRoutingParameters(static::getRoutingParameters()->setTemplate($template));
    }


    /**
     * Returns the language used for this page
     *
     * @return LanguageInterface
     */
    public static function getLanguage(): LanguageInterface
    {
        if (empty(static::$language_code)) {
            static::$language = Language::get(static::getLanguageCode());
        }

        return static::$language;
    }


    /**
     * Returns the language used for this page in ISO 639-2-b format
     *
     * @return string
     */
    public static function getLanguageCode(): string
    {
        if (empty(static::$language)) {
            if (PLATFORM_WEB) {
                // Get requested language from client
                static::$language_code = static::detectRequestedLanguage();

            } else {
                // Get requested language from core
                static::$language_code = Core::readRegister('system', 'language');
            }
        }

        return static::$language_code;
    }


    /**
     * Returns the port used for this request. When on command line, assume the default from configuration
     *
     * @return int
     */
    public static function getPort(): int
    {
        if (PLATFORM_WEB) {
            return (int) $_SERVER['SERVER_PORT'];
        }

        // We're on command line
        $config = Config::getArray('web.domains.primary');

        if (array_key_exists('port', $config)) {
            // Return configured WWW port
            return Config::getInteger('web.domains.primary.port');
        }

        if (substr($config['www'], 4, 1) === 's') {
            // Return default HTTPS port
            return 443;
        }

        // Return default HTTP port
        return 80;
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
     * @return bool
     */
    public static function getBuildBodyWrapper(): bool
    {
        return static::$build_body;
    }


    /**
     * Sets the class for the given page section
     *
     * @param string $class
     * @param string $section
     * @return void
     */
    public static function setClass(string $class, string $section): void
    {
        static::$page_classes[$section] = $class;
    }


    /**
     * Sets the class for the given page section
     *
     * @param string $class
     * @param string $section
     * @return void
     */
    public static function defaultClass(string $class, string $section): void
    {
        if (empty(static::$page_classes[$section])) {
            static::$page_classes[$section] = $class;
        }
    }


    /**
     * Returns the class for the given section, if available
     *
     * @param string $section
     * @param string|null $default
     * @return string|null
     */
    public static function getClass(string $section, ?string $default = null): ?string
    {
        return isset_get(static::$page_classes[$section], $default);
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
            if (is_bool($default)) {
                // We don't have a referer, return the current URL instead
                return UrlBuilder::getCurrent()->__toString();
            }

            // Use the specified referrer
            return UrlBuilder::getWww($default)->__toString();
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
     * Returns if this page is executed directly from Route, or if its executed by executeReturn() call
     *
     * @return bool
     */
    public static function isExecutedDirectly(): bool
    {
        return !static::$levels;
    }


    /**
     * Will throw a Http404Exception when this page is executed directly from Route
     *
     * @return void
     */
    public static function cannotBeExecutedDirectly(?string $message = null): void
    {
        if (Page::isExecutedDirectly()) {
            if (!$message) {
                $message = tr('The page ":page" cannot be accessed directly', [
                    ':page' => Strings::untilReverse(static::getExecutedFile(), '.php')
                ]);
            }

            throw Http404Exception::new($message)->makeWarning();
        }
    }


    /**
     * Returns the number of pages we have recursed into.
     *
     * Returns 0 for the first page, 1 for the next, etc.
     *
     * @return int
     */
    public static function getLevels(): int
    {
        return static::$levels;
    }


    /**
     * Return the domain for this page, or the primary domain on CLI
     *
     * @return string
     */
    public static function getDomain(): string
    {
        return Domains::getCurrent();
    }


    /**
     * Return the protocol for this page, or the primary domain on CLI
     *
     * @return string
     */
    public static function getProtocol(): string
    {
        if (PLATFORM_WEB) {
            return $_SERVER['REQUEST_SCHEME'];
        }

        return Strings::until(Config::getString('web.domains.primary.www'), '://');
    }


    /**
     * Return the URL for this page
     *
     * @param bool $no_queries
     * @return string
     */
    public static function getUrl(bool $no_queries = false): string
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . static::getUri($no_queries);
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
        return ($no_queries ? Strings::until($_SERVER['REQUEST_URI'], '?') : $_SERVER['REQUEST_URI']);
    }


    /**
     * Return the complete request URL for this page (WITH domain)
     *
     * @param string $type
     * @return string
     */
    public static function getRootUrl(string $type = 'www'): string
    {
        return static::$parameters->getRootUrl($type);
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
     * @return TemplateInterface
     */
    public static function getTemplate(): TemplateInterface
    {
        if (empty(static::$template)) {
            // Default template is AdminLte
            return new AdminLte();
        }

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
            throw AuthenticationException::new(tr('You have to sign in to view this page'))
                ->setNewTarget($new_target);
        }
    }


    /**
     * Will throw an AccessDeniedException if the current session user does not have ALL the specified rights
     *
     * @param array|Stringable|string $rights
     * @param string|int|null $missing_rights_target
     * @param string|int|null $guest_target
     * @return void
     */
    public static function requiresAllRights(array|Stringable|string $rights, string|int|null $missing_rights_target = 403, string|int|null $guest_target = 401): void
    {
        static::requiresNotGuest();

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
     * Returns the <head>ers to be sent
     *
     * @return array
     */
    public static function getHeaders(): array
    {
        return static::$headers;
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
     * @param Stringable|string|float|int|null $page_title
     * @return void
     */
    public static function setPageTitle(Stringable|string|float|int|null $page_title): void
    {
        static::$page_title = strip_tags((string) $page_title);
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
     * @param Stringable|string|float|int|null $header_title
     * @return void
     */
    public static function setHeaderTitle(Stringable|string|float|int|null $header_title): void
    {
        static::$header_title = (string) $header_title;

        if (!static::$page_title) {
            static::$page_title = Config::get('project.name', 'Phoundation') . ' - ' . $header_title;
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
     * @param Stringable|string|float|int|null $header_sub_title
     * @return void
     */
    public static function setHeaderSubTitle(Stringable|string|float|int|null $header_sub_title): void
    {
        static::$header_sub_title = get_null((string) $header_sub_title);
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
     * Ensures that this session user has all the specified rights, or a redirect will happen
     *
     * @param array|string $rights
     * @param string $target
     * @param int|null $rights_redirect
     * @param string|null $guest_redirect
     * @return void
     */
    public static function hasRightsOrRedirects(array|string $rights, string $target, ?int $rights_redirect = null, ?string $guest_redirect = null): void
    {
        if (Session::getUser()->hasAllRights($rights)) {
            if (Session::getSignInKey() === null) {
                // Well, then, all fine and dandy!
                return;
            }

            // Check sign-key restrictions and if those are okay, we are good to go
            static::hasSignKeyRestrictions($rights, $target);
            return;
        }

        if (!$target) {
            // If target wasn't specified, we can safely assume it's the same as the page target.
            $target = static::$target;
        }

        // Is this a system page though? System pages require no rights to be viewed.
        $system = dirname($target);
        $system = basename($system);

        if ($system === 'system') {
            // Hurrah, it's a bo, eh, system page! System pages require no rights. Everyone can see a 404, 500, etc...
            return;
        }

        // Is this a guest? Guests have no rights and can only see system pages and pages that require no rights
        if (Session::getUser()->isGuest()) {
            // This user has no rights at all, send to sign-in page
            if (!$guest_redirect) {
                $guest_redirect = '/sign-in.html';
            }

            $current        = static::getRedirect(UrlBuilder::getCurrent());
            $guest_redirect = UrlBuilder::getWww($guest_redirect)
                ->addQueries($current ? 'redirect=' . $current : null);

            Incident::new()
                ->setType('401 - Unauthorized')
                ->setSeverity(Severity::low)
                ->setTitle(tr('Guest user has no access to target page ":target" (real target ":real_target" requires rights ":rights"). Redirecting to ":redirect"', [
                    ':target'      => Strings::from(static::$target, DIRECTORY_ROOT),
                    ':real_target' => Strings::from($target, DIRECTORY_ROOT),
                    ':redirect'    => $guest_redirect,
                    ':rights'      => $rights
                ]))
                ->setDetails([
                    'user'        => 0,
                    'uri'         => static::getUri(),
                    'target'      => Strings::from(static::$target, DIRECTORY_ROOT),
                    'real_target' => Strings::from($target, DIRECTORY_ROOT),
                    'rights'      => $rights
                ])
                ->save();

            if (Core::isRequestType(EnumRequestTypes::api)) {
                // This method will exit
                Json::reply([
                    '__system' => [
                        'http_code' => 401
                    ]
                ]);
            }

            if (Core::isRequestType(EnumRequestTypes::ajax)) {
                // This method will exit
                Json::reply([
                    '__system' => [
                        'http_code' => 401,
                        'location'  => (string) UrlBuilder::getWww('/sign-in.html')
                    ]
                ]);
            }

            // This method will exit
            Page::redirect($guest_redirect);
        }

        // This user is missing rights
        if (!$rights_redirect) {
            $rights_redirect = 403;
        }

        // Do the specified rights exist at all? If they aren't defined then no wonder this user doesn't have them
        if (Rights::getNotExist($rights)) {
            // One or more of the rights do not exist
            Incident::new()
                ->setType('Non existing rights')->setSeverity(in_array('admin', Session::getUser()->getMissingRights($rights)) ? Severity::high : Severity::medium)
                ->setTitle(tr('The requested rights ":rights" for target page ":target" (real target ":real_target") do not exist on this system and was not automatically created. Redirecting to ":redirect"', [
                    ':rights'      => Strings::force(Rights::getNotExist($rights), ', '),
                    ':target'      => Strings::from(static::$target, DIRECTORY_ROOT),
                    ':real_target' => Strings::from($target, DIRECTORY_ROOT),
                    ':redirect'    => $rights_redirect
                ]))
                ->setDetails([
                    'user'           => Session::getUser()->getLogId(),
                    'uri'            => static::getUri(),
                    'target'         => Strings::from(static::$target, DIRECTORY_ROOT),
                    'real_target'    => Strings::from($target, DIRECTORY_ROOT),
                    'rights'         => $rights,
                    'missing_rights' => Rights::getNotExist($rights)
                ])
                ->notifyRoles('accounts')
                ->save();

        } else {
            // Registered user does not have the required rights
            Incident::new()
                ->setType('403 - Forbidden')
                ->setSeverity(in_array('admin', Session::getUser()->getMissingRights($rights)) ? Severity::high : Severity::medium)
                ->setTitle(tr('User ":user" does not have the required rights ":rights" for target page ":target" (real target ":real_target"). Executing "system/:redirect" instead', [
                    ':user'        => Session::getUser()->getLogId(),
                    ':rights'      => Session::getUser()->getMissingRights($rights),
                    ':target'      => Strings::from(static::$target, DIRECTORY_ROOT),
                    ':real_target' => Strings::from($target, DIRECTORY_ROOT),
                    ':redirect'    => $rights_redirect
                ]))
                ->setDetails([
                    'user'        => Session::getUser()->getLogId(),
                    'uri'         => static::getUri(),
                    'target'      => Strings::from(static::$target, DIRECTORY_ROOT),
                    'real_target' => Strings::from($target, DIRECTORY_ROOT),
                    'rights'      => Session::getUser()->getMissingRights($rights),
                ])
                ->notifyRoles('accounts')
                ->save();
        }

        // This method will exit
        Route::executeSystem($rights_redirect);
    }


    /**
     * Returns true if the current URL has sign-key restrictions
     *
     * @param array|string $rights
     * @param string $target
     * @return void
     */
    protected static function hasSignKeyRestrictions(array|string $rights, string $target): void
    {
        $key = Session::getSignInKey();

        // User signed in with "sign-in" key that may have additional restrictions
        if (!Core::isRequestType(EnumRequestTypes::html)) {
            Incident::new()
                ->setType('401 - Unauthorized')
                ->setSeverity(Severity::low)
                ->setTitle(tr('Session keys cannot be used on ":type" requests', [
                    ':type' => Core::getRequestType(),
                ]))
                ->setDetails([
                    'user'         => $key->getUser()->getLogId(),
                    'uri'          => static::getUri(),
                    'target'       => Strings::from(static::$target, DIRECTORY_ROOT),
                    'real_target'  => Strings::from($target, DIRECTORY_ROOT),
                    'rights'       => $rights,
                    ':sign_in_key' => $key->getUuid()
                ])
                ->save();

            Route::executeSystem(401);
        }

        if (!$key->signKeyAllowsUrl(UrlBuilder::getCurrent(), $target)) {
            Incident::new()
                ->setType('401 - Unauthorized')
                ->setSeverity(Severity::low)
                ->setTitle(tr('Cannot open URL ":url", sign in key ":uuid" does not allow navigation beyond ":allow"', [
                    ':url'   => UrlBuilder::getCurrent(),
                    ':allow' => $key->getRedirect(),
                    ':uuid'  => $key->getUuid()
                ]))
                ->setDetails([
                    ':url'      => UrlBuilder::getCurrent(),
                    ':users_id' => $key->getUsersId(),
                    ':allow'    => $key->getRedirect(),
                    ':uuid'     => $key->getUuid()
                ])
                ->save();

            // This method will exit
            Route::executeSystem(401);
        }
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
     * @param string $target       The target file that should be executed or sent to the client
     * @param boolean $attachment If specified as true, will send the file as a downloadable attachment, to be written
     *                            to disk instead of displayed on the browser. If set to false, the file will be sent as
     *                            a file to be displayed in the browser itself.
     * @param bool $system        If true, this is a system page being executed
     * @return never
     *
     * @todo Make Page::executeFromRoute() use Page::executePage() somehow
     * @see Route::execute()
     * @see Template::execute()
     */
    #[NoReturn] public static function execute(string $target, bool $attachment = false, bool $system = false): never
    {
        Log::information(tr('Executing page ":target" with template ":template" in language ":language" and sending output as HTML web page', [
            ':target'   => Strings::from($target, DIRECTORY_ROOT),
            ':template' => static::$template->getName(),
            ':language' => LANGUAGE
        ]));

        // Start the page up
        static::startup($target, $attachment, $system);

        // Execute the specified target file
        // Build the headers, cache output and headers together, then send the headers
        // TODO Work on the HTTP headers, lots of issues here still, like content-length!
        $output  = static::executeTarget($target, false);
        $headers = static::buildHttpHeaders($output, $attachment);

        // Merge the flash messages from sessions into page flash messages
        Page::getFlashMessages()->pullMessagesFrom(Session::getFlashMessages());

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

        // All done, send output to the client
        $output = static::filterOutput($output);
        static::sendOutputToClient($output, $target, $attachment);
    }


    /**
     * Executes the specified page and returns the output
     *
     * Also handles a variety of exceptions and redirects to showing system pages instead, like 400, 401, 404, etc...
     *
     * @param string $page The target page that should be executed and returned
     * @param bool $main_content_only
     * @return string|null
     *
     * @see Route::execute()
     * @see Template::execute()
     */
    public static function executeReturn(string $page, bool $main_content_only = true): ?string
    {
        // Execute the specified target file
        // Get all output buffers and restart buffer
        static::$levels++;
        return static::executeTarget($page, $main_content_only);
        static::$levels--;
    }


    /**
     * Executes the specified system page after a page had an exception
     *
     * @param int $http_code
     * @param Throwable $e
     * @param string $message
     * @return void
     */
    #[NoReturn] protected static function executeSystemAfterPageException(Throwable $e, int $http_code, string $message): void
    {
        if (Config::getBoolean('security.web.monitor.non-200-urls', true)) {
            Non200Url::new()->generate($http_code)->save();
        }

        Log::warning($message);
        Log::warning('Registered request as non HTTP-200 URL');
        Log::warning($e);

        // Clear flash messages
        Session::getFlashMessages()->clear();
        Page::getFlashMessages()->clear();

        // Modify POST requests to GET requests and remove all GET and POST data
        $_SERVER['REQUEST_METHOD'] = 'GET';
        GetValidator::new()->clear();
        PostValidator::new()->clear();

        Core::writeRegister($e, 'e');
        Route::executeSystem($http_code);
    }


    /**
     * Check if this user should be forcibly being redirected to a different page
     *
     * @return void
     */
    public static function checkForceRedirect(): void
    {
        // Does this user have a forced redirect?
        if (!Session::getUser()->isGuest()) {
            $redirect = Session::getUser()->getRedirect();

            if ($redirect) {
                // Are we at the forced redirect page? If so, we can stay
                $current = (string) UrlBuilder::getCurrent();

                if (Strings::until($redirect, '?') !== Strings::until($current, '?')) {
                    // We're at a different page. Should we redirect to the specified page?
                    if (!static::skipRedirect()) {
                        // No, it's not, redirect!
                        Log::action(tr('User ":user" has a redirect to ":url", redirecting there instead', [
                            ':user' => Session::getUser()->getLogId(),
                            ':url'  => $redirect
                        ]));

                        // Get URL builder object, ensure that sign-in page gets a redirect=$current_url
                        $redirect = UrlBuilder::getWww($redirect);

                        if ((string) $redirect === (string) UrlBuilder::getWww('sign-in')) {
                            $redirect->addQueries('redirect=' . $current);
                        }

                        Page::redirect($redirect);
                    }

                    Log::warning(tr('User ":user" has a redirect to ":url" which MAY NOT redirected to, ignoring redirect', [
                        ':user' => Session::getUser()->getLogId(),
                        ':url'  => $redirect
                    ]));
                }
            }
        }
    }


    /**
     * Returns true if redirecting for the specified URL should be skipped
     *
     * Currently, sign-out or index pages should not be redirected to
     *
     * @param Stringable|string|null $url
     * @return bool
     */
    protected static function skipRedirect(Stringable|string|null $url = null): bool
    {
        if (!$url) {
            // Default to current URL
            $url = UrlBuilder::getCurrent();
        }

        // Compare URLs without queries
        $url  = Strings::until((string) $url, '?');
        $skip = [
            (string) UrlBuilder::getWww('sign-out'),
        ];

        return in_array($url, $skip);
    }


    /**
     * Returns the redirect URL if it should not be skipped
     *
     * @param Stringable|string $redirect
     * @return string|null
     */
    protected static function getRedirect(Stringable|string $redirect): ?string
    {
        if (static::skipRedirect($redirect)) {
            Log::warning(tr('Skipping redirect to ":redirect" as it is now allowed', [
                ':redirect' => $redirect
            ]));

            return null;
        }

        return (string) $redirect;
    }


    /**
     * Return the specified URL with a redirect URL stored in $core->register['redirect']
     *
     * @note If no URL is specified, the current URL will be used
     * @param UrlBuilder|string|bool|null $url
     * @param int $http_code
     * @param int|null $time_delay
     * @param string|null $reason_warning
     * @return never
     * @see UrlBuilder
     * @see UrlBuilder::addQueries()
     */
    #[NoReturn] public static function redirect(UrlBuilder|string|bool|null $url = null, int $http_code = 302, ?int $time_delay = null, ?string $reason_warning = null): never
    {
        if (!PLATFORM_WEB) {
            throw new RedirectException(tr('Page::redirect() can only be called on web sessions'));
        }

//        if (Session::getSignInKey()?->getAllowNavigation()) {
//            // This session was opened using a sign-in key that does not allow navigation, we cannot redirect away!
//            throw new RedirectException(tr('Cannot redirect sign-in session with UUID ":uuid" for user ":user" to URL ":url", this session does not allow navigation', [
//                ':uuid' => Session::getSignInKey()->getUuid(),
//                ':user' => Session::getUser()->getLogId(),
//                ':url'  => $url
//            ]));
//        }

        // Build URL
        $redirect = UrlBuilder::getWww($url);

        // Protect against endless redirecting.
        if (UrlBuilder::isCurrent($redirect)) {
            // POST-requests may redirect to the same page as the redirect will change POST to GET
            if (!Page::isPostRequestMethod()) {
                // If the specified redirect URL was a short code like "prev" or "referer", then it was not hard coded
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
            // Add a redirect back query
            $redirect = UrlBuilder::getWww($redirect)->addQueries(['redirect' => $_GET['redirect']]);
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
            case 301:
                // no-break
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

        if ($reason_warning) {
            Log::warning(tr('Redirecting because: :reason', [':reason' => $reason_warning]));
        }

        // Redirect with time delay
        if ($time_delay) {
            Log::action(tr('Redirecting with HTTP ":http" and ":time" seconds delay to url ":url"', [
                ':http' => $http_code,
                ':time' => $time_delay,
                ':url'  => $redirect
            ]));

            header('Refresh: '.$time_delay . ';' . $redirect, true, $http_code);
        } else {
            // Redirect immediately
            Log::action(tr('Redirecting with HTTP ":http" to url ":url"', [
                ':http' => $http_code,
                ':url'  => $redirect
            ]));

            header('Location:' . $redirect, true, $http_code);
        }

        exit();
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

                if (empty(Config::get('language.supported', [])[$requested['language']])) {
                    continue;
                }

                $return[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($return);
        return $return;
    }


    /**
     * Returns the current HTML output buffer for this page
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
     * Returns the current length HTML output buffer for this page
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

        // Headers have been sent, from here we know if it's a 200 or something else
        if (static::$http_code === 200) {
            Log::success(tr('Sent :http with ":length bytes" for URL ":url"', [
                ':length' => $length,
                ':http'   => (static::$http_code ? 'HTTP ' . static::$http_code : 'HTTP 0'),
                ':url'    => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            ]), 4);

        } else {
            Log::warning(tr('Sent ":http" with ":length bytes" for URL ":url"', [
                ':length' => $length,
                ':http'   => (static::$http_code ? 'HTTP ' . static::$http_code : 'HTTP 0'),
                ':url'    => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            ]));
        }
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
     * Add meta-information
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
     * @param string|null $url
     * @return void
     */
    public static function setFavIcon(?string $url = null): void
    {
        try {
            if (!$url) {
                $url  = 'img/favicons/' . Core::getProjectSeoName() . '/project.png';
                $url  = static::versionFile($url, 'img');
                $file = Filesystem::absolute(LANGUAGE . '/' . $url, DIRECTORY_CDN);

                static::$headers['link'][$url] = [
                    'rel'  => 'icon',
                    'href' => UrlBuilder::getImg($url),
                    'type' => File::new($file)->getMimetype()
                ];
            } else {
                $url = static::versionFile($url, 'img');

                // Unknown (likely remote?) link
                static::$headers['link'][$url] = [
                    'rel'  => 'icon',
                    'href' => UrlBuilder::getImg($url),
                    'type' => 'image/' . Strings::fromReverse($url, '.')
                ];
            }

        } catch (FilesystemException $e) {
            Log::warning('Failed to find favicon, see next message for more information');
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

        // Convert the given URL (parts) to real URLs
        foreach (Arrays::force($urls, ',') as $url) {
            $url = static::versionFile($url, 'js');

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

        // Convert the given URL (parts) to real URLs
        foreach (Arrays::force($urls, '') as $url) {
            $url = static::versionFile($url, 'css');

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
     * Builds and return the HTML <head> tag
     *
     * @return string|null
     */
    public static function buildHtmlHeadTag(): ?string
    {
        $return = '<!DOCTYPE ' . static::$doctype . '>
        <html lang="' . Session::getLanguage() . '">' . PHP_EOL;

        if (static::$page_title) {
            $return .= '<title>' . (Core::isProductionEnvironment() ? null : '(' . ENVIRONMENT . ') ') . static::$page_title . '</title>' . PHP_EOL;
        }

        foreach (static::$headers['meta'] as $key => $value) {
            $return .= '<meta name="' . $key . '" content="' . $value . '" />' . PHP_EOL;
        }

        foreach (static::$headers['link'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"');
            $return .= '<link ' . $header . ' />' . PHP_EOL;
        }

        foreach (static::$headers['javascript'] as $header) {
            $header  = Arrays::implodeWithKeys($header, ' ', '=', '"');
            $return .= '<script ' . $header . '></script>' . PHP_EOL;
        }

        return $return . '</head>';
    }


    /**
     * Build and return the HTML footers
     *
     * @todo This should be upgraded to using Javascript / Css objects
     * @return string|null
     */
    public static function buildHtmlFooters(): ?string
    {
        Log::warning('TODO Reminder: Page::buildFooters() should be upgraded to using Javascript / Css objects');

        $return = '';

        if (isset_get(static::$footers['html'])) {
            $return .= implode('', static::$footers['html']);
        }

        foreach (static::$footers['javascript'] as $footer) {
            if (isset($footer['src'])) {
                $footer  = Arrays::implodeWithKeys($footer, ' ', '=', '"');
                $return .= '<script ' . $footer . '></script>' . PHP_EOL;

            } elseif (isset($footer['content'])) {
                $return .= '<script>' . PHP_EOL . $footer['content'] . PHP_EOL . '</script>' . PHP_EOL;

            } else {
                throw new OutOfBoundsException(tr('Invalid script footer specified, should contain at least "src" or "content"'));
            }
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

        // Create ETAG, possibly send out HTTP304 if the client sent matching ETAG
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
        switch (Config::getBoolString('security.expose.phoundation', 'limited')) {
            case 'limited':
                header('Powered-By: Phoundation');
                break;

            case 'full':
                header(tr('Powered-By: Phoundation version ":version"', [
                    ':version' => Core::FRAMEWORKCODEVERSION
                ]));
                break;

            case 'none':
                // no-break
            case '':
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid configuration value ":value" for "security.signature" Please use one of "none", "limited", or "full"', [
                    ':value' => Config::getBoolString('security.expose.phoundation', 'limited')
                ]));
        }

        $headers[] = 'Content-Type: ' . static::$content_type . '; charset=' . Config::get('languages.encoding.charset', 'UTF-8');
        $headers[] = 'Content-Language: ' . LANGUAGE;
        $headers[] = 'Content-Length: ' . strlen($output);

        if (static::$http_code == 200) {
            if (empty($params['last_modified'])) {
                $headers[] = 'Last-Modified: ' . Date::convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT') . ' GMT';

            } else {
                $headers[] = 'Last-Modified: ' . Date::convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT') . ' GMT';
            }
        }

        // Add noindex, nofollow and nosnipped headers for non production environments and non normal HTTP pages.
        // These pages should NEVER be indexed
        if (!Core::isProductionEnvironment() or !Core::isRequestType(EnumRequestTypes::html) or Config::get('web.noindex', false)) {
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
                        throw new HttpException(tr('Unknown CORS header ":header" specified', [
                            ':header' => $key
                        ]));
                }
            }
        }

        // Add cache headers and store headers in the object headers list
        return static::addCacheHeaders($headers);
    }


    /**
     * Send all the specified HTTP headers
     *
     * @note The number of sent bytes does NOT include the bytes sent for the HTTP response code header
     * @param array|null $headers
     * @return int The number of bytes sent. -1 if static::sendHeaders() was called for the second time.
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
     * @note Even if $exit_message was specified, the normal shutdown functions will still be called
     * @param string|null $exit_message If specified, this message will be displayed and the process will be terminated
     * @param bool $sig_kill
     * @return never
     * @todo Implement this and add required functionality
     */
    #[NoReturn] public static function exit(?string $exit_message = null, bool $sig_kill = false): never
    {
        // If something went really, really wrong...
        if ($sig_kill) {
            exit($exit_message);
        }

        // POST-requests should always show a flash message for feedback!
        if (Page::isPostRequestMethod()) {
            if (!Page::getFlashMessages()->getCount()) {
                Log::warning('Detected POST request without a flash message to give user feedback on what happened with this request!');
            }
        }

        if (static::$http_code === 200) {
            Log::success(tr('Script ":script" ended successfully with HTTP code ":httpcode" in ":time" with ":usage" peak memory usage', [
                ':script'   => static::getExecutedPath(),
                ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                ':httpcode' => static::$http_code
            ]));

        } else {
            Log::warning(tr('Script ":script" ended with HTTP warning code ":httpcode" in ":time" with ":usage" peak memory usage', [
                ':script'   => static::getExecutedPath(),
                ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                ':httpcode' => static::$http_code
            ]));
        }

        // Normal kill request
        Log::action(tr('Killing web page process'), 2);
        exit();
    }


    /**
     * Add the specified HTML to the HEAD tag
     *
     * @todo This should -in the near future- be updated to sending Javascript, Css, etc objects instead of "some array"
     * @param string $key
     * @param array $entry
     * @return void
     */
    public static function addToHeader(string $key, array $entry): void
    {
        static::$headers[$key][] = $entry;
    }


    /**
     * Add the specified HTML to the footer
     *
     * @todo This should -in the near future- be updated to sending Javascript, Css, etc objects instead of "some array"
     * @param string $key
     * @param array|string|null $entry
     * @return void
     */
    public static function addToFooter(string $key, array|string|null $entry): void
    {
        static::$footers[$key][] = $entry;
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
                    case EnumRequestTypes::api:
                        // no-break
                    case EnumRequestTypes::ajax:
                        // no-break
                    case EnumRequestTypes::admin:
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

        if (Core::isRequestType(EnumRequestTypes::ajax) or Core::isRequestType(EnumRequestTypes::api)) {
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
        if (!Config::get('web.cache.enabled', 'auto') or Core::isRequestType(EnumRequestTypes::ajax) or Core::isRequestType(EnumRequestTypes::api)) {
            static::$etag = null;
            return false;
        }

        // Create local ETAG
        static::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . Core::readRegister('etag'));

// :TODO: Document why we are trimming with an empty character mask... It doesn't make sense but something tells me we're doing this for a good reason...
        if (trim((string) isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == static::$etag) {
            if (empty($core->register['flash'])) {
                // The client sent an etag which is still valid, no body (or anything else) necessary
                http_response_code(304);
                exit();
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

            return true;
        }

        if (static::$http_headers_sent) {
            // Since
            Log::warning(tr('HTTP Headers already sent by :method. This can happen with PHP due to PHP ignoring output buffer flushes, causing this to be called over and over. just ignore this message.', [
                ':method' => 'static::sendHeaders()'
            ]), 2);
            return true;
        }

        if ($send_now) {
            static::$http_headers_sent = true;
        }

        return false;
    }


    /**
     * Starts this page object up
     *
     * This method will start up the session, perform a sleep() call  if we're on a system page, convert the target to
     * an absolute filename, and will check target restrictions.
     *
     * @param string $target
     * @param bool $attachment
     * @param bool $system
     * @return void
     */
    protected static function startup(string $target, bool $attachment = false, bool $system = false): void
    {
        // Ensure we have received routing parameters, can't execute without!
        if (empty(static::$parameters)) {
            throw new PageException(tr('Cannot execute target ":target", no routing parameters specified', [
                ':target' => $target
            ]));
        }

        // Ensure we have flash messages available
        if (!isset(static::$flash_messages)) {
            static::$flash_messages = FlashMessages::new();
        }

        // Start the session if Core hasn't failed so far
        if (!Core::getFailed()) {
            // But not for API's! API's have to handle different session management
            if (!Core::isRequestType(EnumRequestTypes::api)) {
                Session::startup();
            }
        }

        if (Strings::fromReverse(dirname($target), '/') === 'system') {
            // Wait a small random time (Between 0mS and 100mS) to avoid timing attacks on system pages
            try {
                usleep(random_int(1, 100000));

            } catch (Exception $e) {
                // random_int() crashed for ... reasons? Fall back on mt_rand()
                usleep(mt_rand(1, 100000));
            }
        }

        // Set the page hash and check if we have access to this page?
        static::$hash   = sha1($_SERVER['REQUEST_URI']);
        static::$target = $target;
        static::$restrictions->check(static::$target, false);

        // Check user access rights. Routing parameters should be able to tell us what rights are required now
        // Check only when in state "script". State "maintenance", for example, requires no rights checking
        if (Core::isState('script')) {
            Page::hasRightsOrRedirects(static::$parameters->getRequiredRights(static::$target), static::$target);
        }

        // Check if this session should actually be redirected somewhere else.
        // System pages never have a redirect, though!
        if (!$system) {
            static::checkForceRedirect();
        }

        // Do we have this page in cache, perhaps?
        static::tryCache($target, $attachment);
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
                $output = static::filterOutput($cache['output']);

                Log::success(tr('Sent ":length" bytes of HTTP to client', [':length' => $length]), 3);
                static::sendOutputToClient($output, $target, $attachment);

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
     * Detects and returns what language the user prefers to see
     *
     * @return string a valid language requested by the user that is supported by the systems configuration
     */
    protected static function detectRequestedLanguage(): string
    {
        $languages = Config::getArray('language.supported', []);

        switch (count($languages)) {
            case 0:
                return LANGUAGE;

            case 1:
                return current($languages);

            default:
                // This is a multilingual website. Ensure language is supported and add language selection to the URL.
                $requested = static::acceptsLanguages();

                if (empty($requested)) {
                    // Go for default language
                    return Config::getString('languages.default', 'en');
                }

                foreach ($requested as $locale) {
                    if (in_array($locale['language'], $languages)) {
                        // This requested language exists
                        return $locale['language'];
                    }
                }

                // None of the requested languages are supported! Oh noes! Go for default language.
                Notification::new()
                    ->setUrl('developer/incidents.html')
                    ->setMode(EnumDisplayMode::warning)
                    ->setCode('unsupported-languages-requested')
                    ->setRoles('developer')
                    ->setTitle(tr('Unsupported language requested by client'))
                    ->setMessage(tr('None of the requested languages ":languages" is supported', [
                        ':languages' => $requested
                    ]))
                    ->send();

                return Config::getString('languages.default', 'en');
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
     * @param bool $main_content_only
     * @return string|null
     * @todo Move AccessDeniedException handling to Page::execute()
     */
    protected static function executeTarget(string $target, bool $main_content_only = false): ?string
    {
        Log::information(tr('Executing target ":target"', [
            ':target' => Strings::from($target, DIRECTORY_ROOT),
        ]), 4);

        static::addExecutedPath($target);

        try {
            // Ensure we have an absolute target
            if (!str_starts_with($target, '/')) {
                // Ensure we have an absolute target
                try {
                    $target = Filesystem::absolute(static::$parameters->getRootDirectory() . Strings::unslash($target));

                } catch (FileNotExistException $e) {
                    throw FileNotExistException::new(tr('The specified target ":target" does not exist', [
                        ':target' => $target
                    ]), $e)->addData([
                        'target'  => $target
                    ]);
                }
            }

            // Execute the target
            switch (Core::getRequestType()) {
                case EnumRequestTypes::api:
                    // no-break
                case EnumRequestTypes::ajax:
                    static::$api_interface = new Api();
                    $output = static::$api_interface->execute($target);
                    break;

                default:
                    $output = static::$template_page->execute($target, $main_content_only);
            }

        } catch (ValidationFailedExceptionInterface $e) {
            Page::executeSystemAfterPageException($e, 400, tr('Page did not catch the following "ValidationFailedException" warning. Executing "system/400" instead'));

        } catch (AuthenticationExceptionInterface $e) {
            Page::executeSystemAfterPageException($e, 401, tr('Page did not catch the following "AuthenticationException" warning. Executing "system/401" instead'));

        } catch (IncidentsExceptionInterface|AccessDeniedExceptionInterface $e) {
            $new_target = $e->getNewTarget();

            if ($new_target) {
                Log::warning(tr('Access denied to target ":target" for user ":user", executing specified new target ":new" instead', [
                    ':target' => $target,
                    ':user'   => Session::getUser()->getDisplayId(),
                    ':new'    => $new_target
                ]));

                Page::execute($target);
            }

            Page::executeSystemAfterPageException($e, 403, tr('Page did not catch the following "IncidentsExceptionInterface or AccessDeniedExceptionInterface" warning. Executing "system/401" instead'));

        } catch (Http404Exception|DataEntryNotExistsExceptionInterface|DataEntryDeletedException $e) {
            Page::executeSystemAfterPageException($e, 404, tr('Page did not catch the following "DataEntryNotExistsException" or "DataEntryDeletedException" warning. Executing "system/404" instead'));

        } catch (Http405Exception|DataEntryReadonlyExceptionInterface|CoreReadonlyExceptionInterface $e) {
            Page::executeSystemAfterPageException($e, 405, tr('Page did not catch the following "Http405Exception or DataEntryReadonlyExceptionInterface or CoreReadonlyExceptionInterface" warning. Executing "system/405" instead'));

        } catch (Http409Exception $e) {
            Page::executeSystemAfterPageException($e, 409, tr('Page did not catch the following "Http409Exception" warning. Executing "system/409" instead'));
        }

        return $output;
    }


    /**
     * Send the generated page output to the client
     *
     * @param string $output
     * @param string $target
     * @param bool $attachment
     * @return never
     */
    #[NoReturn] protected static function sendOutputToClient(string $output, string $target, bool $attachment): never
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

        exit();
    }


    /**
     * Returns an absolute target for the specified target
     *
     * @param string $target
     * @return string
     */
    protected static function getAbsoluteTarget(string $target): string
    {
        return Filesystem::absolute($target, DIRECTORY_WWW . 'pages/');
    }


    /**
     * Will automatically add the timestamp of the specified file as a versioning string
     *
     * This is done for efficient caching where you can pretty much set cache to 10 years as changes are picked up by
     * updated versions of the files
     *
     * @see http://particletree.com/notebook/automatically-version-your-css-and-javascript-files/
     *
     * @param string $url
     * @param string $type
     * @return string
     */
    protected static function versionFile(string $url, string $type): string
    {
        static $minified;

        if (!isset($minified)) {
            // All files are minified or none are
            $minified = (Config::get('web.minified', true) ? '.min' : '');
        }

        if (Config::getBoolean('cache.version-files', true)) {
            // Determine the absolute file path
            // then get timestamp and inject it into the given file
            $file = DIRECTORY_DATA . 'content/cdn/' . LANGUAGE . '/' . $type . '/' . $url . $minified . $type;
            $url  = Strings::untilReverse($url, '.') . '.' . filectime($file) . '.' . Strings::fromReverse($url, '.');
        }

        return $url;
    }
}
