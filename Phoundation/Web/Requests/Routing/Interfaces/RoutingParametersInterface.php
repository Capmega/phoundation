<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests\Routing\Interfaces;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;


/**
 * interface RoutingParametersInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface RoutingParametersInterface
{
    /**
     * Returns the template as an object
     *
     * @return TemplateInterface
     */
    public function getTemplateObject(): TemplateInterface;

    /**
     * Returns the template to use
     *
     * @return string
     */
    public function getTemplate(): string;

    /**
     * Sets the template to use
     *
     * @param string $template
     * @return static
     */
    public function setTemplate(string $template): static;

    /**
     * Returns what rights are required to access the specified target by specified rights and required directory rights
     *
     * @param string $target
     * @return array
     */
    public function getRequiredRights(string $target): array;

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
    public function getRequireDirectoryRights(): ?string;

    /**
     * Returns filename exceptions to required directory rights
     *
     * @return array|null
     */
    public function getRightsExceptions(): ?array;

    /**
     * Returns filename exceptions to required directory rights
     *
     * @param array|string $exceptions
     * @return static
     */
    public function setRightsExceptions(array|string $exceptions): static;

    /**
     * Sets if (and from what directory onwards) rights should be taken from the directories automatically for each page
     *
     * If set, rights required to access a page would depend on each directory in its directory. The last directory in
     * the specified directory, and each subsequent directory below it until the file itself will be a required right
     * for the user to access that page
     *
     * @param string $require_directory_rights
     * @param array|string|null $rights_exceptions
     * @return static
     */
    public function setRequireDirectoryRights(string $require_directory_rights, array|string|null $rights_exceptions = null): static;

    /**
     * Returns the URI being processed
     *
     * @return string|null
     */
    public function getUri(): ?string;

    /**
     * Sets the URI being processed
     *
     * @param string $uri
     * @return static
     */
    public function setUri(string $uri): static;

    /**
     * Returns the matches for the URI being processed
     *
     * @return array|null
     */
    public function getMatches(): ?array;

    /**
     * Returns the matches for the URI being processed
     *
     * @param array $matches
     * @return static
     */
    public function setMatches(array $matches): static;

    /**
     * Returns the directory to use for the router so that it knows where to find the scripts to route to
     *
     * @return string
     */
    public function getRootDirectory(): string;

    /**
     * Sets the directory to use for the router so that it knows where to find the scripts to route to
     *
     * @param string $root_directory
     * @return static
     */
    public function setRootDirectory(string $root_directory): static;

    /**
     * Returns the server restrictions
     *
     * @return RestrictionsInterface
     */
    public function getRestrictions(): RestrictionsInterface;

    /**
     * Sets the server restrictions
     *
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public function setRestrictions(RestrictionsInterface|array|string|null $restrictions): static;

    /**
     * Returns the default base url for links generated by these pages
     *
     * @return string
     */
    public function getRootUrl(): string;

    /**
     * Sets the default base url for links generated by these pages
     *
     * @param string $root_url
     * @return static
     */
    public function setRootUrl(string $root_url): static;

    /**
     * Returns the regex that selects on what scripts these parameters will be applied
     *
     * @return string
     */
    public function getPattern(): string;

    /**
     * Sets the regex that selects on what scripts these parameters will be applied
     *
     * @param string $pattern
     * @return static
     */
    public function setPattern(string $pattern): static;

    /**
     * Returns if these parameters can only be used for system pages
     *
     * @return bool
     */
    public function getSystemPagesOnly(): bool;

    /**
     * Sets if these parameters can only be used for system pages
     *
     * @param bool $system_pages_only
     * @return static
     */
    public function setSystemPagesOnly(bool $system_pages_only): static;

    /**
     * Returns the required rights to access this page
     *
     * @return array
     */
    public function getRights(): array;

    /**
     * Sets the required rights to access this page
     *
     * @note Rights may be specified as a string, array, Right object, or Rights list. All should work fine
     * @param Rights|Right|array|string|null $rights
     * @return static
     */
    public function setRights(Rights|Right|array|string|null $rights): static;

    /**
     * Adds multiple required rights to access this page
     *
     * @param Rights|Right|array|string|null $rights
     * @return static
     */
    public function addRights(Rights|Right|array|string|null $rights): static;

    /**
     * Adds a required right to access this page
     *
     * @param Right|string|null $right
     * @return static
     */
    public function add(Right|string|null $right): static;
}