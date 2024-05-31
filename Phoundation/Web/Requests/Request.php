<?php
/**
 * Class Request
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

namespace Phoundation\Web\Requests;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\Interfaces\AuthenticationExceptionInterface;
use Phoundation\Cache\Cache;
use Phoundation\Cache\InstanceCache;
use Phoundation\Core\Exception\Interfaces\CoreReadonlyExceptionInterface;
use Phoundation\Core\Exception\InvalidRequestTypeException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryReadonlyExceptionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataStaticContentType;
use Phoundation\Data\Traits\TraitDataStaticExecuted;
use Phoundation\Data\Traits\TraitGetInstance;
use Phoundation\Data\Validator\Exception\Interfaces\ValidationFailedExceptionInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Date\Time;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Traits\TraitDataStaticRestrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\Exception\Interfaces\IncidentsExceptionInterface;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Ajax\Ajax;
use Phoundation\Web\Api\Api;
use Phoundation\Web\Html\Components\Widgets\Menus\Interfaces\MenusInterface;
use Phoundation\Web\Html\Components\Widgets\Menus\Menus;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelsInterface;
use Phoundation\Web\Html\Components\Widgets\Panels\Panels;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;
use Phoundation\Web\Html\Template\Interfaces\TemplatePageInterface;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Exception\Http404Exception;
use Phoundation\Web\Http\Exception\Http405Exception;
use Phoundation\Web\Http\Exception\Http409Exception;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Json\Interfaces\JsonInterface;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Exception\RequestTypeException;
use Phoundation\Web\Requests\Exception\SystemPageNotFoundException;
use Phoundation\Web\Requests\Interfaces\RequestInterface;
use Phoundation\Web\Requests\Traits\TraitDataStaticRouteParameters;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;
use Stringable;
use Templates\Phoundation\AdminLte\AdminLte;
use Throwable;


abstract class Request implements RequestInterface
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
     * @var FileInterface $target
     */
    protected static FileInterface $target;

    /**
     * The real / initial target that was executed for this request
     *
     * @var FileInterface $main_target
     */
    protected static FileInterface $main_target;

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
     * The number of page levels that we're recursed in. Typically, this will be 0, but when executing pages from within
     * pages, recursing down, each time it will go up by one until that page is finished, then it will be lowered again
     *
     * @var int $stack_level
     */
    protected static int $stack_level = -1;

    /**
     * Sets if the request should render the entire page or the contents of the page only
     *
     * @var bool $main_contents_only
     */
    protected static bool $main_contents_only = false;

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
     * @var TemplatePageInterface|JsonInterface $page
     */
    protected static TemplatePageInterface|JsonInterface $page;

    /**
     * The template class that builds the UI
     *
     * @var TemplateInterface $template
     */
    protected static TemplateInterface $template;

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
     * Sets the routing parameters for this request
     *
     * @param RoutingParametersInterface $parameters
     * @param bool                       $force
     *
     * @return void
     */
    public static function setRoutingParameters(RoutingParametersInterface $parameters, bool $force = false): void
    {
        if (isset(static::$parameters)) {
            if (!$force) {
                throw new OutOfBoundsException(tr('Cannot set routing parameters for this request, routing parameters have already been set'));
            }
        }

        if (!$parameters->getTemplate()) {
            throw new OutOfBoundsException(tr('Cannot use routing parameters ":pattern", it has no template set', [
                ':pattern' => static::getRoutingParameters()
                                    ->getPattern(),
            ]));
        }

        static::$parameters = $parameters;
        static::setTemplate($parameters->getTemplateObject());
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
            static::$template = new AdminLte();
        }

        return static::$template;
    }


    /**
     * Sets the template to the specified template name
     *
     * @param TemplateInterface $template
     *
     * @return void
     */
    public static function setTemplate(TemplateInterface $template): void
    {
        static::$template = $template;
        static::$page     = $template->getPage();
    }


    /**
     * Returns the routing parameters for this request
     *
     * @return RoutingParametersInterface
     */
    public static function getRoutingParameters(): RoutingParametersInterface
    {
        if (empty(static::$parameters)) {
            throw new OutOfBoundsException(tr('Cannot return routing parameters from this request, no routing parameters have been set'));
        }

        return static::$parameters;
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
     *
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
     *
     * @return void
     */
    public static function setPanelsObject(PanelsInterface $panels): void
    {
        static::$panels = $panels;
    }


    /**
     * Returns the file executed for this request
     *
     * @return bool
     */
    public static function getMainContentsOnly(): bool
    {
        return static::$main_contents_only;
    }


    /**
     * Returns the file executed for this request
     *
     * @param bool $main_contents_only
     *
     * @return void
     */
    public static function setMainContentsOnly(bool $main_contents_only): void
    {
        static::$main_contents_only = $main_contents_only;
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
     * @return TemplatePageInterface|JsonInterface
     */
    public static function getPage(): TemplatePageInterface|JsonInterface
    {
        return static::$page;
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
                                ':languages' => $requested,
                            ]))
                            ->send();

                return Config::getString('languages.default', 'en');
        }
    }


    /**
     * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list
     * of languages / locales accepted by the HTTP client
     *
     * @return array The list of accepted languages and locales as specified by the HTTP client
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category  Function reference
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
                    'language' => Config::get('languages.default', 'en'),
                    'locale'   => Strings::cut(Config::get('locale.LC_ALL', 'US'), '_', '.'),
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
     * Returns the port used for this request. When on command line, assume the default from configuration
     *
     * @return int
     */
    public static function getPort(): int
    {
        if (PLATFORM_WEB) {
            return (int) $_SERVER['SERVER_PORT'];
        }
        // We're on a command line
        $config = Config::getArray('web.domains.primary');
        if (array_key_exists('port', $config)) {
            // Return configured WWW port
            return Config::getInteger('web.domains.primary.port');
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
                return UrlBuilder::getCurrent()
                                 ->__toString();
            }

            // Use the specified referrer
            return UrlBuilder::getWww($default)
                             ->__toString();
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

        return Strings::until(Config::getString('web.domains.primary.web'), '://');
    }


    /**
     * Return the URL for this page
     *
     * @param bool $no_queries
     *
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
     *
     * @param bool $no_queries
     *
     * @return string
     */
    public static function getUri(bool $no_queries = false): string
    {
        return ($no_queries ? Strings::until($_SERVER['REQUEST_URI'], '?') : $_SERVER['REQUEST_URI']);
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
     * Return the complete request URL for this page (WITH domain)
     *
     * @param string $type
     *
     * @return string
     */
    public static function getRootUrl(string $type = 'web'): string
    {
        return static::$parameters->getRootUrl($type);
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
        if (
            Session::getUser()
                   ->isGuest()
        ) {
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
        static::checkRequireNotGuestUser($guest_target);
        if (
            !Session::getUser()
                    ->hasAllRights($rights)
        ) {
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
        if (
            Session::getUser()
                   ->isGuest()
        ) {
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
        static::checkRequireNotGuestUser($guest_target);
        if (
            !Session::getUser()
                    ->hasSomeRights($rights)
        ) {
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
        if (!static::isRequestType($type)) {
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
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return void
     */
    public static function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): void
    {
        if (empty(static::$source)) {
            static::$source = new Iterator();
        }
        static::$source->add($value, $key, $skip_null, $exception);
    }


    /**
     * Returns the value for the specified data key, if exist. If not, the default value will be returned
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::$source?->get($key, false) ?? $default;
    }


    /**
     * Sets the value for the specified data key, if exist. If not, the default value will be returned
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     *
     * @return void
     */
    public static function set(mixed $value, Stringable|string|float|int $key): void
    {
        if (empty(static::$source)) {
            static::$source = new Iterator();
        }

        static::$source->set($value, $key);
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
        if (static::isExecutedDirectly()) {
            if (!$message) {
                $message = tr('The target ":target" cannot be accessed directly', [
                    ':page' => static::getTarget()
                                     ->getPath('root'),
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
     * @return FileInterface
     */
    public static function getTarget(): FileInterface
    {
        return static::$target;
    }


    /**
     * Sets the target for this request
     *
     * @param FileInterface|string $target
     *
     * @return void
     */
    protected static function setTarget(FileInterface|string $target): void
    {
        // Determine the target request type
        static::detectRequestType($target);

        $target         = static::ensureRequestPathPrefix($target);
        static::$target = File::new($target, static::getRestrictions())->makeAbsolute(DIRECTORY_WEB);
        static::$target->checkRestrictions(false);
        static::getTargets()->add(static::$target);
        static::addExecutedPath($target); // TODO We should get this from targets

        // Store request hash used for caching, store real / original target
        if (empty(static::$main_target)) {
            if (PLATFORM_WEB) {
                static::$hash = sha1($_SERVER['REQUEST_URI']);

            } else {
                static::$hash = sha1(Strings::force($_SERVER['argv']));
            }
            static::$main_target = static::$target;
            if (PLATFORM_WEB) {
                // Start the main web target buffer
                ob_start();
            }
        }
    }


    /**
     * Kill this web page script process
     *
     * @note Even if $exit_message was specified, the normal shutdown functions will still be called
     *
     * @param string|null $exit_message If specified, this message will be displayed and the process will be terminated
     * @param bool        $sig_kill
     *
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
        if (static::isPostRequestMethod()) {
            if (
                !Response::getFlashMessages()
                         ?->getCount()
            ) {
                Log::warning('Detected POST request without a flash message to give user feedback on what happened with this request!');
            }
        }
        switch (Response::getHttpCode()) {
            case 200:
                // no break
            case 301:
                // no break
            case 302:
                // no break
            case 304:
                Log::success(tr('Script(s) ":script" ended successfully with HTTP code ":http_code", sending ":sent" to client in ":time" with ":usage" peak memory usage', [
                    ':script'    => static::getExecutedPath(true),
                    ':time'      => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'     => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                    ':http_code' => Response::getHttpCode(),
                    ':sent'      => Numbers::getHumanReadableBytes(Response::getBytesSent()),
                ]));
                break;
            default:
                Log::warning(tr('Script(s) ":script" ended with HTTP warning code ":http_code", sending ":sent" to client  in ":time" with ":usage" peak memory usage', [
                    ':script'    => static::getExecutedPath(true),
                    ':time'      => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'     => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                    ':http_code' => Response::getHttpCode(),
                    ':sent'      => Numbers::getHumanReadableBytes(Response::getBytesSent()),
                ]));
        }

        InstanceCache::logStatistics();

        // Normal kill request
        Log::action(tr('Killing web page process'), 2);
        exit();
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
     * Returns if this request is the specified method
     *
     * @param string $method
     *
     * @return bool
     */
    public static function isRequestMethod(#[ExpectedValues(values: [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PATCH',
    ])] string $method): bool
    {
        return static::getRequestMethod() === strtoupper($method);
    }


    /**
     * Returns the request method for this page
     *
     * @return string
     */
    #[ExpectedValues(values: [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PATCH',
    ])]
    public static function getRequestMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
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
     * @param string|null $target
     *
     * @return void
     */
    public static function detectRequestType(?string $target = null): void
    {
        $target = $target ?? static::$target;

        if (PLATFORM_CLI) {
            // We're running on the command line
            $request_type = EnumRequestTypes::cli;

        } else {
            if (str_contains($target, '/admin/')) {
                $request_type = EnumRequestTypes::admin;

            } elseif (str_contains($target, '/ajax/')) {
                $request_type = EnumRequestTypes::ajax;

            } elseif (str_contains($target, '/api/') or (str_starts_with($_SERVER['SERVER_NAME'], 'api'))) {
                $request_type = EnumRequestTypes::api;

            } elseif (str_starts_with($_SERVER['SERVER_NAME'], 'cdn')) {
                $request_type = EnumRequestTypes::file;

            } elseif (Config::get('web.html.amp.enabled', false) and (!empty($_GET['amp']) or (str_starts_with($_SERVER['SERVER_NAME'], 'amp')))) {
                $request_type = EnumRequestTypes::amp;

            } elseif (is_numeric(substr($target, -3, 3))) {
                $request_type = EnumRequestTypes::system;

            } else {
                $request_type = EnumRequestTypes::html;
            }
        }

        static::setRequestType($request_type);
    }


    /**
     * Ensures that this session user has all the specified rights, or a redirect will happen
     *
     * @param array|string    $rights
     * @param string|int|null $rights_redirect
     * @param string|int|null $guest_redirect
     *
     * @return void
     */
    protected static function hasRightsOrRedirects(array|string $rights, string|int|null $rights_redirect = null, string|int|null $guest_redirect = null): void
    {
        if (Session::getUser()->hasAllRights($rights)) {

            if (Session::getSignInKey() === null) {
                // Well, then, all fine and dandy!
                return;
            }

            // Check sign-key restrictions and if those are okay, we are good to go
            static::hasSignKeyRestrictions($rights, static::$target);
            return;
        }

        // Is this a system page though? System pages require no rights to be viewed.
        if (static::getRequestType() === EnumRequestTypes::system) {
            // Hurrah, it's a bo, eh, system page! System pages require no rights. Everyone can see a 404, 500, etc...
            return;
        }

        // Is this a guest? Guests have no rights and can only see system pages and pages that require no rights
        if (Session::getUser()->isGuest()) {
            // This user has no rights at all, send to sign-in page
            if (!$guest_redirect) {
                $guest_redirect = 'sign-in';
            }

            $current        = Response::getRedirect(UrlBuilder::getCurrent());
            $guest_redirect = UrlBuilder::getWww($guest_redirect)
                                        ->addQueries($current ? 'redirect=' . $current : null);

            Incident::new()
                    ->setType('401 - Unauthorized')
                    ->setSeverity(Severity::low)
                    ->setTitle(tr('Guest user has no access to target page ":target" (real target ":real_target" requires rights ":rights"). Redirecting to ":redirect"', [
                        ':target'      => static::$target->getPath('web'),
                        ':real_target' => static::$target->getPath('web'),
                        ':redirect'    => $guest_redirect,
                        ':rights'      => Strings::force($rights, ', '),
                    ]))
                    ->setDetails([
                        'user'        => 0,
                        'uri'         => static::getUri(),
                        'target'      => static::$target->getPath('web'),
                        'real_target' => static::$target->getPath('web'),
                        'rights'      => $rights,
                    ])
                    ->save();

            if (static::isRequestType(EnumRequestTypes::api)) {
                // This method will exit
                Json::reply([
                    '__system' => [
                        'http_code' => 401,
                    ],
                ]);
            }

            if (static::isRequestType(EnumRequestTypes::ajax)) {
                // This method will exit
                Json::reply([
                    '__system' => [
                        'http_code' => 401,
                        'location'  => (string) UrlBuilder::getAjax('sign-in'),
                    ],
                ]);
            }

            // This method will exit
            Response::redirect($guest_redirect);
        }

        // This user is missing rights
        if (!$rights_redirect) {
            $rights_redirect = 403;
        }

        // Do the specified rights exist at all? If they aren't defined then no wonder this user doesn't have them
        if (Rights::getNotExist($rights)) {
            // One or more of the rights do not exist
            Incident::new()
                    ->setType('Non existing rights')
                    ->setSeverity(in_array('admin', Session::getUser()
                                                           ->getMissingRights($rights)) ? Severity::high : Severity::medium)
                    ->setTitle(tr('The requested rights ":rights" for target page ":target" (real target ":real_target") do not exist on this system and was not automatically created. Redirecting to ":redirect"', [
                        ':rights'      => Strings::force(Rights::getNotExist($rights), ', '),
                        ':target'      => static::$target->getPath('web'),
                        ':real_target' => static::$main_target->getPath('web'),
                        ':redirect'    => $rights_redirect,
                    ]))
                    ->setDetails([
                        'user'           => Session::getUser()
                                                   ->getLogId(),
                        'uri'            => static::getUri(),
                        'target'         => static::$target->getPath('web'),
                        'real_target'    => static::$main_target->getPath('web'),
                        'rights'         => $rights,
                        'missing_rights' => Rights::getNotExist($rights),
                    ])
                    ->notifyRoles('accounts')
                    ->save();

        } else {
            // Registered user does not have the required rights
            Incident::new()
                    ->setType('403 - Forbidden')
                    ->setSeverity(in_array('admin', Session::getUser()
                                                           ->getMissingRights($rights)) ? Severity::high : Severity::medium)
                    ->setTitle(tr('User ":user" does not have the required rights ":rights" for target page ":target" (real target ":real_target"). Executing "system/:redirect" instead', [
                        ':user'        => Session::getUser()
                                                 ->getLogId(),
                        ':rights'      => Session::getUser()
                                                 ->getMissingRights($rights),
                        ':target'      => static::$target->getPath('web'),
                        ':real_target' => static::$main_target->getPath('web'),
                        ':redirect'    => $rights_redirect,
                    ]))
                    ->setDetails([
                        'user'        => Session::getUser()
                                                ->getLogId(),
                        'uri'         => static::getUri(),
                        'target'      => static::$target->getPath('web'),
                        'real_target' => static::$main_target->getPath('web'),
                        'rights'      => Session::getUser()
                                                ->getMissingRights($rights),
                    ])
                    ->notifyRoles('accounts')
                    ->save();
        }
        // This method will exit
        static::executeSystem($rights_redirect);
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
        if (!static::isRequestType(EnumRequestTypes::html)) {
            Incident::new()
                    ->setType('401 - Unauthorized')
                    ->setSeverity(Severity::low)
                    ->setTitle(tr('Session keys cannot be used on ":type" requests', [
                        ':type' => static::getRequestType(),
                    ]))
                    ->setDetails([
                        'user'         => $key->getUser()
                                              ->getLogId(),
                        'uri'          => static::getUri(),
                        'target'       => static::$target->getPath('web'),
                        'real_target'  => Strings::from($target, DIRECTORY_ROOT),
                        'rights'       => $rights,
                        ':sign_in_key' => $key->getUuid(),
                    ])
                    ->save();
            static::executeSystem(401);
        }
        if (!$key->signKeyAllowsUrl(UrlBuilder::getCurrent(), $target)) {
            Incident::new()
                    ->setType('401 - Unauthorized')
                    ->setSeverity(Severity::low)
                    ->setTitle(tr('Cannot open URL ":url", sign in key ":uuid" does not allow navigation beyond ":allow"', [
                        ':url'   => UrlBuilder::getCurrent(),
                        ':allow' => $key->getRedirect(),
                        ':uuid'  => $key->getUuid(),
                    ]))
                    ->setDetails([
                        ':url'      => UrlBuilder::getCurrent(),
                        ':users_id' => $key->getUsersId(),
                        ':allow'    => $key->getRedirect(),
                        ':uuid'     => $key->getUuid(),
                    ])
                    ->save();
            // This method will exit
            static::executeSystem(401);
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
     * Attempts to set the request type to the
     *
     * @param EnumRequestTypes $request_type
     *
     * @return void
     */
    public static function setRequestType(EnumRequestTypes $request_type): void
    {
        if (static::$request_type !== EnumRequestTypes::unknown) {
            $fail = true;

            // We already have a request type determined, so we already have an appropriate response object initialized
            // as well. We cannot just change from generating a web page to returning an API output, for example, so
            // check if the change is allowed
            switch ($request_type) {
                case static::$request_type:
                    // The new request type matches the initial request type, we can continue. The response won't be
                    // reset, so we are done here
                    return;

                case EnumRequestTypes::system:
                    // Any HTML request can cause a 404, 500, etc., so any HTML request can switch to a system page
                    // no break

                case EnumRequestTypes::file:
                    // Any HTML request may generate and return a file, so any HTML request can switch to a file
                    switch (static::$request_type) {
                        case EnumRequestTypes::html:
                            // no break

                        case EnumRequestTypes::admin:
                            // no break

                        case EnumRequestTypes::amp:
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
                    ':target'  => static::$target,
                    ':new'     => $request_type,
                    ':current' => static::$request_type,
                ]));
            }

            // Clean any current responses that are in buffer
            Response::clean();
        }

        // Set up the response object for this request
        switch ($request_type) {
            case EnumRequestTypes::unsupported:
                throw new OutOfBoundsException(tr('Unsupported web request type ":type" encountered', [
                    ':type' => static::getRequestType(),
                ]));

            case EnumRequestTypes::unknown:
                throw new OutOfBoundsException(tr('Unknown web request type ":type" encountered', [
                    ':type' => static::getRequestType(),
                ]));

            default:
                static::$request_type = $request_type;
        }
    }


    /**
     * Executes the specified system page
     *
     * @param int            $http_code
     * @param Throwable|null $e
     * @param string|null    $message
     *
     * @return never
     */
    #[NoReturn] public static function executeSystem(int $http_code, ?Throwable $e = null, ?string $message = null): never
    {
        SystemRequest::new()->execute($http_code, $e, $message);
    }


    /**
     * Execute the specified target for this request and returns the output
     *
     * @param FileInterface|string $target
     *
     * @return string|null
     */
    public static function execute(FileInterface|string $target): ?string
    {
        return static::doExecute($target, false, false);
    }


    /**
     * Execute the specified target for this request and returns the output
     *
     * @param FileInterface|string $target
     * @param bool                 $die
     *
     * @return string|null
     */
    public static function executeAndFlush(FileInterface|string $target, bool $die = false): ?string
    {
        return static::doExecute($target, true, $die);
    }


    /**
     * Execute the specified target for this request and returns the output
     *
     * @param FileInterface|string $target
     * @param bool                 $flush
     * @param bool                 $die
     *
     * @return string|null
     */
    public static function doExecute(FileInterface|string $target, bool $flush, bool $die): ?string
    {
        // Set target and check if we have this target in the cache
        try {
            static::setTarget($target);
            static::$stack_level++;

        } catch (FileNotExistException $e) {
            static::processFileNotFound($e, $target);
        }
        if (PLATFORM_CLI) {
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
            static::preparePageVariable();
            $return = static::tryCache($die);
            if (!$return) {
                // Check user access rights from routing parameters
                // Check only for non-system pages
                if (!static::getSystem()) {
                    static::hasRightsOrRedirects(static::$parameters->getRequiredRights(static::$target));
                    Response::checkForceRedirect();
                }
                $return = static::executeWebTarget($flush);
            }
        }
        static::$stack_level--;
        if ($flush or (static::$stack_level < 0)) {
            // The stack is empty, there is nothing executing above this. Assume HTTP headers have been set by this
            // point, and send the output to the client
            Response::addOutput($return);
            Response::send(true);
        }

        // Return the output to the page that executed this page
        return $return;
    }


    /**
     * Process a FileNotFoundException
     *
     * @param FileNotExistException $e
     * @param FileInterface|string  $target
     *
     * @return never
     * @throws FileNotExistException
     */
    #[NoReturn] protected static function processFileNotFound(FileNotExistException $e, FileInterface|string $target): never
    {
        if (static::$stack_level >= 0) {
            Log::warning(tr('Sub target ":target" does not exist, displaying 500 page instead', [
                ':target' => $target,
            ]));
            throw $e;
        }
        if (static::getSystem()) {
            // This is not a normal request, this is a system request. System pages SHOULD ALWAYS EXIST, but if they
            // don't, hard fail because this method will normally execute a system page and we just saw those don't
            // exist for some reason
            throw new SystemPageNotFoundException(tr('The requested system page ":page" does not exist', [
                ':page' => $target,
            ]));
        }
        Log::warning(tr('Main target ":target" does not exist, displaying 404 page instead', [
            ':target' => $target,
        ]));
        Request::executeSystem(404);
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
    public static function getSubmitButton(string $post_key = 'submit', bool $prefix = false, bool $return_key = false): string|true|null
    {
        return PostValidator::getSubmitButton($post_key, $prefix, $return_key);
    }


    /**
     * This method will prepare the static::$page variable
     *
     * @return void
     */
    protected static function preparePageVariable(): void
    {
        switch (static::getRequestType()) {
            case EnumRequestTypes::api:
                Log::information(tr('Executing page ":target" on stack level ":level" with in language ":language" and sending output as API page', [
                    ':target'   => Strings::from(static::getTarget(), '/web/'),
                    ':template' => static::$template->getName(),
                    ':level'    => static::$stack_level,
                    ':language' => LANGUAGE,
                ]), (static::$stack_level ? 5 : 7));

                static::$page = new Api();
                break;

            case EnumRequestTypes::ajax:
                Log::information(tr('Executing page ":target" on stack level ":level" with in language ":language" and sending output as AJAX API page', [
                    ':target'   => Strings::from(static::getTarget(), '/web/'),
                    ':level'    => static::$stack_level,
                    ':language' => LANGUAGE,
                ]), (static::$stack_level ? 5 : 7));

                static::$page = new Ajax();

                if (!static::$stack_level) {
                    // Start session only for AJAX and HTML requests
                    Session::startup();
                }
                break;
            default:
                Log::information(tr('Executing page ":target" on stack level ":level" with template ":template" in language ":language" and sending output as HTML web page', [
                    ':target'   => Strings::from(static::getTarget(), '/web/'),
                    ':template' => static::$template->getName(),
                    ':level'    => static::$stack_level,
                    ':language' => LANGUAGE,
                ]), (static::$stack_level ? 5 : 7));

                // static::$page should already be defined at this stage
                if (empty(static::$page)) {
                    throw new OutOfBoundsException(tr('Cannot execute HTML page request for target ":target", no template specified', [
                        ':target' => static::$target,
                    ]));
                }

                if (!static::$stack_level) {
                    // Start session only for AJAX and HTML requests
                    // Initialize the flash messages
                    Session::startup();
                    Response::addFlashMessages(Session::getFlashMessages());
                }
        }
    }


    /**
     * Try to send this page from cache, if available
     *
     * @param bool $die
     *
     * @return string|null
     */
    protected static function tryCache(bool $die): ?string
    {
        // Do we have a cached version available?
        $cache = Cache::read(static::$hash, 'pages');
        if ($cache) {
            try {
                Log::action(tr('Sending cached reply to client'), 3);
                $cache = Json::decode($cache);
                Response::setHttpHeaders($cache['headers']);
                Response::addOutput($cache['output']);
                Response::send($die);

            } catch (Throwable $e) {
                // Cache failed!
                Log::warning(tr('Failed to send full cache page ":page" with following exception, ignoring cache and building page', [
                    ':page' => static::$hash,
                ]));
                Log::exception($e);
            }
        }

        return null;
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
        try {
            // Prepare page, increase the stack counter, and execute the target
            if (!$flush and static::$stack_level) {
                // Execute only the file and return the output
                return execute();
            }

            // Execute the entire page and return the output
            return static::$page->execute();

        } catch (ValidationFailedExceptionInterface $e) {
            static::executeSystem(400, $e, tr('Page did not catch the following "ValidationFailedException" warning. Executing "system/400" instead'));

        } catch (AuthenticationExceptionInterface $e) {
            static::executeSystem(401, $e, tr('Page did not catch the following "AuthenticationException" warning. Executing "system/401" instead'));

        } catch (IncidentsExceptionInterface $e) {
            $new_target = $e->getNewTarget();

            if (!$new_target) {
                static::executeSystem(403, $e, tr('Page did not catch the following "IncidentsExceptionInterface or AccessDeniedExceptionInterface" warning. Executing "system/401" instead'));
            }

            Log::warning(tr('Access denied to target ":target" for user ":user", executing specified new target ":new" instead', [
                ':target' => static::$target,
                ':user'   => Session::getUser()
                                    ->getDisplayId(),
                ':new'    => $new_target,
            ]));

            return static::execute($new_target);

        } catch (Http404Exception|DataEntryNotExistsExceptionInterface|DataEntryDeletedException $e) {
            static::executeSystem(404, $e, tr('Page did not catch the following "DataEntryNotExistsException" or "DataEntryDeletedException" warning. Executing "system/404" instead'));

        } catch (Http405Exception|DataEntryReadonlyExceptionInterface|CoreReadonlyExceptionInterface $e) {
            static::executeSystem(405, $e, tr('Page did not catch the following "Http405Exception or DataEntryReadonlyExceptionInterface or CoreReadonlyExceptionInterface" warning. Executing "system/405" instead'));

        } catch (Http409Exception $e) {
            static::executeSystem(409, $e, tr('Page did not catch the following "Http409Exception" warning. Executing "system/409" instead'));
        }
    }


    /**
     * Ensures that this request target path is absolute, or has the correct prefix
     *
     * @param FileInterface|string $target
     *
     * @return FileInterface|string
     */
    protected static function ensureRequestPathPrefix(FileInterface|string $target): FileInterface|string
    {
        if (is_string($target)) {
            if (is_absolute_path($target)) {
                return $target;
            }

        } elseif ($target->isAbsolute()) {
            return $target;

        } else {
            $target = $target->getPath();
        }

        return match (static::getRequestType()) {
            EnumRequestTypes::api     => Strings::ensureStartsWith($target, 'api/'),
            EnumRequestTypes::ajax    => Strings::ensureStartsWith($target, 'ajax/'),
            EnumRequestTypes::html,
            EnumRequestTypes::file,
            EnumRequestTypes::system,
            EnumRequestTypes::admin,
            EnumRequestTypes::amp     => Strings::ensureStartsWith($target, 'pages/'),
            default                   => throw new OutOfBoundsException(tr('Unsupported request type ":request" for this process', [
                ':request'            => static::getRequestType(),
            ])),
        };
    }
}
