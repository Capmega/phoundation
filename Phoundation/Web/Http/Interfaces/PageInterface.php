<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Interfaces;


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
interface PageInterface
{
    /**
     * Sets the class for the given page section
     *
     * @param string $class
     * @param string $section
     * @return void
     */
    public static function setClass(string $class, string $section): void;

    /**
     * Sets the class for the given page section
     *
     * @param string $class
     * @param string $section
     * @return void
     */
    public static function defaultClass(string $class, string $section): void;

    /**
     * Returns the class for the given section, if available
     *
     * @param string $section
     * @param string|null $default
     * @return string|null
     */
    public static function getClass(string $section, ?string $default = null): ?string;
}
