<?php

namespace Phoundation\Web\Routing;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Servers\Server;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\Http\UrlBuilder;
use Templates\AdminLte\AdminLte;



/**
 * Class RouteParameters
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @var Server|Restrictions|array|string $server_restrictions
     */
    protected Server|Restrictions|array|string $server_restrictions;

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
     * @var array|null $rights
     */
    protected ?array $rights = null;

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
     * RouteParameters class constructor
     */
    public function __construct(bool $system_pages_only = false)
    {
        $this->system_pages_only = $system_pages_only;
    }



    /**
     * Returns a new RouteParameters object
     *
     * @return RoutingParameters
     */
    public static function new(): RoutingParameters
    {
        return new RoutingParameters();
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
     * Returns the template as an object
     *
     * @return Template
     */
    public function getTemplateObject(): Template
    {
        return $this->template::new();
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
     * @return Server|Restrictions|array|string|null
     */
    public function getServerRestrictions(): Server|Restrictions|array|string|null
    {
        if (!isset($this->server_restrictions)) {
            // Set default server restrictions
            $this->server_restrictions = Core::ensureServer(PATH_WWW, null, 'Route');
        }

        return $this->server_restrictions;
    }



    /**
     * Sets the server restrictions
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @return static
     */
    public function setServerRestrictions(Server|Restrictions|array|string|null $server_restrictions): static
    {
        $this->server_restrictions = Core::ensureServer($server_restrictions, PATH_WWW, 'Route');
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
                $rights = $rights->list();
            } else {
                $rights = $rights->getSeoName();
            }
        } else {
            $rights = Arrays::force($rights);
        }

        return $rights;
    }
}