<?php

/**
 * Class Request
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Exception\InvalidRequestTypeException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataStaticContentType;
use Phoundation\Data\Traits\TraitDataStaticExecuted;
use Phoundation\Data\Traits\TraitGetInstance;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Traits\TraitDataStaticRestrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenusInterface;
use Phoundation\Web\Html\Components\Widgets\Menus\Menus;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelsInterface;
use Phoundation\Web\Html\Components\Widgets\Panels\Panels;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;
use Phoundation\Web\Html\Template\Interfaces\TemplatePageInterface;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Exception\Http404Exception;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Enums\EnumDomainAllowed;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Exception\RequestException;
use Phoundation\Web\Requests\Exception\RequestTypeException;
use Phoundation\Web\Requests\Exception\SystemPageNotFoundException;
use Phoundation\Web\Requests\Interfaces\JsonPageInterface;
use Phoundation\Web\Requests\Interfaces\RequestInterface;
use Phoundation\Web\Requests\Restrictions\Exception\RequestHasWrongEncodingException;
use Phoundation\Web\Requests\Restrictions\Exception\RequestMethodRestrictionsException;
use Phoundation\Web\Requests\Restrictions\Exception\RequestHasNoEncodingSpecifiedException;
use Phoundation\Web\Requests\Restrictions\Interfaces\RequestMethodRestrictionsInterface;
use Phoundation\Web\Requests\Restrictions\RequestMethodRestrictions;
use Phoundation\Web\Requests\Traits\TraitDataStaticRouteParameters;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;
use Phoundation\Web\Routing\Route;
use Phoundation\Web\Uploads\Interfaces\UploadHandlersInterface;
use Phoundation\Web\Uploads\UploadHandlers;
use Stringable;
use Templates\Phoundation\AdminLteV3\AdminLteV3;
use Throwable;


class Request implements RequestInterface
{
    use TraitDataStaticContentType;
    use TraitDataStaticExecuted;
    use TraitDataStaticRestrictions;
    use TraitDataStaticRouteParameters;
    use TraitGetInstance;


    /**
     * Singleton instance
     *
     * @var RequestInterface $instance
     */
    protected static RequestInterface $instance;

    /**
     * The type of web request
     *
     * @var EnumRequestTypes $request_type
     */
    protected static EnumRequestTypes $request_type = EnumRequestTypes::unknown;

    /**
     * The file that is currently executed for this request
     *
     * @var PhoFileInterface $_target
     */
    public static PhoFileInterface $_target;

    /**
     * The file that is currently executed for this system page request
     *
     * @var int|null $system_target
     */
    protected static ?int $system_target = null;

    /**
     * The real / initial target that was executed for this request
     *
     * @var PhoFileInterface $main_target
     */
    protected static PhoFileInterface $main_target;

    /**
     * The file that is currently executed for this request
     *
     * @var IteratorInterface $targets
     */
    protected static IteratorInterface $targets;

    /**
     * The data sent to this executed file
     *
     * @var IteratorInterface|null $source
     */
    protected static ?IteratorInterface $source = null;

    /**
     * The unique hash for this page
     *
     * @var string|null $hash
     */
    protected static ?string $hash = null;

    /**
     * The number of page levels that we are recursed in. Typically, this will be 0, but when executing pages from within
     * pages, recursing down, each time it will go up by one until that page is finished, then it will be lowered again
     *
     * @var int $stack_level
     */
    protected static int $stack_level = -1;

    /**
     * The list of metadata that the client accepts
     *
     * @var array|null $accepts
     */
    protected static ?array $accepts = null;

    /**
     * If true, this request will not return content to be displayed, but content to be saved as a file
     *
     * @var bool $attachment
     */
    protected static bool $attachment = false;

    /**
     * If true, this request will return a system HTML page, which ALWAYS is a numeric HTTP code (404 or 500, for
     * example)
     *
     * @var bool $system
     */
    protected static bool $system = false;

    /**
     * The TemplatePage class that helps build the response page
     *
     * @var TemplatePageInterface|JsonPageInterface $page
     */
    protected static TemplatePageInterface|JsonPageInterface $page;

    /**
     * The template class that builds the UI
     *
     * @var TemplateInterface $template
     */
    protected static TemplateInterface $template;

    /**
     * The menus for this page
     *
     * @var MenusInterface $_menus
     */
    protected static MenusInterface $_menus;

    /**
     * The panels for this page
     *
     * @var PanelsInterface $_panels
     */
    protected static PanelsInterface $_panels;

    /**
     * The upload handler for this request
     *
     * @var UploadHandlersInterface $upload_handlers
     */
    protected static UploadHandlersInterface $upload_handlers;

    /**
     * Tracks if the current request is executing a system page or not
     *
     * @var bool $is_system
     */
    protected static bool $is_system = false;

    /**
     * Contains the restrictions for this web request
     *
     * @var RequestMethodRestrictionsInterface $web_restrictions
     */
    protected static RequestMethodRestrictionsInterface $web_restrictions;

    /**
     * Tracks client request headers
     *
     * @var IteratorInterface $headers
     */
    protected static IteratorInterface $headers;


    /**
     * Sets the routing parameters for this request
     *
     * @param RoutingParametersInterface $_parameters
     * @param bool                       $force
     *
     * @return void
     */
    public static function setRoutingParameters(RoutingParametersInterface $_parameters, bool $force = false): void
    {
        if (isset(static::$_parameters)) {
            if (!$force) {
                throw new OutOfBoundsException(tr('Cannot set routing parameters for this request, routing parameters have already been set'));
            }
        }

        if (!$_parameters->getTemplate()) {
            throw new OutOfBoundsException(tr('Cannot use routing parameters ":pattern", it has no template set', [
                ':pattern' => Request::getRoutingParametersObject()
                                    ->getPattern(),
            ]));
        }

        static::$_parameters = $_parameters;
        Request::setTemplateObject($_parameters->getTemplateObject());
    }


    /**
     * Returns the current Template for this page
     *
     * @return TemplateInterface
     */
    public static function getTemplateObject(): TemplateInterface
    {
        if (empty(static::$template)) {
            // Default template is AdminLteV3
            static::$template = new AdminLteV3();
        }

        return static::$template;
    }


    /**
     * Sets the template to the specified template name
     *
     * @param TemplateInterface $_template
     *
     * @return void
     */
    public static function setTemplateObject(TemplateInterface $_template): void
    {
        static::$template = $_template;
        static::$page     = $_template->getPage();
    }


    /**
     * Returns the routing parameters for this request
     *
     * @return RoutingParametersInterface
     */
    public static function getRoutingParametersObject(): RoutingParametersInterface
    {
        if (empty(static::$_parameters)) {
            throw new OutOfBoundsException(tr('Cannot return routing parameters from this request, no routing parameters have been set'));
        }

        return static::$_parameters;
    }


    /**
     * Returns true if the request has routing parameters set
     *
     * @return bool
     */
    public static function hasRoutingParameters(): bool
    {
        return isset(static::$_parameters);
    }


    /**
     * Returns the current tab index and automatically increments it
     *
     * @return MenusInterface
     */
    public static function getMenusObject(): MenusInterface
    {
        if (!isset(static::$_menus)) {
            // Menus have not yet been initialized, do so now.
            static::$_menus = new Menus();
        }

        return static::$_menus;
    }


    /**
     * Sets the current tab index and automatically increments it
     *
     * @param MenusInterface $_menus
     *
     * @return void
     */
    public static function setMenusObject(MenusInterface $_menus): void
    {
        static::$_menus = $_menus;
    }


    /**
     * Returns the current panels configured for this page
     *
     * @return PanelsInterface
     */
    public static function getPanelsObject(): PanelsInterface
    {
        if (!isset(static::$_panels)) {
            // Menus have not yet been initialized, do so now.
            static::$_panels = new Panels();
        }

        return static::$_panels;
    }


    /**
     * Sets the current panels configured for this page
     *
     * @param PanelsInterface $_panels
     *
     * @return void
     */
    public static function setPanelsObject(PanelsInterface $_panels): void
    {
        static::$_panels = $_panels;
    }


    /**
     * Returns the file executed for this request
     *
     * @return bool
     */
    public static function getAttachment(): bool
    {
        return static::$attachment;
    }


    /**
     * Returns the file executed for this request
     *
     * @param bool $attachment
     *
     * @return void
     */
    public static function setAttachment(bool $attachment): void
    {
        static::$attachment = $attachment;
    }


    /**
     * Returns the current TemplatePage used for this page
     *
     * @return TemplatePageInterface|JsonPageInterface|null
     */
    public static function getPageObject(): TemplatePageInterface|JsonPageInterface|null
    {
        if (PLATFORM_WEB) {
            if (Request::isRequestType(EnumRequestTypes::html)) {
                return static::$page;
            }
        }

        return null;
    }


    /**
     * Returns requested main mimetype, or if requested mimetype is accepted or not
     *
     * The function will return true if the specified mimetype is supported, or false, if not
     *
     * @param string $mimetype The mimetype that hopefully is accepted by the client
     *
     * @return mixed True if the client accepts it, false if not
     * @see Request::acceptsLanguages()
     *      code
     *      // This will return true
     *      $result = accepts('image/webp');
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
     * Detects and returns what language the user prefers to see
     *
     * @return string a valid language requested by the user that is supported by the systems configuration
     */
    public static function detectRequestedLanguage(): string
    {
        $languages = config()->getArray('locale.languages.supported', []);

        switch (count($languages)) {
            case 0:
                return LANGUAGE;

            case 1:
                return current($languages);

            default:
                // This is a multilingual website. Ensure language is supported and add language selection to the URL.
                $requested = Request::acceptsLanguages();

                if (empty($requested)) {
                    // Go for default language
                    return config()->getString('locale.languages.default', 'en');
                }

                foreach ($requested as $locale) {
                    if (in_array($locale['language'], $languages)) {
                        // This requested language exists
                        return $locale['language'];
                    }
                }

                // None of the requested languages are supported! Oh noes! Go for default language.
                Notification::new()
                            ->setUrl(Url::new('reports/security/incidents.html')->makeWww())
                            ->setMode(EnumDisplayMode::warning)
                            ->setCode('unsupported-languages-requested')
                            ->setRoles('developer')
                            ->setTitle(tr('Unsupported language requested by client'))
                            ->setMessage(tr('None of the requested languages ":languages" is supported', [
                                ':languages' => $requested,
                            ]))
                            ->send();

                return config()->getString('locale.languages.default', 'en');
        }
    }


    /**
     * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list
     * of languages / locales accepted by the HTTP client
     *
     * @return array The list of accepted languages and locales as specified by the HTTP client
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         * @package   system
     * @see       accepts()
     * @note      : This function is called by the startup system and its output stored in
     *            $core->register['accept_language']. There is typically no need to execute this function on any other
     *            places
     * @version   1.27.0: Added function and documentation
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     */
    public static function acceptsLanguages(): array
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // No accept language headers were specified
            $return = [
                '1.0' => [
                    'language' => config()->getString('locale.languages.default', 'en'),
                    'locale'   => Strings::cut(config()->getString('locale.LC_ALL', 'US'), '_', '.'),
                ],
            ];

        } else {
            $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $headers = Arrays::force($headers, ',');
            $default = array_shift($headers);
            $return  = [
                '1.0' => [
                    'language' => Strings::until($default, '-'),
                    'locale'   => (str_contains($default, '-') ? Strings::from($default, '-') : null),
                ],
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
                $requested = Strings::until($header, ';');
                $requested = [
                    'language' => Strings::until($requested, '-'),
                    'locale'   => (str_contains($requested, '-') ? Strings::from($requested, '-') : null),
                ];

                if (empty(config()->get('locale.languages.supported', [])[$requested['language']])) {
                    continue;
                }

                $return[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($return);

        return $return;
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
        // We are on a command line
        $config = config()->getArray('platforms.web.domains.primary');
        if (array_key_exists('port', $config)) {
            // Return configured WWW port
            return config()->getInteger('platforms.web.domains.primary.port');
        }
        if (substr($config['web'], 4, 1) === 's') {
            // Return default HTTPS port
            return 443;
        }

        // Return default HTTP port
        return 80;
    }


    /**
     * Returns the request method for this page
     *
     * @param bool                     $default        If true, if no referer is available, the current page URL will be returned instead. If
     *                                                 string, and no referer is available, the default string will be returned instead
     *
     * @param EnumDomainAllowed|string $allowed_domain The type of domain that is allowed to be redirected to
     *
     * @return UrlInterface|null
     * @todo To make sense, update this to just ONLY return the referer. If we need more, have another method return that.
     */
    public static function getReferer(string|bool $default = false, EnumDomainAllowed|string $allowed_domain = EnumDomainAllowed::current): ?UrlInterface
    {
        $url = array_get_safe($_SERVER, 'HTTP_REFERER');

        if ($url) {
            return Url::new($url)->checkHost($allowed_domain);
        }

        if ($default) {
            if (is_bool($default)) {
                // We do not have a referer, return the current URL instead
                return Url::newCurrent();
            }

            // Use the specified referrer
            return Url::new($default)->checkHost($allowed_domain)->makeWww();
        }

        // We got nothing...
        return null;
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

        return Strings::until(config()->getString('platforms.web.domains.primary.web'), '://');
    }


    /**
     * Returns the URL for this page
     *
     * @param bool $no_queries
     *
     * @return string
     */
    public static function getUrl(bool $no_queries = false): string
    {
        static $url_queries, $url_noqueries;

        if ($no_queries) {
            if (empty($url_noqueries)) {
                if (PLATFORM_WEB) {
                    $url_noqueries = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . Request::getUri($no_queries);

                } else {
                    // This is a command line process, things like the request scheme are not available!
                    $url_noqueries = config()->getString('platforms.web.domains.primary.web');
                    $url_noqueries = str_replace(':LANGUAGE', Session::getLanguage(), $url_noqueries);
                }
            }

            return $url_noqueries;
        }

        if (empty($url_queries)) {
            if (PLATFORM_WEB) {
                $url_queries = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . Request::getUri($no_queries);

            } else {
                // This is a command line process, things like the request scheme are not available!
                $url_queries = config()->getString('platforms.web.domains.primary.web');
                $url_queries = str_replace(':LANGUAGE', Session::getLanguage(), $url_queries);
            }
        }

        return $url_queries;
    }


    /**
     * Returns the Url object for this page
     *
     * @param bool $no_queries
     *
     * @return UrlInterface
     */
    public static function getUrlObject(bool $no_queries = false): UrlInterface
    {
        static $url_queries, $url_noqueries;

        if ($no_queries) {
            if (empty($url_noqueries)) {
                $url_noqueries = Url::new(Request::getUrl($no_queries));
            }

            return $url_noqueries;
        }

        if (empty($url_queries)) {
            $url_queries = Url::new(Request::getUrl($no_queries));
        }

        return $url_queries;
    }


    /**
     * Return the request URI for this page
     *
     * @note On the CLI platform this method will return "/"
     *
     * @param bool $no_queries
     *
     * @return string|null
     */
    public static function getUri(bool $no_queries = false): ?string
    {
        if (PLATFORM_WEB) {
            return ($no_queries ? Strings::until($_SERVER['REQUEST_URI'], '?') : $_SERVER['REQUEST_URI']);
        }

        return null;
    }


    /**
     * Returns the request URI for this page (WITHOUT domain)
     *
     * @return string
     */
    public static function getRootUri(): string
    {
        if (PLATFORM_WEB) {
            $url = Request::getRootUrl();
            $url = Strings::from($url, '://');
            $url = Strings::from($url, '/');

            return $url;
        }

        return '/';
    }


    /**
     * Return the complete request URL for this page (WITH domain)
     *
     * @param string $type
     *
     * @return string
     */
    public static function getRootUrl(string $type = 'web'): string
    {
        return static::$_parameters->getRootUrl($type);
    }


    /**
     * Will throw an AccessDeniedException if the current session user is "guest"
     *
     * @param string|int|null $new_target
     *
     * @return void
     */
    public static function checkRequireGuestUser(string|int|null $new_target = 'index'): void
    {
        if (Session::getUserObject()->isGuest()) {
            throw AuthenticationException::new(tr('You have to sign out to view this page'))
                                         ->setNewTarget($new_target);
        }
    }


    /**
     * Will throw an AccessDeniedException if the current session user does not have ALL the specified rights
     *
     * @param array|Stringable|string $rights
     * @param string|int|null         $missing_rights_target
     * @param string|int|null         $guest_target
     *
     * @return void
     */
    public static function requiresAllRights(array|Stringable|string $rights, string|int|null $missing_rights_target = 403, string|int|null $guest_target = 401): void
    {
        Request::checkRequireNotGuestUser($guest_target);

        if (!Session::getUserObject()->hasAllRights($rights)) {
            throw AccessDeniedException::new(tr('You do not have the required rights to view this page'))
                                       ->setNewTarget($missing_rights_target);
        }
    }


    /**
     * Will throw an AccessDeniedException if the current session user is "guest"
     *
     * @param string|int|null $new_target
     *
     * @return void
     */
    public static function checkRequireNotGuestUser(string|int|null $new_target = '401'): void
    {
        if (Session::getUserObject()->isGuest()) {
            throw AuthenticationException::new(tr('You have to sign in to view this page'))
                                         ->setNewTarget($new_target);
        }
    }


    /**
     * Will throw an AccessDeniedException if the current session user does not have SOME of the specified rights
     *
     * @param array|string    $rights
     * @param string|int|null $missing_rights_target
     * @param string|int|null $guest_target
     *
     * @return void
     */
    public static function requiresSomeRights(array|string $rights, string|int|null $missing_rights_target = 403, string|int|null $guest_target = 401): void
    {
        Request::checkRequireNotGuestUser($guest_target);

        if (!Session::getUserObject()->hasSomeRights($rights)) {
            throw AccessDeniedException::new(tr('You do not have the required rights to view this page'))
                                       ->setNewTarget($missing_rights_target);
        }
    }


    /**
     * Will throw an InvalidRequestTypeException if the current request type does not match the specified request type
     *
     * @param EnumRequestTypes $type The call type you wish to compare to
     *
     * @return void
     */
    public static function checkRequestType(EnumRequestTypes $type): void
    {
        if (!Request::isRequestType($type)) {
            throw new InvalidRequestTypeException(tr('Requested request type ":requested" does not match current request type ":current"', [
                ':required' => $type,
                ':current'  => static::$request_type,
            ]));
        }
    }


    /**
     * Will return true if the specified $type is equal to the request type
     *
     * @param EnumRequestTypes $type The call type you wish to compare to
     *
     * @return bool This function will return true if $type matches core::callType, or false if it does not.
     */
    public static function isRequestType(EnumRequestTypes $type): bool
    {
        return ($type === static::$request_type);
    }


    /**
     * Returns the data sent to this executed file
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return void
     */
    public static function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): void
    {
        if (empty(static::$source)) {
            static::$source = new Iterator();
        }
        static::$source->add($value, $key, $skip_null_values, $exception);
    }


    /**
     * Returns the value for the specified data key, if it exist. If not, the default value will be returned
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool                        $exception
     *
     * @return mixed
     */
    public static function get(Stringable|string|float|int $key, mixed $default = null, bool $exception = true): mixed
    {
        return static::$source?->get($key, $default, $exception);
    }


    /**
     * Sets the value for the specified data key, if exist. If not, the default value will be returned
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return void
     */
    public static function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = false): void
    {
        if (empty(static::$source)) {
            static::$source = new Iterator();
        }

        static::$source->set($value, $key, $skip_null_values);
    }


    /**
     * Returns the data sent to this executed file
     *
     * @return IteratorInterface|null
     */
    public static function getSource(): ?IteratorInterface
    {
        return static::$source;
    }


    /**
     * Returns the number of pages we have recursed into.
     *
     * Returns 0 for the first page, 1 for the next, etc.
     *
     * @return int
     */
    public static function getStackLevel(): int
    {
        return static::$stack_level;
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
     * Will throw a Http404Exception when this page is executed directly from Route
     *
     * @param string|null $message
     *
     * @return void
     */
    public static function cannotBeExecutedDirectly(?string $message = null): void
    {
        if (Request::isExecutedDirectly()) {
            if (!$message) {
                $message = tr('The target ":target" cannot be accessed directly', [
                    ':target' => Request::getTargetObject()
                                       ->getSource('root'),
                ]);
            }
            throw Http404Exception::new($message)
                                  ->makeWarning();
        }
    }


    /**
     * Returns if this page is executed directly from Route, or if its executed by executeReturn() call
     *
     * @return bool
     */
    public static function isExecutedDirectly(): bool
    {
        return !static::$stack_level;
    }


    /**
     * Returns the file executed for this request
     *
     * @return PhoFileInterface
     */
    public static function getTargetObject(): PhoFileInterface
    {
        return static::$_target;
    }


    /**
     * Returns the file executed for this system page request
     *
     * @return int|null
     */
    public static function getSystemTarget(): ?int
    {
        return static::$system_target;
    }


    /**
     * Redirects to the default page for this user if the user has configured one and this is the first page after
     * signing in
     *
     * @return void
     */
    protected static function redirectToDefaultPage(): void
    {
        $page = Session::getDefaultPage();

        if ($page) {
            if (array_get_safe($_SERVER, 'SCRIPT_URI') === $page) {
                // The current location is the default page, we are good
                return;
            }

            // Redirect to the default page instead
            Response::redirect($page);
        }
    }


    /**
     * Sets the target for this request
     *
     * @param PhoFileInterface|string|int $_target
     *
     * @return void
     */
    protected static function setTarget(PhoFileInterface|string|int $_target): void
    {
        // Get a target string
        if (is_integer($_target)) {
            $_target = 'system/' . abs($_target) . '.php';

        } elseif ($_target instanceof PhoFileInterface) {
            $_target = $_target->getSource();
        }

        // Determine the target request type
        Request::detectRequestType($_target);

        // Determine the target file that is to be executed
        $_target         = Request::ensureRequestPathPrefix($_target);
        static::$_target = PhoFile::new($_target, Request::getRestrictionsObject())->makeAbsolute(DIRECTORY_WEB);
        static::$_target->checkRestrictions(false);

        Request::getTargets()->add(static::$_target);
        Request::addExecutedPath($_target); // TODO We should get this from targets

        // Store request hash used for caching, store real / original target
        if (empty(static::$main_target)) {
            if (PLATFORM_WEB) {
                static::$hash = sha1($_SERVER['REQUEST_URI']);

            } else {
                static::$hash = sha1(Strings::force($_SERVER['argv']));
            }

            static::$main_target = static::$_target;

            if (PLATFORM_WEB) {
                // Start the main web target buffer
                ob_start();
            }
        }
    }


    /**
     * Checks if the current page request is a GET request, throws a RequestMethodRestrictionsException if not
     *
     * @param string $action
     *
     * @return void
     * @throws RequestMethodRestrictionsException
     */
    public static function checkGetRequestMethod(string $action): void
    {
        if (!Request::isGetRequestMethod()) {
            throw new RequestMethodRestrictionsException(tr('Cannot ":action", this is only allowed with a GET request', [
                ':action' => $action,
            ]));
        }
    }


    /**
     * Checks if the current page request is a POST request, throws a RequestMethodRestrictionsException if not
     *
     * @param string $action
     *
     * @return void
     * @throws RequestMethodRestrictionsException
     */
    public static function checkPostRequestMethod(string $action): void
    {
        if (!Request::isPostRequestMethod()) {
            throw new RequestMethodRestrictionsException(tr('Cannot ":action", this is only allowed with a POST request', [
                ':action' => $action,
            ]));
        }
    }


    /**
     * Returns if this request is a GET method
     *
     * @param bool $strict
     *
     * @return bool
     */
    public static function isGetRequestMethod(bool $strict = true): bool
    {
        if (PLATFORM_WEB) {
            // As soon as we inquire about the request method being GET, Phoundation will assume that GET is allowed
            Request::getMethodRestrictionsObject()->allow(EnumHttpRequestMethod::get);
            return Request::isRequestMethod(EnumHttpRequestMethod::get, $strict) or Request::isRequestMethod(EnumHttpRequestMethod::post);
        }

        return false;
    }


    /**
     * Returns if this request is a POST method
     *
     * @return bool
     */
    public static function isPostRequestMethod(): bool
    {
        if (PLATFORM_WEB) {
            // As soon as we inquire about the request method being POST, Phoundation will assume that POST is allowed
            Request::getMethodRestrictionsObject()->allow(EnumHttpRequestMethod::post);
            return Request::isRequestMethod(EnumHttpRequestMethod::post);
        }

        return false;
    }


    /**
     * Returns if this request is the specified method
     *
     * @param EnumHttpRequestMethod $method
     * @param bool                  $strict
     *
     * @return bool
     */
    public static function isRequestMethod(EnumHttpRequestMethod $method, bool $strict = true): bool
    {
        if (!$strict) {
            if ($method === EnumHttpRequestMethod::upload) {
                // Upload  is not a real method, its POST
                $method = EnumHttpRequestMethod::post;
            }

            if ($method === EnumHttpRequestMethod::head) {
                // HEAD is GET without a return body, treat it like GET
                $method = EnumHttpRequestMethod::get;
            }
        }

        return Request::getRequestMethod() === $method;
    }


    /**
     * Returns the request method for this page
     *
     * @return EnumHttpRequestMethod|null
     */
    public static function getRequestMethod(): ?EnumHttpRequestMethod
    {
        if (PLATFORM_WEB) {
            return EnumHttpRequestMethod::from(strtolower($_SERVER['REQUEST_METHOD']));
        }

        return null;
    }


    /**
     * Returns the file executed for this request
     *
     * @return IteratorInterface
     */
    public static function getTargets(): IteratorInterface
    {
        if (empty(static::$targets)) {
            static::$targets = new Iterator();
        }

        return static::$targets;
    }


    /**
     * Determines what type of web request was made
     *
     * @param string $target
     *
     * @return void
     */
    protected static function detectRequestType(string $target): void
    {
        if (PLATFORM_CLI) {
            // We are running on the command line
            $request_type = EnumRequestTypes::cli;

        } else {
            $uri = Strings::from($target, 'web/');

            if (str_starts_with($uri, 'ajax/')) {
                $request_type = EnumRequestTypes::ajax;

            } elseif (str_starts_with($uri, 'api/') or (str_starts_with($_SERVER['SERVER_NAME'], 'api'))) {
                $request_type = EnumRequestTypes::api;

            } elseif (str_starts_with($_SERVER['SERVER_NAME'], 'cdn')) {
                $request_type = EnumRequestTypes::file;

            } elseif (is_numeric(substr($target, -3, 3))) {
                // TODO Should this not be set by the system?
                $request_type = EnumRequestTypes::system;

            } else {
                $request_type = EnumRequestTypes::html;
            }
        }

        Request::setRequestType($request_type);
    }


    /**
     * Ensures that this session user has all the specified rights, or a redirect will happen
     *
     * @param array|string    $rights
     * @param string|int|null $rights_redirect
     * @param string|int|null $guest_redirect
     *
     * @return bool
     */
    public static function hasRightsOrRedirect(array|string $rights, string|int|null $rights_redirect = null, string|int|null $guest_redirect = null): bool
    {
        $return = true;

        if (!Session::getUserObject()->hasAllRights($rights, null)) {
            $return = false;
        }

        if ($return or Session::getUserObject()->hasAllRights($rights, 'god')) {
            // The user has all the required rights, but did the user sign in through a sign-in key?
            // If so, then there may be restrictions!
            if (!Session::getSignInKey()) {
                // Well, then, all fine and dandy!
                return $return;
            }

            // Check sign-key restrictions and if those are okay, we are good to go
            Request::hasSignKeyRestrictions($rights, static::$_target->getSource());
            return $return;
        }

        // Is this a system page though? System pages require no rights to be viewed.
        if (Request::getRequestType() === EnumRequestTypes::system) {
            // Hurrah, it is a bo, eh, system page! System pages require no rights. Everyone can see 404, 500, etc...
            return true;
        }

        // Is this a guest? Guests have no rights and can only see system pages and pages that require no rights
        if (Session::getUserObject()->isGuest()) {
            // This user has no rights at all, send it to sign-in page
            if (!$guest_redirect) {
                $guest_redirect = 'sign-in';
            }

            $current        = Response::getRedirect(Url::newCurrent());
            $guest_redirect = Url::new($guest_redirect)
                                 ->makeWww()
                                 ->addRedirect($current);

            Incident::new()
                    ->setType('401 - Unauthorized')
                    ->setSeverity(EnumSeverity::low)
                    ->setTitle(tr('Guest user has no access to target page'))
                    ->setBody(tr('Guest user has no access to target page ":target" (real target ":real_target" requires rights ":rights"). Redirecting to ":redirect"', [
                        ':target'      => static::$_target->getSource('web'),
                        ':real_target' => static::$_target->getSource('web'),
                        ':redirect'    => $guest_redirect,
                        ':rights'      => Strings::force($rights, ', '),
                    ]))
                    ->setDetails([
                        'user'        => 0,
                        'uri'         => Request::getUri(),
                        'target'      => static::$_target->getSource('web'),
                        'real_target' => static::$_target->getSource('web'),
                        'rights'      => $rights,
                    ])
                    ->save()
                    ->throw(AuthenticationException::class);
        }

        // This user is missing rights
        if (!$rights_redirect) {
            $rights_redirect = 403;
        }

        // Do the specified rights exist at all? If they  are not defined then no wonder this user does not have them
        if (Rights::getNotExist($rights)) {
            // One or more of the rights do not exist
            Incident::new()
                    ->setType('Non existing rights')
                    ->setSeverity(in_array('admin', Session::getUserObject()->getRightsObject()->getMissing($rights)) ? EnumSeverity::high : EnumSeverity::medium)
                    ->setTitle(tr('The requested rights ":rights" do not exist on this system and was not automatically created', [
                        ':rights'      => Strings::force(Rights::getNotExist($rights), ', '),
                    ]))
                    ->setBody(tr('The requested rights ":rights" for target page ":target" (real target ":real_target") do not exist on this system and was not automatically created. Redirecting to ":redirect"', [
                        ':rights'      => Strings::force(Rights::getNotExist($rights), ', '),
                        ':target'      => static::$_target->getSource('web'),
                        ':real_target' => static::$main_target->getSource('web'),
                        ':redirect'    => $rights_redirect,
                    ]))
                    ->setDetails([
                        'user'           => Session::getUsersLogId(),
                        'uri'            => Request::getUri(),
                        'target'         => static::$_target->getSource('web'),
                        'real_target'    => static::$main_target->getSource('web'),
                        'rights'         => $rights,
                        'missing_rights' => Rights::getNotExist($rights),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw(AccessDeniedException::class);

        } else {
            // Registered user does not have the required rights
            Incident::new()
                    ->setType('403 - Forbidden')
                    ->setSeverity(in_array('admin', Session::getUserObject()->getRightsObject()->getMissing($rights)) ? EnumSeverity::high : EnumSeverity::medium)
                    ->setTitle(tr('User ":user" does not have the required rights ":rights"', [
                        ':user'        => Session::getUsersLogId(),
                        ':rights'      => Session::getUserObject()->getRightsObject()->getMissing($rights),
                    ]))
                    ->setBody(tr('User ":user" does not have the required rights ":rights" for target page ":target" (real target ":real_target"). Executing "system/:redirect" instead', [
                        ':user'        => Session::getUsersLogId(),
                        ':rights'      => Session::getUserObject()->getRightsObject()->getMissing($rights),
                        ':target'      => static::$_target->getSource('web'),
                        ':real_target' => static::$main_target->getSource('web'),
                        ':redirect'    => $rights_redirect,
                    ]))
                    ->setDetails([
                        'user'        => Session::getUsersLogId(),
                        'uri'         => Request::getUri(),
                        'target'      => static::$_target->getSource('web'),
                        'real_target' => static::$main_target->getSource('web'),
                        'rights'      => Session::getUserObject()->getRightsObject()->getMissing($rights),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw(AccessDeniedException::class);
        }
    }


    /**
     * Returns true if the current URL has sign-key restrictions
     *
     * @param array|string $rights
     * @param string       $target
     *
     * @return void
     */
    protected static function hasSignKeyRestrictions(array|string $rights, string $target): void
    {
        $key = Session::getSignInKey();

        // User signed in with "sign-in" key that may have additional restrictions
        if (!Request::isRequestType(EnumRequestTypes::html)) {
            Incident::new()
                    ->setType('401 - Unauthorized')
                    ->setSeverity(EnumSeverity::low)
                    ->setTitle(tr('Session keys cannot be used on ":type" requests', [
                        ':type' => Request::getRequestType(),
                    ]))
                    ->setDetails([
                        'user'         => $key->getUserObject()->getLogId(),
                        'uri'          => Request::getUri(),
                        'target'       => static::$_target->getSource('web'),
                        'real_target'  => Strings::from($target, DIRECTORY_ROOT),
                        'rights'       => $rights,
                        ':sign_in_key' => $key->getUuid(),
                    ])
                    ->save()
                    ->throw(AccessDeniedException::class);
        }

        if (!$key->signKeyAllowsUrl(Url::newCurrent(), $target)) {
            Incident::new()
                    ->setType('401 - Unauthorized')
                    ->setSeverity(EnumSeverity::low)
                    ->setTitle(tr('Cannot open URL ":url", sign in key ":uuid" does not allow navigation beyond ":allow"', [
                        ':url'   => Url::newCurrent(),
                        ':allow' => $key->getRedirect(),
                        ':uuid'  => $key->getUuid(),
                    ]))
                    ->setDetails([
                        ':url'      => Url::newCurrent(),
                        ':users_id' => $key->getUsersId(),
                        ':allow'    => $key->getRedirect(),
                        ':uuid'     => $key->getUuid(),
                    ])
                    ->save()
                    ->throw(AccessDeniedException::class);
        }
    }


    /**
     * Returns the type of web request type is running. Will be one of html, ajax, api, or file.
     *
     * @return EnumRequestTypes
     */
    public static function getRequestType(): EnumRequestTypes
    {
        return static::$request_type;
    }


    /**
     * Attempts to set the request type to the specified type
     *
     * @param EnumRequestTypes $request_type
     *
     * @return void
     */
    public static function setRequestType(EnumRequestTypes $request_type): void
    {
        if (static::$request_type !== EnumRequestTypes::unknown) {
            $fail = true;

            // We already have a request type determined, so we already have an appropriate response object initialized as well. We cannot just change from
            // generating a web page to returning an API output, for example, so check if the change is allowed
            switch ($request_type) {
                case static::$request_type:
                    // The new request type matches the initial request type, we can continue. The response will not be reset, so we are done here
                    return;

                case EnumRequestTypes::system:
                    // Any HTML request can cause a 404, 500, etc., so any HTML request can switch to a system page
                    $fail = false;
                    break;

                case EnumRequestTypes::file:
                    // Any HTML request may generate and return a file, so any HTML request can switch to a file
                    switch (static::$request_type) {
                        case EnumRequestTypes::html:
                            // no break

                        case EnumRequestTypes::file:
                            break;

                        default:
                            $fail = false;
                    }

                    break;
            }

            if ($fail) {
                throw new RequestTypeException(tr('Cannot process target ":target" it has request type ":current" while the current request type is ":new"', [
                    ':target'  => static::$_target,
                    ':new'     => $request_type,
                    ':current' => static::$request_type,
                ]));
            }

            // Clean any current responses that are in buffer
            Response::clean();
        }

        static::$request_type = $request_type;
        static::checkRequestTypeEnabled();
    }


    /**
     * Returns true if the current request type is enabled, false if not
     *
     * @param bool $default [true] Sets the default value that will be returned if the configuration path does not exist
     * @return bool
     */
    protected static function getConfigRequestTypeEnabled(bool $default = true): bool
    {
        return config()->getBoolean('platforms.web.request-types.' . static::$request_type->value . '.enabled', $default);
    }


    /**
     * Checks if the specified request type is enabled per configuration
     *
     * @return void
     */
    protected static function checkRequestTypeEnabled(): void
    {
        // Set up the response object for this request
        switch (static::$request_type) {
            case EnumRequestTypes::api:
                // Manually startup session for API requests
                // TODO Implement support for API requests and sessions, user access, etc
                //Session::start();
                $enabled = static::getConfigRequestTypeEnabled(false); // API calls by default are disabled
                break;

            case EnumRequestTypes::unsupported:
                throw new OutOfBoundsException(tr('Unsupported web request type ":type" encountered', [
                    ':type' => Request::getRequestType(),
                ]));

            case EnumRequestTypes::unknown:
                throw new OutOfBoundsException(tr('Unknown web request type ":type" encountered', [
                    ':type' => Request::getRequestType(),
                ]));

            default:
                $enabled = static::getConfigRequestTypeEnabled();
        }

        if (!$enabled) {
            // Whoopsie, this is not allowed!
            Incident::new()
                    ->setSeverity(EnumSeverity::high)
                    ->setType(ts('request-type-disabled'))
                    ->setTitle(ts('Received web request type which is disabled by configuration'))
                    ->setBody(ts('Received web ":type" type request ":url" from IP address ":ip" on the web platform which is disabled by configuration', [
                        ':type' => static::$request_type->value,
                        ':url'  => Route::getRequest(),
                        ':ip'   => $_SERVER['REMOTE_ADDR'],
                    ]))
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw(AccessDeniedException::class);
        }
    }


    /**
     * Executes the specified system page
     *
     * @param int            $http_code          The system page to execute. If specified as a negative number, the page will be executed forcibly, even if
     *                                           debug mode is enabled
     * @param Throwable|null $e           [null] The (optional) exception that caused this system page to be executed
     * @param string|null    $message     [null] The optional user-visible message to add to this system page
     * @param string|null    $log_message [null] The optional log-only message to add to this system page
     *
     * @return never
     * @throws RequestException in case the system page failed to execute correctly
     */
    #[NoReturn] public static function executeSystem(int $http_code, ?Throwable $e = null, ?string $message = null, ?string $log_message = null): never
    {
        try {
            if ($e and (Debug::isEnabled() and $http_code > 0)) {
                // In debug mode we do not show pretty pages, we dump all the exception data on screen
                throw $e;
            }

            static::$system_target = $http_code;
            static::$is_system     = true;

            if (!Session::hasStartedUp()) {
                // Start session here because the reply will need it
                Session::start();
            }

            Response::checkForceRedirect();

            SystemRequest::new()->execute(abs($http_code), $e, $message, $log_message);

        } catch (Throwable $f) {
            throw RequestException::new(tr('Failed to execute system page ":page"', [':page' => $http_code]), $f)
                                  ->setData(['original_exception' => $e]);
        }
    }


    /**
     * Returns true if this request is executing a system page
     *
     * @return bool
     */
    public static function isSystemPage(): bool
    {
        return static::$is_system;
    }

    /**
     * Execute the specified target for this request and returns the output
     *
     * @param PhoFileInterface|string|int $target
     *
     * @return string|null
     */
    public static function execute(PhoFileInterface|string|int $target): ?string
    {
        return Request::doExecute($target, false, false);
    }


    /**
     * Execute the specified target for this request and returns the output
     *
     * @param PhoFileInterface|string $target
     * @param bool                    $die
     *
     * @return string|null
     */
    public static function executeAndFlush(PhoFileInterface|string $target, bool $die = false): ?string
    {
        return Request::doExecute($target, true, $die);
    }


    /**
     * Execute the specified target for this request and returns the output
     *
     * @param PhoFileInterface|string|int $target
     * @param bool                        $flush
     *
     * @return string|null
     */
    protected static function doExecute(PhoFileInterface|string|int $target, bool $flush): ?string
    {
        // Set target and check if we have this target in the cache
        try {
            Request::setTarget($target);
            static::$stack_level++;

        } catch (FileNotExistException $e) {
            Request::processFileNotFoundException($e, $target);
        }

        if (PLATFORM_CLI) {
            if (Log::getVerbose()) {
                Log::action(ts('Executing program ":program"', [':program' => static::$_target->getRootname()]));
            }

            if (static::$stack_level > 0) {
                // This is a CLI sub command, execute it directly with output buffering and return the output
                ob_start();
                $return = execute();

            } else {
                // This is a CLI command, execute it directly
                execute();
                $return = null;
            }

        } else {
            ob_start();
            Request::preparePageVariable();

            switch (Request::getRequestType()) {
                case EnumRequestTypes::html:
                    if (!Request::getSystem()) {
                        // Users always have access to /my/ URL's
                        if (!Request::getConfigAllowMyPages() or !Request::isMyPage()) {
                            // Check if the user has access to the requested page, then check if the user should be force redirected
                            Request::hasRightsOrRedirect(Request::getUrlObject()->getRights());
                        }

                        Response::checkForceRedirect();
                    }

                    break;

                case EnumRequestTypes::ajax:
                    if (!Request::getSystem()) {
                        // Check if the user has access to the requested page
                        Request::hasRightsOrRedirect(Request::getUrlObject()->getRights());
                    }

                    break;

                case EnumRequestTypes::api:
                    // TODO Implement support for sessions, user rights, etc for API's
            }

            // Execute the current target
            $return = Request::executeWebTarget($flush);
        }

        static::$stack_level--;

        if ($flush or (static::$stack_level < 0)) {
            // The stack is empty, there is nothing executing above this. Assume HTTP headers have been set by this
            // point, and send the output to the client
            Request::checkDataValidated();
            Response::addOutput($return);
            Response::send(true);
        }

        // Return the output to the page that executed this page
        return $return;
    }


    /**
     * Returns the boolean value for the configuration path "security.web.pages.my.allow"
     *
     * @return bool
     */
    public static function getConfigAllowMyPages(): bool
    {
        return config()->getBoolean('security.web.pages.my.allow', true);
    }


    /**
     * Returns true if the current request is for a "my" page, a page starting with /my/
     *
     * @return bool
     */
    public static function isMyPage(): bool
    {
        if (Session::getUserObject()->isGuest()) {
            // Guest users do not have access to /my/ pages
            return false;
        }

        $path = Request::getUrlObject()->getPath();
        $path = Strings::from($path, '/', instance: 2);

        return str_starts_with($path, 'my/');
    }


    /**
     * Checks that all untrusted data was validated, will throw ValidatorException if not
     *
     * @return void
     * @throws ValidatorException
     */
    protected static function checkDataValidated(): void
    {
        if (PLATFORM_WEB) {
            if (!config()->getBoolean('security.validation.require', true)) {
                // Non-validated pages are allowed
                return;
            }

            // Ensure GET and POST data has been validated!
            if (Request::isRequestMethod(EnumHttpRequestMethod::post)) {
                PostValidator::new()->checkHasBeenValidated();
            }

            GetValidator::new()->checkHasBeenValidated();

        } else {
            ArgvValidator::new()->checkHasBeenValidated();
        }
    }


    /**
     * Process a FileNotFoundException
     *
     * @param FileNotExistException   $e
     * @param PhoFileInterface|string $target
     *
     * @return never
     * @throws FileNotExistException
     */
    #[NoReturn] protected static function processFileNotFoundException(FileNotExistException $e, PhoFileInterface|string $target): never
    {
        if (!Session::hasStartedUp()) {
            // Start session here because processing the file not found will need it
            Session::start();
        }

        Response::checkForceRedirect();

        if (Request::getSystem()) {
            // This is not a normal request, this is a system request. System pages SHOULD ALWAYS EXIST, but if they
            // do not, hard fail because this method will normally execute a system page, and we just saw those do not
            // exist for some reason
            throw new SystemPageNotFoundException(tr('The requested system page ":page" does not exist', [
                ':page' => $target,
            ]));
        }

        if (static::$stack_level >= 0) {
            Log::warning(ts('Sub target ":target" does not exist, displaying 500 page instead', [
                ':target' => $target,
            ]));

            throw $e;
        }

        Log::warning(ts('Main target ":target" does not exist, displaying 404 page instead', [
            ':target' => $target,
        ]));

        throw Http404Exception::new(tr('The requested page ":page" does not exist', [
            ':page' => $target,
        ]));
    }


    /**
     * Returns if the request is a system page
     *
     * @return bool
     */
    public static function getSystem(): bool
    {
        return static::$system;
    }


    /**
     * Sets if the request is a system page
     *
     * @param bool $system
     *
     * @return void
     */
    public static function setSystem(bool $system): void
    {
        if (static::$system and ($system !== static::$system)) {
            throw new OutOfBoundsException(tr('This is now a system web request, this request cannot be changed into a non system web request'));
        }

        static::$system = $system;
    }


    /**
     * Returns the value for the specified submit button for POST requests
     *
     * This method will search for and -if found- return the value for the specified HTTP POST key. By default it will
     * search for the POST name "submit". If can scan in prefix mode, where it will search for HTTP POST keys that start
     * with the specified POST key. If it finds a matching entry, that first entry will be returned.
     *
     * This method will not (and cannot) return if ANY button was pressed as it cannot see the difference between a
     * button and a form value. A specific button name must be specified.
     *
     * @note This method will return NULL in case the requested submit button was not pressed
     * @note For non POST requests this method will always return NULL
     * @note For buttons that have an empty value (null, ""), this method will return TRUE instead
     * @note The specified POST key, if found, will be removed from the POST array. If a prefix scan was requested, the
     *       found (and returned) POST key will be removed from the POST array.
     * @note The button values will be removed from the POST array but kept in cache, so calling this method twice for
     *       the same button will return the same value, even though it was removed from POST after the first call.
     * @note If $return_key AND $prefix were specified, this method will return the POST key FROM the specified
     *       $post_key. So if $post_key was "delete_" and the found key was "delete_2342897342", this method will return
     *       the value "2342897342" instead of "delete_2342897342"
     *
     * @param string $post_key
     * @param bool   $prefix     Will not return the specified POST $post_key value but scan for a POST key that starts
     *                           with $post_key, and return that value.
     * @param bool   $return_key If true, will return the found POST_KEY instead of the value.
     *
     * @return string|true|null
     */
    public static function getSubmitButton(string $post_key = 'submit-button', bool $prefix = false, bool $return_key = false): string|true|null
    {
        return PostValidator::new()->getSubmitButton($post_key, $prefix, $return_key);
    }


    /**
     * This method will prepare the static::$page variable
     *
     * @return void
     */
    protected static function preparePageVariable(): void
    {
        switch (Request::getRequestType()) {
            case EnumRequestTypes::api:
                static::$page = new ApiPage();
                break;

            case EnumRequestTypes::ajax:
                static::$page = new AjaxPage();

                if (!static::$stack_level) {
                    // Start session only for AJAX and HTML requests
                    Session::start();
                }

                break;

            default:
                // static::$page should already be defined at this stage
                if (empty(static::$page)) {
                    throw new OutOfBoundsException(tr('Cannot execute HTML page request for target ":target", no template specified', [
                        ':target' => static::$_target,
                    ]));
                }

                if (!static::$stack_level) {
                    // Start session only for AJAX and HTML requests
                    // Initialize the flash messages
                    Session::start();
                    Response::getFlashMessagesObject()->addSource(Session::getFlashMessagesObject());
                }
        }
    }


    /**
     * Executes the specified target, processes default exceptions, and returns the results
     *
     * @param bool $flush
     *
     * @return string|null
     */
    protected static function executeWebTarget(bool $flush): ?string
    {
        // Execute the specified target file
        switch (Request::getRequestType()) {
            case EnumRequestTypes::api:
                Log::action(ts('Executing API page ":target" on stack level ":level" with in language ":language" and sending output as API page', [
                    ':target'   => Strings::from(Request::getTargetObject(), '/web/'),
                    ':level'    => static::$stack_level,
                    ':language' => LANGUAGE,
                ]), (static::$stack_level ? 5 : 7));
                break;

            case EnumRequestTypes::ajax:
                Log::action(ts('Executing AJAX page ":target" on stack level ":level" with in language ":language" and sending output as AJAX API page', [
                    ':target'   => Strings::from(Request::getTargetObject(), '/web/'),
                    ':level'    => static::$stack_level,
                    ':language' => LANGUAGE,
                ]), (static::$stack_level ? 5 : 7));
                break;

            default:
                Log::action(ts('Executing program ":target" on stack level ":level" with template ":template" in language ":language" and sending output as HTML web page', [
                    ':target'   => Strings::from(Request::getTargetObject(), '/web/'),
                    ':template' => static::$template->getName(),
                    ':level'    => static::$stack_level,
                    ':language' => LANGUAGE,
                ]), (static::$stack_level ? 5 : 7));
        }

        // Hide $_FILES data in case files were uploaded
        if (count($_FILES)) {
            UploadHandlers::hideData();
        }

        // Prepare page, increase the stack counter, and execute the target
        if (!$flush and static::$stack_level) {
            // Execute only the file and return the output
            return execute();
        }

        if (!Request::isSystemPage()) {
            Response::addHeadDataAttribute(Url::getBase(), 'base-url');
        }

        // Execute the entire page and return the output
        $results = static::$page->execute();

        // Are all request method restrictions satisfied? Only check non-system pages, system pages will allow all
        if (!static::$is_system) {
            Request::getMethodRestrictionsObject()->checkRestrictions();
        }

        return $results;
    }


    /**
     * Ensures that this request target path is absolute, or has the correct prefix
     *
     * @param string $target
     *
     * @return string
     */
    protected static function ensureRequestPathPrefix(string $target): string
    {
        if (str_starts_with($target, DIRECTORY_DATA)) {
            // The specified target is an absolute path that needs no adjustment
            return $target;
        }

        $target = Strings::ensureBeginsNotWith($target, '/');

        return match (Request::getRequestType()) {
            EnumRequestTypes::api     => Strings::ensureBeginsWith($target, 'api/'),
            EnumRequestTypes::ajax    => Strings::ensureBeginsWith($target, 'ajax/'),
            EnumRequestTypes::file    => Strings::ensureBeginsWith($target, 'files/'),
            EnumRequestTypes::html,
            EnumRequestTypes::system  => Strings::ensureBeginsWith($target, 'pages/'),
            default                   => throw new OutOfBoundsException(tr('Unsupported request type ":request" for this process', [
                ':request' => Request::getRequestType(),
            ])),
        };
    }


    /**
     * Returns the upload handler for this request
     *
     * @return UploadHandlersInterface
     */
    public static function getFileUploadHandlersObject(): UploadHandlersInterface
    {
        if (empty(static::$upload_handlers)) {
            static::$upload_handlers = new UploadHandlers();
        }

        return static::$upload_handlers;
    }


    /**
     * Returns the "restrictions" object for this request
     *
     * @return RequestMethodRestrictionsInterface
     */
    public static function getMethodRestrictionsObject(): RequestMethodRestrictionsInterface
    {
        if (empty(static::$web_restrictions)) {
            static::$web_restrictions = new RequestMethodRestrictions();
        }

        return static::$web_restrictions;
    }


    /**
     * Returns all request headers
     *
     * @return IteratorInterface
     */
    public static function getHeaders(): IteratorInterface
    {
        if (empty(static::$headers)) {
            static::$headers = Iterator::new(getallheaders());
        }

        return static::$headers;
    }


    /**
     * Returns the value for the specified request header
     *
     * @param string $name      The header which should be returned
     * @param bool   $exception If true will throw an exception if the header does not exist
     *
     * @return string|null
     */
    public static function getHeader(string $name, bool $exception = false): ?string
    {
        return Request::getHeaders()->get($name, exception: $exception);
    }


    /**
     * Returns the encoding for user data as specified by the client
     *
     * @return string|null
     */
    public static function getEncoding(): ?string
    {
        static $encoding = null;

        if ($encoding === null) {
            $encoding = Request::getHeader('Content-Encoding');
            $encoding = Strings::from((string) $encoding, 'charset=');
        }

        return get_null($encoding);
    }


    /**
     * Returns the encoding for user data as specified by the client
     *
     * @param string|null $encoding
     * @param bool|null   $strict
     *
     * @return bool
     */
    public static function hasEncoding(?string $encoding = null, ?bool $strict = null): bool
    {
        // Apply defaults to arguments
        $encoding = $encoding ?? Request::getEncoding();
        $strict   = $strict   ?? config()->getBoolean('security.validation.encoding.strict', false);

        if ($encoding) {
            return Response::getEncoding() === Request::getEncoding();
        }

        if ($strict) {
            throw RequestHasNoEncodingSpecifiedException::new(tr('Cannot accept client request, encoding has not been specified'));
        }

        // Client specified no encoding, assume it is ok
        return true;
    }


    /**
     * Returns the encoding for user data as specified by the client
     *
     * @param bool|null $strict
     *
     * @return void
     */
    public static function checkEncoding(?bool $strict = null): void
    {
        if (Request::hasEncoding(Response::getEncoding(), $strict)) {
            return;
        }

        throw RequestHasWrongEncodingException::new(tr('Cannot accept client request, client uses ":client" encoding while ":server" coding is required', [
            ':client' => Request::getEncoding(),
            ':server' => Response::getEncoding()
        ]));
    }


    /**
     * Returns the encoding for user data as specified by the client
     *
     * @return string|null
     */
    public static function detectEncoding(): ?string
    {
        throw new UnderConstructionException();
        // iconv(mb_detect_encoding($text, mb_detect_order(), true), 'UTF-8', $text);
    }


    /**
     * Returns a validated useragent
     *
     * @todo Implement useragent validation
     * @return string|null
     */
    public static function getUserAgent(): ?string
    {
        return Request::getHeader('User-Agent');
    }


    /**
     * Returns the remote IP address where this request came from
     *
     * @return string|null
     */
    public static function getRemoteIpAddress(): ?string
    {
        return array_get_safe($_SERVER, 'REMOTE_ADDR');
    }


    /**
     * Returns an alternative remote IP address where this request came from
     *
     * @return string|null
     */
    public static function getRemoteIpAddressReal(): ?string
    {
        return array_get_safe($_SERVER, 'HTTP_X_REAL_IP');
    }


    /**
     * Returns the language indicated in the URL, unless it is a non-supported language, in which case the default language will be returned
     *
     * @param string|null $locale
     *
     * @return string
     */
    public static function getLanguageFromUrl(?string $locale = null): string
    {
        $default   = config()->getString('locale.languages.default', 'en');
        $supported = config()->getArray('locale.languages.supported', [
            'en',
            'es',
        ]);

        if (empty($supported)) {
            $supported = [not_empty(Strings::until(Strings::until($locale, '_'), '-'), $default)];
        }

        // Language is defined by the www/LANGUAGE dir that is used.
        $url = $_SERVER['REQUEST_URI'];
        $url = Strings::ensureBeginsNotWith($url, '/');

        if (empty($url)) {
            return $default;
        }

        $language  = Strings::until($url, '/');
        $supported = array_unique($supported);

        if (!in_array($language, $supported, true)) {
            Incident::new()
                    ->setSeverity(EnumSeverity::low)
                    ->setType('Language')
                    ->setTitle('Unknown / unsupported language')
                    ->setUrl(Request::getUrl())
                    ->setBody(ts('The requested language ":language" is unsupported, falling back onto the default language ":default"', [
                        ':language' => $language,
                        ':default'  => $default,
                    ]))
                    ->setNotifyRoles('security')
                    ->setLog(7)
                    ->save();

            $language = $default;
        }

        return $language;
    }
}
