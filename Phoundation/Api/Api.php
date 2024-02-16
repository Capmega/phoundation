<?php

declare(strict_types=1);

namespace Phoundation\Api;


use Phoundation\Core\Log\Log;
use Phoundation\Web\Page;


/**
 * Class Api
 *
 * This class contains methods to assist in building web pages
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Api
{
    /**
     * Returns a new ApiInterface object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Execute the specified API page
     *
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        return execute_page($target);
    }


    /**
     * Build and send API specific HTTP headers
     *
     * @param string $output
     * @return void
     */
    public function buildHttpHeaders(string $output): void
    {
        Page::setContentType('application/json');
    }
}