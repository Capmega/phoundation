<?php

/**
 * Class RouteParameters
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Core\Sessions\Session;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;
use Templates\Phoundation\AdminLte\AdminLte;

class RoutingParameters implements RoutingParametersInterface
{
    /**
     * Sets what template to use
     *
     * @var string $template
     */
    protected string $template;

    /**
     * Set what directory the router will be looking in for the scripts to execute
     *
     * @var string $root_directory
     */
    protected string $root_directory;

    /**
     * Server restrictions indicating what the router can access
     *
     * @var FsRestrictions|array|string|null $restrictions
     */
    protected FsRestrictions|array|string|null $restrictions = null;

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
    protected bool $system_pages_only = false;

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
     * If set, rights required to access a page would depend on each directory in its directory. The last directory in
     * the specified directory, and each subsequent directory below it until the file itself will be a required right
     * for the user to access that page
     *
     * @var string|null $require_directory_rights
     */
    protected ?string $require_directory_rights = null;

    /**
     * Exception file names to the required directory rights
     *
     * @var array|null $rights_exceptions
     */
    protected ?array $rights_exceptions = null;


    /**
     * RouteParameters class constructor
     */
    public function __construct(?string $pattern = null)
    {
        $this->pattern = $pattern;
    }


    /**
     * Returns the template as an object
     *
     * @return TemplateInterface
     */
    public function getTemplateObject(): TemplateInterface
    {
        return $this->template::new();
    }


    /**
     * Returns a new RouteParameters object
     *
     * @param string|null $pattern
     *
     * @return static
     */
    public static function new(?string $pattern = null): static
    {
        return new static($pattern);
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
     *
     * @return static
     */
    public function setTemplate(string $template): static
    {
        if (!is_subclass_of($template, TemplateInterface::class)) {
            throw new OutOfBoundsException(tr('Cannot construct new Route object: Specified template class ":class" is not a sub class of ":interface"', [
                ':class'     => $template,
                ':interface' => TemplateInterface::class,
            ]));
        }

        $this->template = $template;

        return $this;
    }


    /**
     * Returns what rights are required to access the specified target by specified rights and required directory rights
     *
     * @param string $target
     *
     * @return array
     */
    public function getRequiredRights(string $target): array
    {
        // Is this file an exception for required rights?
        if ($this->rights_exceptions and in_array(basename($target), $this->rights_exceptions)) {
            return [];
        }

        // Check defined rights and directory rights, both have to pass
        if ($this->require_directory_rights) {
            if (substr_count($this->require_directory_rights, '/') > 1) {
                $dirname = dirname($this->require_directory_rights);

            } else {
                $dirname = $this->require_directory_rights;
            }

            // First cut to WWW directory
            // Then the rest, as the directory may be partial
            // Then remove the file name to only have the directory parts
            // Ensure it doesn't start with a slash to avoid empty right entries
            // Then explode to array
            $directory = Strings::from($target, DIRECTORY_WEB);
            $directory = Strings::from($directory, $dirname);
            $directory = Strings::ensureStartsNotWith($directory, '/');
            $directory = dirname($directory);

            if ($directory === '.') {
                // Current directory, there is no directory
                $directory = [];

            } else {
                $directory = explode(FsPath::DIRECTORY_SEPARATOR, $directory);
            }

            // Merge with the already specified rights
            return array_merge($this->rights, $directory);
        }

        return $this->rights;
    }


    /**
     * Returns if (and from what directory onwards) rights should be taken from the directories automatically for each
     * page
     *
     * If set, rights required to access a page would depend on each directory in its directory. The last directory in
     * the specified directory, and each subsequent directory below it until the file itself will be a required right
     * for the user to access that page
     *
     * @return string|null
     */
    public function getRequireDirectoryRights(): ?string
    {
        return $this->require_directory_rights;
    }


    /**
     * Sets if (and from what directory onwards) rights should be taken from the directories automatically for each page
     *
     * If set, rights required to access a page would depend on each directory in its directory. The last directory in
     * the specified directory, and each subsequent directory below it until the file itself will be a required right
     * for the user to access that page
     *
     * @param string            $require_directory_rights
     * @param array|string|null $rights_exceptions
     *
     * @return static
     */
    public function setRequireDirectoryRights(string $require_directory_rights, array|string|null $rights_exceptions = null): static
    {
        $this->require_directory_rights = Strings::slash($require_directory_rights);

        if ($rights_exceptions) {
            $this->rights_exceptions = Arrays::force($rights_exceptions, null);
        }

        return $this;
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
     *
     * @return static
     */
    public function setRightsExceptions(array|string $exceptions): static
    {
        $this->rights_exceptions = Arrays::force($exceptions);

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
     *
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
     *
     * @return static
     */
    public function setMatches(array $matches): static
    {
        $this->matches = $matches;

        return $this;
    }


    /**
     * Returns the directory to use for the router so that it knows where to find the scripts to route to
     *
     * @return string
     */
    public function getRootDirectory(): string
    {
        if (!isset($this->root_directory)) {
            $this->root_directory = '';
        }

        $directory = $this->root_directory;

        if ($this->matches) {
            // Apply matches for this parameters pattern
            foreach ($this->matches as $key => $value) {
                $directory = str_replace('$' . $key, $value[0], $directory);
            }
        }

        return DIRECTORY_WEB . $directory;
    }


    /**
     * Sets the directory to use for the router so that it knows where to find the scripts to route to
     *
     * @param string $root_directory
     *
     * @return static
     */
    public function setRootDirectory(string $root_directory): static
    {
        $this->root_directory = $root_directory;

        return $this;
    }


    /**
     * Returns the server restrictions
     *
     * @return FsRestrictionsInterface
     */
    public function getRestrictions(): FsRestrictionsInterface
    {
        return $this->restrictions ?? FsRestrictions::getWeb(false, 'RoutingParameter::setRestrictions()');
    }


    /**
     * Sets the server restrictions
     *
     * @param FsRestrictionsInterface|array|string|null $restrictions
     *
     * @return static
     */
    public function setRestrictions(FsRestrictionsInterface|array|string|null $restrictions): static
    {
        $this->restrictions = $restrictions ?? FsRestrictions::getWeb(false, 'RoutingParameter::setRestrictions()');

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
     *
     * @return static
     */
    public function setRootUrl(string $root_url): static
    {
        // Make it a correct local URL
        $this->root_url = (string) Url::getWww($root_url, true);

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
     *
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
     *
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
     *
     * @param Rights|Right|array|string|null $rights
     *
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
     *
     * @return static
     */
    public function addRights(Rights|Right|array|string|null $rights): static
    {
        $this->rights = [];

        foreach ($this->getRightsArray($rights) as $right) {
            $this->add($right);
        }

        return $this;
    }


    /**
     * Returns an array of rights from whatever is specified
     *
     * @note This is an experimental function to see how we can have functions accept multiple formats
     * @todo See what we're going to do with this
     *
     * @param Rights|Right|array|string|null $rights
     *
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


    /**
     * Adds a required right to access this page
     *
     * @param Right|string|null $right
     *
     * @return static
     */
    public function add(Right|string|null $right): static
    {
        if ($right) {
            if (is_object($right)) {
                $right = $right->getSeoName();
            }

            $this->rights[] = $right;
        }

        return $this;
    }
}
