<?php

/**
 * Class RouteParameters
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Routing\Interfaces\RoutingParametersInterface;
use Templates\Phoundation\AdminLte\AdminLte;
use Templates\Phoundation\AdminLteV3\AdminLteV3;

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
     * @var PhoRestrictions|array|string|null $_restrictions
     */
    protected PhoRestrictions|array|string|null $_restrictions = null;

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
            // The default template is AdminLteV3
            $this->template = AdminLteV3::class;
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
            // Apply matches for this parameter pattern
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
     * @return PhoRestrictionsInterface
     * @todo Use TraitRestrictionsObject instead!
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface
    {
        return $this->_restrictions ?? PhoRestrictions::newWeb(false, 'RoutingParameter::setRestrictionsObject()');
    }


    /**
     * Sets the server restrictions
     *
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     *
     * @return static
     * @todo Use TraitRestrictionsObject instead!
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $_restrictions): static
    {
        $this->_restrictions = $_restrictions ?? PhoRestrictions::newWeb(false, 'RoutingParameter::setRestrictionsObject()');

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
        $this->root_url = (string) Url::new($root_url, true)->makeWww();

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
}
