<?php

declare(strict_types=1);

namespace Phoundation\Api;


use Phoundation\Core\Log\Log;
use Phoundation\Web\Interfaces\WebRequestInterface;
use Phoundation\Web\Interfaces\WebResponseInterface;
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
     * @param WebRequestInterface $request
     * @param WebResponseInterface $response
     * @return string|null
     */
    public function execute(WebRequestInterface $request, WebResponseInterface $response): ?string
    {
        return execute_web_script($request, $response);
    }


    /**
     * Build and send API specific HTTP headers
     *
     * @param string $output
     * @return void
     */
    public function renderHttpHeaders(string $output): void
    {
        Page::setContentType('application/json');
    }
}