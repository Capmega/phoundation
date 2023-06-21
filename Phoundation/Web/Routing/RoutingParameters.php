<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\Http\Protocols;
use Phoundation\Web\Http\UrlBuilder;
use Templates\AdminLte\AdminLte;


/**
 * Class RouteParameters
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class RoutingParameters
{
    /**
     * Sets what template to use
     *
     * @var string $template
     */
    protected string $template;

    /**
     * Set what path the router will be looking in for the scripts to execute
     *
     * @var string $root_path
     */
    protected string $root_path;

    /**
     * Server restrictions indicating what the router can access
     *
     * @var Restrictions|array|string $restrictions
     */
    protected Restrictions|array|string $restrictions;

    /**
     * Sets the default base URL for all links generated
     *
     * @var string $root_url
     */
    protected string $root_url;

    /**
     * The regex pattern on which these parameters apply
     *
     * @var string|null $pattern
     */
    protected ?string $pattern = null;

    /**
     * If true, these parameters will only apply to system pages
     *
     * @var bool $system_pages_only
     */
    protected bool $system_pages_only;

    /**
     * The required rights to access this resource
     *
     * @var array $rights
     */
    protected array $rights = [];

    /**
     * The URI being processed
     *
     * @var string|null $uri
     */
    protected ?string $uri = null;

    /**
     * The matches for this URI
     *
     * @var array|null $matches
     */
    protected ?array $matches = null;

    /**
     * If set, rights required to access a page would depend on each directory in its path. The last directory in the
     * specified path, and each subsequent directory below it until the file itself will be a required right for the
     * user to access that page
     *
     * @var string|null $require_path_rights
     */
    protected ?string $require_path_rights = null;

    /**
     * Exception file names to the required directory rights
     *
     * @var array|null $rights_exceptions
     */
    protected ?array $rights_exceptions = null;


    /**
     * RouteParameters class constructor
     */
    public function __construct(bool $system_pages_only = false)
    {
        $this->system_pages_only = $system_pages_only;
    }


    /**
     * Returns a new RouteParameters object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Returns the template as an object
     *
     * @return Template
     */
    public function getTemplateObject(): Template
    {
        return $this->template::new();
    }


    /**
     * Returns the template to use
     *
     * @return string
     */
    public function getTemplate(): string
    {
        if (!isset($this->template)) {
            // The default template is AdminLte
            $this->template = AdminLte::class;
        }

        return $this->template;
    }


    /**
     * Sets the template to use
     *
     * @param string $template
     * @return static
     */
    public function setTemplate(string $template): static
    {
        if (!is_subclass_of($template, 'Phoundation\Web\Http\Html\Template\Template')) {
            throw new OutOfBoundsException(tr('Cannot construct new Route object: Specified template class ":class" is not a sub class of "Phoundation\Web\Http\Html\Template\Template"', [
                ':class' => $template
            ]));
        }

        $this->template = $template;
        return $this;
    }


    /**
     * Returns what rights are required to access the specified target by specified rights and required directory rights
     *
     * @param string $target
     * @return array
     */
    public function getRequiredRights(string $target): array
    {
        // Is this file an exception for required rights?
        if ($this->rights_exceptions and in_array(basename($target), $this->rights_exceptions)) {
            return [];
        }

        // Check defined rights and directory rights, both have to pass
        if ($this->require_path_rights) {
            if (substr_count($this->require_path_rights, '/') > 1) {
                $dirname = dirname($this->require_path_rights);
            } else {
                $dirname = $this->require_path_rights;
            }

            // First cut to WWW path
            // Then the rest, as the path may be partial
            // Then remove the file name to only have the path parts
            // Ensure it doesn't start with a slash to avoid empty right entries
            // Then explode to array
            $path = Strings::from($target, PATH_WWW);
            $path = Strings::from($path, $dirname);
            $path = Strings::startsNotWith($path, '/');
            $path = dirname($path);

            if ($path === '.') {
                // Current directory, there is no path
                $path = [];
            } else {
                $path = explode(Filesystem::DIRECTORY_SEPARATOR, $path);
            }

            // Merge with the already specified rights
            return array_merge($this->rights, $path);
        }

        return $this->rights;
    }


    /**
     * Returns if (and from what directory onwards) rights should be taken from the directories automatically for each
     * page
     *
     * If set, rights required to access a page would depend on each directory in its path. The last directory in the
     * specified path, and each subsequent directory below it until the file itself will be a required right for the
     * user to access that page
     *
     * @return string|null
     */
    public function getRequirePathRights(): ?string
    {
        return $this->require_path_rights;
    }


    /**
     * Returns filename exceptions to required directory rights
     *
     * @return array|null
     */
    public function getRightsExceptions(): ?array
    {
        return $this->rights_exceptions;
    }


    /**
     * Returns filename exceptions to required directory rights
     *
     * @param array|string $exceptions
     * @return static
     */
    public function setRightsExceptions(array|string $exceptions): static
    {
        $this->rights_exceptions = Arrays::force($exceptions);
        return $this;
    }


    /**
     * Sets if (and from what directory onwards) rights should be taken from the directories automatically for each page
     *
     * If set, rights required to access a page would depend on each directory in its path. The last directory in the
     * specified path, and each subsequent directory below it until the file itself will be a required right for the
     * user to access that page
     *
     * @param string $require_path_rights
     * @param array|string|null $rights_exceptions
     * @return static
     */
    public function setRequirePathRights(string $require_path_rights, array|string|null $rights_exceptions = null): static
    {
        $this->require_path_rights = Strings::slash($require_path_rights);

        if ($rights_exceptions) {
            $this->rights_exceptions = Arrays::force($rights_exceptions, null);
        }

        return $this;
    }


    /**
     * Returns the URI being processed
     *
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }


    /**
     * Sets the URI being processed
     *
     * @param string $uri
     * @return static
     */
    public function setUri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }


    /**
     * Returns the matches for the URI being processed
     *
     * @return array|null
     */
    public function getMatches(): ?array
    {
        return $this->matches;
    }


    /**
     * Returns the matches for the URI being processed
     *
     * @param array $matches
     * @return static
     */
    public function setMatches(array $matches): static
    {
        $this->matches = $matches;
        return $this;
    }


    /**
     * Returns the path to use for the router so that it knows where to find the scripts to route to
     *
     * @return string
     */
    public function getRootPath(): string
    {
        if (!isset($this->root_path)) {
            $this->root_path = '';
        }

        $path = $this->root_path;

        if ($this->matches) {
            // Apply matches for this parameters pattern
            foreach ($this->matches as $key => $value) {
                $path = str_replace('$' . $key, $value[0], $path);
            }
        }

        return 'www/' . $path;
    }


    /**
     * Sets the path to use for the router so that it knows where to find the scripts to route to
     *
     * @param string $root_path
     * @return static
     */
    public function setRootPath(string $root_path): static
    {
        $this->root_path = $root_path;
        return $this;
    }


    /**
     * Returns the server restrictions
     *
     * @return RestrictionsInterface|array|string|null
     */
    public function getRestrictions(): RestrictionsInterface|array|string|null
    {
        if (!isset($this->restrictions)) {
            // Set default server restrictions
            $this->restrictions = Core::ensureRestrictions(PATH_WWW, false, 'Route');
        }

        return $this->restrictions;
    }


    /**
     * Sets the server restrictions
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public function setRestrictions(RestrictionsInterface|array|string|null $restrictions): static
    {
        $this->restrictions = Core::ensureRestrictions($restrictions, PATH_WWW, 'Route');
        return $this;
    }


    /**
     * Returns the default base url for links generated by these pages
     *
     * @return string
     */
    public function getRootUrl(): string
    {
        if (!isset($this->root_url)) {
            // If not specified, use the default configured root uri for this domain
            return Domains::getRootUrl();
        }

        $root_url = $this->root_url;
        $root_url = str_replace(':LANGUAGE', Session::getLanguage(), $root_url);

        return $root_url;
    }


    /**
     * Sets the default base url for links generated by these pages
     *
     * @param string $root_url
     * @return static
     */
    public function setRootUrl(string $root_url): static
    {
        // Apply keyword replacements
        $root_url = str_replace(':DOMAIN'  , Domains::getCurrent(), $root_url);
        $root_url = str_replace(':PROTOCOL', Protocols::getCurrent(), $root_url);

        $this->root_url = Strings::endsWith(Strings::startsNotWith($root_url, '/'), '/');
        return $this;
    }


    /**
     * Returns the regex that selects on what scripts these parameters will be applied
     *
     * @return string
     */
    public function getPattern(): string
    {
        if (!isset($this->pattern)) {
            $this->pattern = '';
        }

        return $this->pattern;
    }


    /**
     * Sets the regex that selects on what scripts these parameters will be applied
     *
     * @param string $pattern
     * @return static
     */
    public function setPattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }


    /**
     * Returns if these parameters can only be used for system pages
     *
     * @return bool
     */
    public function getSystemPagesOnly(): bool
    {
        return $this->system_pages_only;
    }


    /**
     * Sets if these parameters can only be used for system pages
     *
     * @param bool $system_pages_only
     * @return static
     */
    public function setSystemPagesOnly(bool $system_pages_only): static
    {
        $this->system_pages_only = $system_pages_only;
        return $this;
    }


    /**
     * Returns the required rights to access this page
     *
     * @return array
     */
    public function getRights(): array
    {
        return $this->rights;
    }


    /**
     * Sets the required rights to access this page
     *
     * @note Rights may be specified as a string, array, Right object, or Rights list. All should work fine
     * @param Rights|Right|array|string|null $rights
     * @return static
     */
    public function setRights(Rights|Right|array|string|null $rights): static
    {
        $this->rights = [];
        $this->addRights($rights);
        return $this;
    }


    /**
     * Adds multiple required rights to access this page
     *
     * @param Rights|Right|array|string|null $rights
     * @return static
     */
    public function addRights(Rights|Right|array|string|null $rights): static
    {
        $this->rights = [];

        foreach ($this->getRightsArray($rights) as $right) {
            $this->addRight($right);
        }

        return $this;
    }


    /**
     * Adds a required right to access this page
     *
     * @param Right|string|null $right
     * @return static
     */
    public function addRight(Right|string|null $right): static
    {
        if ($right) {
            if (is_object($right)) {
                $right = $right->getSeoName();
            }

            $this->rights[] = $right;
        }

        return $this;
    }


    /**
     * Returns an array of rights from whatever is specified
     *
     * @note This is an experimental function to see how we can have functions accept multiple formats
     * @todo See what we're going to do with this
     * @param Rights|Right|array|string|null $rights
     * @return array
     */
    protected function getRightsArray(Rights|Right|array|string|null $rights): array
    {
        if (is_object($rights)) {
            if ($rights instanceof Rights) {
                $rights = $rights->getSource();
            } else {
                $rights = $rights->getSeoName();
            }
        } else {
            $rights = Arrays::force($rights);
        }

        return $rights;
    }
}